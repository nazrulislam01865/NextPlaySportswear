<?php

namespace App\Services\Email;

/**
 * Turn the stored order/quote metadata into human-readable email rows.
 * Pure in-memory formatting: no queries, no model loading, and no JSON in the output.
 */
final class ForwardingEmailFormatter
{
    /**
     * @param array<string|int, mixed> $fields
     * @return array{title:string,rows:array<int,array{label:string,value:string,depth:int,group:bool}>}
     */
    public function section(string $title, array $fields): array
    {
        $rows = [];
        $this->flatten($fields, $rows, 0);

        return ['title' => $title, 'rows' => $rows];
    }

    /** @param array<string|int,mixed> $fields */
    public function asText(array $fields): string
    {
        return $this->rowsText($this->section('', $fields)['rows']);
    }

    /**
     * @param array<int,array{title:string,rows:array<int,array{label:string,value:string,depth:int,group:bool}>}> $sections
     */
    public function sectionsText(array $sections): string
    {
        $lines = [];
        foreach ($sections as $section) {
            if ($section['rows'] === []) {
                continue;
            }
            if ($lines !== []) {
                $lines[] = '';
            }
            $lines[] = strtoupper($section['title']);
            $lines[] = str_repeat('-', 42);
            $lines[] = $this->rowsText($section['rows']);
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param array<int,array{label:string,value:string,depth:int,group:bool}> $rows
     */
    private function rowsText(array $rows): string
    {
        $lines = [];
        foreach ($rows as $row) {
            $indent = str_repeat('  ', $row['depth']);
            if ($row['group']) {
                $lines[] = $indent.$row['label'].':';
            } else {
                $valueLines = explode("\n", str_replace(["\r\n", "\r"], "\n", $row['value']));
                $lines[] = $indent.$row['label'].': '.array_shift($valueLines);
                foreach ($valueLines as $line) {
                    $lines[] = $indent.'  '.$line;
                }
            }
        }

        return implode("\r\n", $lines);
    }

    /**
     * @param array<string|int,mixed> $fields
     * @param array<int,array{label:string,value:string,depth:int,group:bool}> $rows
     */
    private function flatten(array $fields, array &$rows, int $depth): void
    {
        foreach ($fields as $key => $rawValue) {
            if ($rawValue === null || $rawValue === '') {
                continue;
            }

            $label = $this->label($key);
            $value = $this->decodeJsonObject($rawValue);

            if (is_array($value)) {
                if ($value === []) {
                    continue;
                }
                $rows[] = ['label' => $label, 'value' => '', 'depth' => $depth, 'group' => true];
                $this->flatten($value, $rows, $depth + 1);
            } else {
                $rows[] = [
                    'label' => $label,
                    'value' => match (true) {
                        is_bool($value) => $value ? 'Yes' : 'No',
                        is_scalar($value) => (string) $value,
                        $value instanceof \Stringable => (string) $value,
                        default => '[Unsupported value]',
                    },
                    'depth' => $depth,
                    'group' => false,
                ];
            }
        }
    }

    private function label(int|string $key): string
    {
        if (is_int($key) || ctype_digit((string) $key)) {
            return 'Item '.((int) $key + 1);
        }

        $label = preg_replace('/(?<=[a-z0-9])(?=[A-Z])/', ' ', (string) $key);
        $label = ucwords(str_replace(['_', '-'], ' ', (string) $label));

        $acronyms = [
            'SKU' => 'SKU', 'URL' => 'URL', 'ID' => 'ID', 'PDF' => 'PDF',
            'ETA' => 'ETA', 'VAT' => 'VAT', 'RFQ' => 'RFQ', 'HTML' => 'HTML',
            'UUID' => 'UUID',
        ];

        return preg_replace_callback('/\b(?:SKU|URL|ID|PDF|ETA|VAT|RFQ|HTML|UUID)\b/i',
            static fn (array $match): string => $acronyms[strtoupper($match[0])], $label);
    }

    private function decodeJsonObject(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if ($trimmed === '' || ! in_array($trimmed[0], ['{', '['], true)) {
            return $value;
        }

        $parsed = json_decode($trimmed, true, 64);

        return json_last_error() === JSON_ERROR_NONE && is_array($parsed) ? $parsed : $value;
    }
}

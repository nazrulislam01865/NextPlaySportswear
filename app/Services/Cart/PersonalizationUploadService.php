<?php

namespace App\Services\Cart;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** Private, per-item personalization uploads. Never accept paths from client JSON. */
class PersonalizationUploadService
{
    public function prepare(Request $request, array $product, array $existing = []): array
    {
        $config = json_decode((string) $request->input('configuration_json', '{}'), true);
        $config = is_array($config) ? $config : [];
        $rows = array_values((array) ($config['roster'] ?? []));
        $fields = collect((array) data_get($product, 'roster.fields', []))
            ->filter(fn ($f) => is_array($f) && ($f['enabled'] ?? true))
            ->keyBy('key');
        $incoming = (array) $request->file('personalization_files', []);
        $retained = collect($existing)->filter(fn ($f) => is_array($f) && filled($f['path'] ?? null))
            ->keyBy(fn ($f) => ($f['row'] ?? '').':'.($f['key'] ?? ''));
        $files = [];
        $newPaths = [];
        $uploadCount = 0;

        try {
            foreach ($rows as $rowIndex => &$row) {
                if (!is_array($row)) $row = [];
                if (!is_array($row['values'] ?? null)) $row['values'] = [];
                foreach ($fields as $key => $field) {
                    if (!in_array($field['type'] ?? '', ['image', 'file'], true)) continue;
                    $key = (string) $key;
                    $uploaded = $incoming[$rowIndex][$key] ?? null;
                    if ($uploaded && (string) ($row['values'][$key] ?? '') !== '') {
                        if (!$uploaded->isValid()) {
                            throw ValidationException::withMessages(['personalization_files' => 'The uploaded personalization file could not be received.']);
                        }
                        $uploadCount++;
                        if ($uploadCount > 20) {
                            throw ValidationException::withMessages(['personalization_files' => 'Upload no more than 20 personalization files per cart item.']);
                        }
                        $type = $field['type'];
                        $allowed = $type === 'image'
                            ? ['image/png', 'image/jpeg', 'image/webp']
                            : ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain', 'text/csv', 'application/csv', 'image/png', 'image/jpeg', 'image/webp'];
                        $mime = (string) $uploaded->getMimeType();
                        $max = ($type === 'image' ? 5 : 10) * 1024 * 1024;
                        if (!in_array($mime, $allowed, true) || $uploaded->getSize() > $max) {
                            throw ValidationException::withMessages(['personalization_files' => 'Upload a supported '.($type === 'image' ? 'PNG, JPG or WebP image (5 MB maximum).' : 'PDF, DOC, DOCX, TXT, CSV or image file (10 MB maximum).')]);
                        }
                        $path = $uploaded->store('customer-personalization/'.hash('sha256', $request->session()->getId()), 'local');
                        if (!$path) {
                            throw ValidationException::withMessages(['personalization_files' => 'Unable to save the uploaded file.']);
                        }
                        $newPaths[] = $path;
                        $name = mb_substr(basename($uploaded->getClientOriginalName()), 0, 200);
                        $files[] = ['row' => $rowIndex, 'key' => $key, 'path' => $path, 'original_name' => $name,
                            'mime_type' => $mime, 'size' => $uploaded->getSize(), 'type' => $type];
                        $row['values'][$key] = $name;
                    } else {
                        $previous = $retained->get($rowIndex.':'.$key);
                        // An edit may retain its existing file, but only if the previous
                        // filename still matches the edited row's visible field value.
                        if ($previous && (string) ($row['values'][$key] ?? '') === (string) ($previous['original_name'] ?? '') &&
                            Storage::disk('local')->exists((string) $previous['path'])) {
                            $files[] = $previous;
                        } else {
                            $row['values'][$key] = '';
                        }
                    }
                }
            }
            unset($row);
            // Unknown keys, rows or fields are never accepted as valid uploads.
            foreach ($incoming as $rowIndex => $rowFiles) {
                foreach ((array) $rowFiles as $key => $file) {
                    if (!isset($rows[$rowIndex]) || !in_array($fields->get($key)['type'] ?? '', ['image', 'file'], true) || !$file) {
                        throw ValidationException::withMessages(['personalization_files' => 'A file was uploaded to an invalid personalization field.']);
                    }
                }
            }
            $config['roster'] = $rows;
            return ['configuration_json' => json_encode($config, JSON_THROW_ON_ERROR), 'files' => $files, 'new_paths' => $newPaths];
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($newPaths);
            throw $e;
        }
    }
}

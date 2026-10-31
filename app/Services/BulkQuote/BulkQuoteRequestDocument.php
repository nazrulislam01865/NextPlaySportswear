<?php

namespace App\Services\BulkQuote;

use App\Models\BulkQuoteRequest;
use App\Support\StorefrontBranding;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Phar;
use PharData;

/** Produce a self-contained Word document; artwork pages are visible inline. */
final class BulkQuoteRequestDocument
{
    private array $media = [];
    private array $relationships = [];
    private int $pictureId = 0;
    private string $workingDirectory;

    public function create(BulkQuoteRequest $quote): string
    {
        $this->media = [];
        $this->relationships = [];
        $this->pictureId = 0;
        $this->workingDirectory = sys_get_temp_dir().'/nps-bq-'.bin2hex(random_bytes(8));
        if (! mkdir($this->workingDirectory, 0700) && ! is_dir($this->workingDirectory)) {
            throw new RuntimeException('Unable to create a temporary document directory.');
        }

        try {
            $xml = '';
            $logo = $this->brandLogo();
            if ($logo) {
                $xml .= $this->image($logo, 2.05, 0.8);
            } else {
                // One brand identity per document header: uploaded header logo OR name.
                $xml .= $this->paragraph(trim((string) config('storefront.name', '')) ?: 'NextPlay Sportswear', 'Brand');
            }
            $contacts = array_filter([
                (string) config('storefront.address', ''),
                (string) config('storefront.email', ''),
                (string) config('storefront.phone', ''),
            ], fn (string $v) => trim($v) !== '' && ! str_contains($v, 'example.com'));
            foreach ($contacts as $contact) {
                $xml .= $this->paragraph($contact, 'Contact');
            }
            $xml .= $this->paragraph('Bulk Quotation Request', 'Title');
            $xml .= $this->paragraph('Reference: '.$quote->reference.'    |    Submitted: '.($quote->created_at?->format('d M Y, g:i A') ?: '—'), 'Muted');

            $xml .= $this->heading('Customer and delivery address');
            $xml .= $this->table([
                ['Customer', $quote->full_name], ['Organization', $quote->organization],
                ['Email', $quote->email], ['Phone', $quote->phone],
                ['Shipping address', $quote->shipping_address], ['Country', $quote->country],
                ['State / Province', $quote->state_province], ['Postal code', $quote->postal_code],
            ]);
            $xml .= $this->heading('Product and customization');
            $xml .= $this->table([
                ['Product', $quote->product_type], ['Quantity', $quote->estimatedQuantityLabel()],
                ['Size', $quote->sizes_needed], ['Budget', $quote->budgetRangeLabel()],
                ['Customization', implode(', ', $quote->customizationLabels())],
                ['Artwork / logo details', $quote->artwork_details],
            ]);
            $product = is_array($quote->product_information) ? $quote->product_information : [];
            if ($product !== []) {
                $rows = [['Selected product', $product['product_name'] ?? ''], ['SKU', $product['sku'] ?? '']];
                foreach (($product['options'] ?? []) as $option) {
                    if (is_array($option)) {
                        $rows[] = [$option['label'] ?? 'Option', implode(', ', array_map('strval', (array) ($option['values'] ?? [$option['value'] ?? ''])))];
                    }
                }
                foreach (($product['size_groups'] ?? []) as $group) {
                    if (is_array($group)) {
                        $sizes = [];
                        foreach (($group['sizes'] ?? []) as $size) {
                            if (is_array($size)) {
                                $sizes[] = ($size['size'] ?? 'Size').': '.($size['quantity'] ?? 0);
                            }
                        }
                        $rows[] = ['Size & Quantity'.(isset($group['label']) ? ' — '.$group['label'] : ''), implode(', ', $sizes)];
                    }
                }
                foreach (($product['selected_products'] ?? []) as $item) {
                    if (is_array($item)) {
                        $label = ($item['name'] ?? 'Product').' · '.($item['quantity'] ?? 0).' '.($item['unit'] ?? 'pcs');
                        $rows[] = ['Requested product', $label];
                        foreach (['description' => 'Description', 'sizes' => 'Sizes', 'material' => 'Fabric',
                            'colors' => 'Colours', 'budget' => 'Budget', 'customization_details' => 'Customisation details'] as $key => $name) {
                            if (filled($item[$key] ?? null)) $rows[] = [$name, (string) $item[$key]];
                        }
                        if (! empty($item['customizations'])) $rows[] = ['Customisation options', implode(', ', (array) $item['customizations'])];
                        foreach ((array) ($item['options'] ?? []) as $option) {
                            if (is_array($option)) $rows[] = [(string) ($option['label'] ?? 'Option'),
                                implode(', ', array_map('strval', (array) ($option['values'] ?? [$option['value'] ?? ''])))];
                        }
                        foreach ((array) ($item['size_groups'] ?? []) as $group) {
                            $sizes = [];
                            foreach ((array) ($group['sizes'] ?? []) as $size) {
                                if (is_array($size)) $sizes[] = ($size['size'] ?? 'Size').': '.($size['quantity'] ?? 0);
                            }
                            if ($sizes !== []) $rows[] = ['Size & Quantity', implode(', ', $sizes)];
                        }
                    }
                }
                $rows[] = ['Product notes', $product['notes'] ?? ''];
                $xml .= $this->table($rows);
            }
            $xml .= $this->heading('Delivery requirements');
            $xml .= $this->table([
                ['Needed by', $quote->needed_by?->format('d M Y')], ['Event date', $quote->event_date?->format('d M Y')],
                ['Shipping method', $quote->preferredShippingMethodLabel()],
                ['Additional customer notes', $quote->additional_notes],
            ]);
            $xml .= $this->heading('Artwork and attachments');
            $attachment = (array) $quote->attachment;
            $artworks = [];
            if (! empty($attachment['path'])) $artworks[$attachment['path']] = $attachment;
            foreach ((array) ($product['selected_products'] ?? []) as $entry) {
                $file = is_array($entry) ? ($entry['artwork'] ?? null) : null;
                if (is_array($file) && filled($file['path'] ?? null)) $artworks[$file['path']] = $file;
            }
            if ($artworks === []) {
                $xml .= $this->paragraph('No artwork file was uploaded.', 'Muted');
            } else {
                foreach ($artworks as $artwork) {
                    $name = basename((string) ($artwork['original_name'] ?? 'Customer artwork'));
                    $xml .= $this->paragraph('Original file: '.$name, 'Normal');
                    $input = $this->readAttachment($artwork);
                    $images = $this->previewPages($input, $name);
                    foreach ($images as $i => $path) {
                        $xml .= $this->paragraph('Artwork preview'.(count($images) > 1 ? ' — page '.($i + 1) : ''), 'Muted');
                        $xml .= $this->image($path, 6.65, 8.25);
                    }
                    if ($images === []) {
                        throw new RuntimeException('Unable to display the uploaded artwork in Word. Check the document conversion tools.');
                    }
                }
            }
            $xml .= $this->paragraph('Original customer request — not a priced quotation.', 'Muted');

            $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
                .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
                .' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
                .' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
                .' xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"'
                .' xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">'
                .'<w:body>'.$xml.'<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="720" w:right="900" w:bottom="720" w:left="900"/></w:sectPr></w:body></w:document>';

            $path = tempnam(sys_get_temp_dir(), 'nps-quote-');
            if ($path === false) {
                throw new RuntimeException('Cannot allocate the document file.');
            }
            unlink($path);
            $path .= '.docx';
            $zip = new PharData($path, 0, null, Phar::ZIP);
            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
            $zip->addFromString('word/document.xml', $document);
            $rels = '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';
            foreach ($this->relationships as $id => $target) {
                $rels .= '<Relationship Id="'.$id.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/'.$target.'"/>';
            }
            $zip->addFromString('word/_rels/document.xml.rels', $rels.'</Relationships>');
            $zip->addFromString('word/styles.xml', $this->styles());
            foreach ($this->media as $filename => $source) {
                $zip->addFile($source, 'word/media/'.$filename);
            }
            unset($zip);
            // Our minimal OOXML package can be opened by LibreOffice, but Word for Mac
            // rejects some hand-authored package variants. Re-save via an established
            // Office OOXML writer before serving the download (and reject failures).
            try {
                $this->normalizeForWord($path);
            } catch (\Throwable $exception) {
                @unlink($path);
                if ($exception instanceof RuntimeException) {
                    throw $exception;
                }
                throw new RuntimeException('Could not create a Microsoft Word-compatible request document.', 0, $exception);
            }
            return $path;
        } finally {
            $this->removeDirectory($this->workingDirectory);
        }
    }

    private function brandLogo(): ?string
    {
        // Exactly the image used by the storefront header (not the footer logo).
        // StorefrontBranding already caches this setting; no additional query.
        $path = StorefrontBranding::logoPath();
        if ($path) {
            $disk = Storage::disk('public');
            if (! $disk->exists($path)) {
                throw new RuntimeException('The selected Storefront Branding header logo is missing from storage.');
            }

            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'avif'], true)) {
                throw new RuntimeException('The Storefront Branding header logo has an unsupported image format.');
            }

            $logo = $this->workingDirectory.'/header-logo.'.$extension;
            file_put_contents($logo, $disk->get($path));
        } else {
            // Match the header's configured fallback when no logo was uploaded.
            $configured = trim((string) config('storefront.logo', '/images/logo.png'));
            $logo = public_path(ltrim($configured, '/'));
            if (! is_file($logo)) {
                $logo = public_path('images/logo.png');
            }
            if (! is_file($logo)) {
                return null;
            }
        }

        $extension = strtolower(pathinfo($logo, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            return $logo;
        }

        // Word embeds PNG/JPEG reliably. Convert WebP/AVIF using PHP's GD
        // extension, preserving transparency without an external image request.
        $decoder = match ($extension) {
            'webp' => 'imagecreatefromwebp',
            'avif' => 'imagecreatefromavif',
            default => null,
        };
        if ($decoder === null || ! function_exists($decoder)) {
            throw new RuntimeException('The storefront header logo requires PHP GD with '.strtoupper($extension).' support for Word export.');
        }
        $image = @$decoder($logo);
        if (! $image instanceof \GdImage) {
            throw new RuntimeException('Could not read the Storefront Branding header logo.');
        }
        try {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $converted = $this->workingDirectory.'/header-logo.png';
            if (! imagepng($image, $converted)) {
                throw new RuntimeException('Could not convert the Storefront Branding header logo.');
            }
        } finally {
            imagedestroy($image);
        }

        return $converted;
    }

    private function readAttachment(array $attachment): string
    {
        $disk = Storage::disk(($attachment['disk'] ?? '') === 'local' ? 'local' : 'public');
        $path = trim((string) ($attachment['path'] ?? ''));
        if ($path === '' || ! $disk->exists($path)) {
            throw new RuntimeException('The original customer artwork is missing from storage.');
        }
        $extension = strtolower(pathinfo((string) ($attachment['original_name'] ?? $path), PATHINFO_EXTENSION));
        if (! in_array($extension, ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            throw new RuntimeException('Preview is not supported for this attachment type.');
        }
        $target = $this->workingDirectory.'/customer-artwork.'.$extension;
        $source = $disk->readStream($path);
        if (! is_resource($source)) {
            throw new RuntimeException('Could not read the original customer artwork.');
        }
        $destination = fopen($target, 'wb');
        try {
            stream_copy_to_stream($source, $destination);
        } finally {
            fclose($source);
            fclose($destination);
        }
        return $target;
    }

    /** @return list<string> */
    private function previewPages(string $file, string $name): array
    {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'], true)) {
            return [$file];
        }
        if ($ext !== 'pdf') {
            $this->run([$this->officeBinary(), '-env:UserInstallation=file://'.$this->workingDirectory.'/lo-profile', '--headless', '--convert-to', 'pdf', '--outdir', $this->workingDirectory, $file]);
            $file = substr($file, 0, strrpos($file, '.')).'.pdf';
            if (! is_file($file)) {
                throw new RuntimeException('Office preview conversion failed for '.$name.'.');
            }
        }
        $prefix = $this->workingDirectory.'/artwork';
        $this->run([(string) config('bulk_quote_document.pdftoppm', 'pdftoppm'), '-f', '1', '-l', '20', '-scale-to', '1400', '-png', $file, $prefix]);
        $files = glob($prefix.'-*.png') ?: [];
        sort($files, SORT_NATURAL);
        return array_values($files);
    }

    /**
     * Word-compatible DOCX packages must be saved by a complete OOXML writer.
     * Do not return the intermediate, hand-built package: Mac Word may offer to
     * repair it even though basic ZIP/XML checks and LibreOffice reading succeed.
     */
    private function normalizeForWord(string $source): void
    {
        $outputDirectory = $this->workingDirectory.'/office-output';
        if (! mkdir($outputDirectory, 0700) && ! is_dir($outputDirectory)) {
            throw new RuntimeException('Unable to prepare the Word document output directory.');
        }

        try {
            $this->run([
                $this->officeBinary(),
                '-env:UserInstallation=file://'.$this->workingDirectory.'/word-profile',
                '--headless',
                '--convert-to',
                'docx:Office Open XML Text',
                '--outdir',
                $outputDirectory,
                $source,
            ]);
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                'The request document could not be made compatible with Microsoft Word. '
                .'Install LibreOffice Writer (or configure BULK_QUOTE_SOFFICE_BIN) and try again.',
                0,
                $exception,
            );
        }

        $converted = $outputDirectory.'/'.basename($source);
        if (! is_file($converted) || filesize($converted) < 2000) {
            throw new RuntimeException('The Word-compatible request document was not produced. Check LibreOffice Writer.');
        }

        try {
            $archive = new PharData($converted);
            foreach (['[Content_Types].xml', '_rels/.rels', 'word/document.xml', 'word/styles.xml'] as $part) {
                if (! isset($archive[$part])) {
                    throw new RuntimeException('The Word document is missing a required package part: '.$part);
                }
            }
            unset($archive);
        } catch (\UnexpectedValueException $exception) {
            throw new RuntimeException('The Word document archive is invalid.', 0, $exception);
        }

        if (! rename($converted, $source)) {
            throw new RuntimeException('Could not finalize the Word-compatible request document.');
        }
    }

    private function officeBinary(): string
    {
        $configured = trim((string) config('bulk_quote_document.soffice', 'soffice'));
        if ($configured === '' || $configured === 'soffice') {
            // Local macOS development: the LibreOffice app isn't always in PATH.
            $macExecutable = '/Applications/LibreOffice.app/Contents/MacOS/soffice';
            return is_executable($macExecutable) ? $macExecutable : 'soffice';
        }

        return $configured;
    }

    private function run(array $command): void
    {
        $process = @proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $this->workingDirectory);
        if (! is_resource($process)) {
            throw new RuntimeException('The document preview utility is not installed or cannot start.');
        }
        fclose($pipes[0]);
        stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0) {
            throw new RuntimeException('Artwork conversion failed. Ensure the server has Poppler and LibreOffice installed. '.substr(trim($errors), 0, 160));
        }
    }

    private function paragraph(?string $content, string $style = 'Normal'): string
    {
        $escaped = $this->escape(trim((string) ($content ?? '')) ?: '—');
        return '<w:p><w:pPr><w:pStyle w:val="'.$style.'"/><w:spacing w:after="100"/></w:pPr><w:r><w:t xml:space="preserve">'.$escaped.'</w:t></w:r></w:p>';
    }

    private function heading(string $value): string
    {
        return $this->paragraph($value, 'Heading1');
    }

    private function table(array $rows): string
    {
        // Hanging tab rows stay compact and wrap long values beneath their value column.
        $xml = '';
        foreach ($rows as [$label, $value]) {
            $label = $this->escape((string) $label);
            $value = $this->escape(trim((string) ($value ?? '')) ?: '—');
            $xml .= '<w:p><w:pPr><w:tabs><w:tab w:val="left" w:pos="2860"/></w:tabs><w:spacing w:after="90"/><w:ind w:left="2860" w:hanging="2860"/></w:pPr>'
                .'<w:r><w:rPr><w:b/><w:color w:val="52657C"/></w:rPr><w:t>'.$label.'</w:t></w:r>'
                .'<w:r><w:tab/><w:t xml:space="preserve">'.$value.'</w:t></w:r></w:p>';
        }
        return $xml;
    }

    private function image(string $path, float $maxWidth, float $maxHeight): string
    {
        $image = @getimagesize($path);
        if (! $image || ! in_array($image[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            throw new RuntimeException('Artwork image could not be embedded: only PNG and JPEG are supported.');
        }
        $name = 'image'.(++$this->pictureId).($image[2] === IMAGETYPE_PNG ? '.png' : '.jpg');
        $rid = 'rId'.($this->pictureId + 1);
        $this->media[$name] = $path;
        $this->relationships[$rid] = $name;
        $factor = min($maxWidth / $image[0], $maxHeight / $image[1]);
        $cx = (int) round($image[0] * $factor * 914400);
        $cy = (int) round($image[1] * $factor * 914400);
        $id = $this->pictureId;
        return '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0">'
            .'<wp:extent cx="'.$cx.'" cy="'.$cy.'"/><wp:docPr id="'.$id.'" name="Embedded Artwork '.$id.'"/>'
            .'<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic>'
            .'<pic:nvPicPr><pic:cNvPr id="'.$id.'" name="'.$name.'"/><pic:cNvPicPr/></pic:nvPicPr>'
            .'<pic:blipFill><a:blip r:embed="'.$rid.'"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>'
            .'<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="'.$cx.'" cy="'.$cy.'"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>'
            .'</pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Aptos" w:hAnsi="Aptos"/><w:color w:val="192C49"/><w:sz w:val="20"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Brand"><w:name w:val="Brand"/><w:rPr><w:b/><w:color w:val="0A2448"/><w:sz w:val="26"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:color w:val="0A2448"/><w:sz w:val="32"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="Heading 1"/><w:pPr><w:spacing w:before="210" w:after="85"/></w:pPr><w:rPr><w:b/><w:color w:val="EA5127"/><w:sz w:val="24"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Muted"><w:name w:val="Muted"/><w:rPr><w:color w:val="63748A"/><w:sz w:val="18"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Label"><w:name w:val="Label"/><w:rPr><w:b/><w:color w:val="52657C"/><w:sz w:val="19"/></w:rPr></w:style>'
            .'<w:style w:type="paragraph" w:styleId="Contact"><w:name w:val="Contact"/><w:rPr><w:color w:val="63748A"/><w:sz w:val="17"/></w:rPr></w:style>'
            .'</w:styles>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Default Extension="png" ContentType="image/png"/>'
            .'<Default Extension="jpg" ContentType="image/jpeg"/>'
            .'<Default Extension="jpeg" ContentType="image/jpeg"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'</Types>';
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($path);
    }
}

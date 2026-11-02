<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class ArtworkGuidelinesPageService
{
    public const PAGE_KEY = 'artwork-guidelines';
    public const CACHE_KEY = 'storefront.page.artwork-guidelines';

    /**
     * @return array<string, mixed>
     */
    public function defaults(): array
    {
        return [
            'hero' => [
                'eyebrow' => 'NEXTPLAY SPORTSWEAR',
                'title' => 'Artwork Guidelines',
                'description' => 'Everything you need to know about preparing and submitting artwork for your custom sportswear.',
            ],
            'section_01' => [
                'number' => '01',
                'title' => 'File formats',
                'formats' => [
                    [
                        'name' => 'AI or EPS (Vector)',
                        'badge' => 'Recommended',
                        'badge_color' => 'emerald',
                        'description' => 'Adobe Illustrator or Encapsulated PostScript with fonts outlined and embedded images.',
                    ],
                    [
                        'name' => 'SVG (Vector)',
                        'badge' => 'Accepted',
                        'badge_color' => 'slate',
                        'description' => 'Scalable Vector Graphics for clean lines and solid colors.',
                    ],
                    [
                        'name' => 'PDF (Vector)',
                        'badge' => 'Accepted',
                        'badge_color' => 'slate',
                        'description' => 'High-resolution press-quality PDF with embedded fonts.',
                    ],
                    [
                        'name' => 'PNG (Raster)',
                        'badge' => '300 DPI min',
                        'badge_color' => 'amber',
                        'description' => 'Acceptable for full-colour complex designs. Must have transparent background and be at actual print size.',
                    ],
                ],
                'notice' => 'We cannot accept low-resolution raster formats (JPEG, GIF, BMP, web-optimized PNG) for custom teamwear decoration as they produce blurry, pixelated results.',
            ],
            'section_02' => [
                'number' => '02',
                'title' => 'Artwork preparation checklist',
                'checklist' => [
                    'Convert all fonts and text elements to outlines/curves',
                    'Use CMYK colour space for all full-colour printing',
                    'Specify Pantone (PMS) Solid Coated colour references where possible',
                    'Ensure raster elements are at least 300 DPI at actual printed size',
                    'Include 3 mm bleed on full-coverage sublimation panels',
                    'Provide artwork on a transparent background unless background color is intentional',
                ],
                'callout' => 'PPI vs DPI: For digital files, aim for 300 PPI (pixels per inch) at the final reproduction size. Enlarging a smaller file will degrade print quality.',
            ],
            'section_03' => [
                'number' => '03',
                'title' => 'Confirm size & placement',
                'subtitle' => 'State the artwork dimensions and where each design should appear.',
                'front_spec' => 'Front logo: 90 mm wide',
                'back_spec' => 'Back number: 250 mm high',
                'footnote' => 'Placement example only — specifications depend on the product.',
            ],
            'section_04' => [
                'number' => '04',
                'title' => 'Decoration methods & file requirements',
                'methods' => [
                    [
                        'name' => 'Sublimation',
                        'ideal_for' => 'All-over print, complex patterns, gradients, photo-real prints',
                        'file_types' => 'Vector (AI, EPS, SVG) or 300 DPI CMYK raster',
                        'colours' => 'Full CMYK + Pantone match',
                    ],
                    [
                        'name' => 'Embroidery',
                        'ideal_for' => 'Badges, logos, chest crests, caps, outer jackets',
                        'file_types' => 'Vector preferred; clean 300 DPI image',
                        'colours' => 'Up to 15 thread colours per logo',
                    ],
                    [
                        'name' => 'Screen printing',
                        'ideal_for' => 'Single/spot colour logos, large volume runs',
                        'file_types' => 'Vector only (AI, EPS, SVG) with outlined text',
                        'colours' => '1–6 spot colours (Pantone match)',
                    ],
                    [
                        'name' => 'Heat transfer',
                        'ideal_for' => 'Names, individual numbers, smaller sponsor logos',
                        'file_types' => 'Vector (AI, EPS, SVG) with clean cut paths',
                        'colours' => 'Standard vinyl colours or printed vinyl',
                    ],
                ],
            ],
            'section_05' => [
                'number' => '05',
                'title' => 'Proof review & approval',
                'intro' => 'Before production begins, our design team will prepare a digital proof (mockup) showing:',
                'items' => [
                    'Artwork placement and approximate dimensions',
                    'Colours and colour references',
                    'Spelling of names, numbers, and sponsor details',
                    'Decoration method specified',
                    'Product garment style and seam alignment',
                ],
                'notice' => 'Production will only begin after you have given written approval of the final proof. Check all details carefully — changes after approval may incur additional costs.',
            ],
            'section_06' => [
                'number' => '06',
                'title' => 'Sending your files',
                'intro' => 'Submit files during the order process, or email them to our design team with your order number in the subject line.',
                'filename_label' => 'File naming convention:',
                'filename_example' => 'TeamName_Product_Front_v1.pdf',
                'copyright_notice' => 'Please ensure you hold the rights or permission to use any logos, crests, or copyrighted imagery submitted.',
                'cta_text' => 'Need help with your artwork? Contact our design team',
                'cta_url' => '/contact-us',
            ],
            'seo' => [
                'meta_title' => 'Artwork Guidelines | NextPlay Sportswear',
                'meta_description' => 'Learn how to prepare vector and raster files for custom sportswear sublimation, embroidery, screen printing and heat transfer.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $record = StorefrontPageSetting::where('page_key', self::PAGE_KEY)->first();
            $defaults = $this->defaults();

            if (! $record) {
                return $defaults;
            }

            $content = is_array($record->content) ? $record->content : [];
            $seo = is_array($record->seo) ? $record->seo : [];

            $merged = array_replace_recursive($defaults, $content);
            if (! empty($seo)) {
                $merged['seo'] = array_merge($defaults['seo'], $seo);
            }

            return $merged;
        });
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function update(array $payload, ?int $adminId = null): StorefrontPageSetting
    {
        $setting = StorefrontPageSetting::firstOrNew(['page_key' => self::PAGE_KEY]);

        $seo = $payload['seo'] ?? [];
        unset($payload['seo']);

        $setting->title = $payload['hero']['title'] ?? 'Artwork Guidelines';
        $setting->content = $payload;
        $setting->seo = $seo;

        if (! $setting->exists && $adminId) {
            $setting->created_by = $adminId;
        }
        if ($adminId) {
            $setting->updated_by = $adminId;
        }

        $setting->save();

        Cache::forget(self::CACHE_KEY);

        return $setting;
    }
}


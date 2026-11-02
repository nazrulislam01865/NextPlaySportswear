<?php

namespace App\Services\Storefront;

use App\Models\StorefrontPageSetting;
use Illuminate\Support\Facades\Cache;

class SizeGuidePageService
{
    public const PAGE_KEY = 'size-guide';
    public const CACHE_KEY = 'storefront.page.size-guide';

    public function defaults(): array
    {
        return [
            'hero' => [
                'title' => 'Size Guide',
                'description' => 'Measure first, then use the Size Charts to choose the right size for your team. Product-specific size charts always take priority.',
            ],
            'sections' => [
                ['title' => 'Before measuring', 'description' => 'Small measurement mistakes can affect the fit of a whole team order.', 'items' => [
                    'Use a soft measuring tape. Keep the tape flat without pulling it too tight.',
                    'Measure over light clothing rather than bulky layers or equipment.',
                    'Record actual measurements. Do not guess based on age or another brand.',
                    'Use the size chart on the selected product whenever one is available.',
                ]],
                ['title' => 'How to measure', 'description' => 'Use the same method for the entire roster.', 'items' => [
                    'Chest — Measure around the fullest part of the chest, under the arms and across the shoulder blades.',
                    'Waist — Measure around your natural waist without holding your breath.',
                    'Hips — With feet together, measure around the fullest part of the hips and seat.',
                    'Inseam — Measure from the crotch seam down the inside leg to the desired length.',
                    'Height — Stand straight without shoes; measure from the floor to the top of the head.',
                    'Sleeve — Measure from the center back of the neck, over the shoulder and down to the wrist.',
                ]],
            ],
            'charts' => [
                ['title' => 'Adult tops', 'description' => 'General adult body measurements (inches).', 'rows' => [
                    ['XS','32–34 in','26–28 in','32–34 in','5\'2"–5\'6"'],
                    ['S','35–37 in','29–31 in','35–37 in','5\'4"–5\'8"'],
                    ['M','38–40 in','32–34 in','38–40 in','5\'6"–5\'10"'],
                    ['L','41–43 in','35–37 in','41–43 in','5\'8"–6\'0"'],
                    ['XL','44–46 in','38–40 in','44–46 in','5\'9"–6\'2"'],
                    ['2XL','47–50 in','41–44 in','47–50 in','5\'10"–6\'4"'],
                    ['3XL','51–54 in','45–48 in','51–54 in','5\'10"–6\'5"'],
                ]],
                ['title' => 'Youth tops', 'description' => 'General youth body measurements (inches).', 'rows' => [
                    ['YXS','24–26 in','22–23 in','25–27 in','3\'10"–4\'2"'],
                    ['YS','26–28 in','23–24 in','27–29 in','4\'1"–4\'5"'],
                    ['YM','28–30 in','24–25 in','29–31 in','4\'4"–4\'8"'],
                    ['YL','30–32 in','25–27 in','31–33 in','4\'7"–5\'0"'],
                    ['YXL','32–34 in','27–29 in','33–35 in','4\'11"–5\'4"'],
                ]],
                ['title' => 'Bottoms', 'description' => 'General measurements; last column is the inseam reference.', 'rows' => [
                    ['XS','—','26–28 in','32–34 in','27–29 in inseam'],
                    ['S','—','29–31 in','35–37 in','28–30 in inseam'],
                    ['M','—','32–34 in','38–40 in','29–31 in inseam'],
                    ['L','—','35–37 in','41–43 in','30–32 in inseam'],
                    ['XL','—','38–40 in','44–46 in','31–33 in inseam'],
                    ['2XL','—','41–44 in','47–50 in','31–34 in inseam'],
                ]],
            ],
            'fit' => [
                'title' => 'Fit and team orders',
                'description' => 'Measurements are body measurements, not finished-garment measurements. Product cut, fabric stretch, protective equipment and intended fit may change the recommended size.',
                'tips' => [
                    'Between two sizes? Consider the intended fit, layering, fabric and any protective equipment.',
                    'Team roster tip: have each player confirm their own size against the selected product chart.',
                ],
                'cta_text' => 'Contact Support',
            ],
            'seo' => [
                'meta_title' => 'Size Guide | NextPlay Sportswear',
                'meta_description' => 'Measure chest, waist, hips, inseam and height and compare general adult, youth and bottoms size charts.',
            ],
        ];
    }

    public function settings(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            $defaults = $this->defaults();
            $setting = StorefrontPageSetting::where('page_key', self::PAGE_KEY)->first();
            if (! $setting) {
                return $defaults;
            }
            $result = array_replace_recursive($defaults, is_array($setting->content) ? $setting->content : []);
            $result['seo'] = array_merge($defaults['seo'], is_array($setting->seo) ? $setting->seo : []);
            return $result;
        });
    }

    public function update(array $data, ?int $adminId = null): StorefrontPageSetting
    {
        $setting = StorefrontPageSetting::firstOrNew(['page_key' => self::PAGE_KEY]);
        $seo = $data['seo'] ?? [];
        unset($data['seo']);
        $setting->title = $data['hero']['title'];
        $setting->content = $data;
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

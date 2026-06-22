<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->products() as $index => $data) {
                $category = Category::query()->where('slug', $data['category_slug'])->first();
                $subcategory = Category::query()->where('slug', $data['subcategory_slug'] ?? '')->first();
                if ($subcategory?->id === $category?->id) {
                    $subcategory = null;
                }

                // Never overwrite administrator-managed products during a later
                // db:seed run. Defaults are inserted only when the SKU is absent.
                $product = Product::query()->firstOrCreate(
                    ['sku' => $data['sku']],
                    [
                        'category_id' => $category?->parent_id ? $category->parent_id : $category?->id,
                        'subcategory_id' => $subcategory?->id,
                        'name' => $data['name'],
                        'slug' => $data['slug'],
                        'status' => 'active',
                        'product_type' => $data['product_type'],
                        'brand' => 'NextPlay Sportswear',
                        'badge_label' => $index === 0 ? 'Fully Customizable' : 'Customizable',
                        'badge_color' => 'red',
                        'short_description' => $data['summary'],
                        'description_html' => '<h2>Built for custom team orders</h2><p>'.$data['description'].'</p><h3>Flexible administration</h3><p>Product content, categories, pricing, images, sizes, customization fields, artwork methods, production speeds, and SEO are managed from the NextPlay admin panel.</p>',
                        'features' => $data['features'],
                        'specifications' => $data['specifications'],
                        'base_price' => $data['price'],
                        'currency' => 'USD',
                        'minimum_quantity' => $data['minimum_quantity'],
                        'is_featured' => $index < 4,
                        'is_customizable' => true,
                        'is_active' => true,
                        'track_inventory' => false,
                        'stock_quantity' => 0,
                        'low_stock_threshold' => 5,
                        'allow_backorder' => true,
                        'tags' => $data['tags'],
                        'price_table_headers' => ['Quantity', 'Blank / No Print', 'Full Customization', 'Estimated Savings'],
                        'price_table_rows' => [
                            ['1–11', '$'.number_format($data['price'] - 3, 2), '$'.number_format($data['price'], 2), '—'],
                            ['12–23', '$'.number_format($data['price'] * .84, 2), '$'.number_format($data['price'] * .90, 2), 'Save up to 10%'],
                            ['24–49', '$'.number_format($data['price'] * .75, 2), '$'.number_format($data['price'] * .81, 2), 'Save up to 19%'],
                            ['50+', '$'.number_format($data['price'] * .66, 2), '$'.number_format($data['price'] * .72, 2), 'Save up to 28%'],
                        ],
                        'price_table_highlight_column' => 2,
                        'price_table_note' => 'Pricing is an estimate. Final pricing is recalculated after product options, artwork complexity, production speed, tax, and shipping are validated.',
                        'meta_title' => $data['name'].' | NextPlay Sportswear',
                        'meta_description' => $data['summary'],
                        'meta_keywords' => implode(', ', $data['tags']),
                        'og_title' => $data['name'],
                        'og_description' => $data['summary'],
                        'og_image_url' => $data['image'],
                        'robots_index' => true,
                        'robots_follow' => true,
                        'sort_order' => ($index + 1) * 10,
                        'published_at' => now(),
                    ]
                );

                if ($product->images()->doesntExist()) {
                    foreach ([$data['image'], $data['image_secondary'] ?? $data['image']] as $imageIndex => $url) {
                        $product->images()->create([
                            'url' => $url,
                            'alt_text' => $data['name'].($imageIndex ? ' alternate view' : ''),
                            'is_primary' => $imageIndex === 0,
                            'sort_order' => $imageIndex,
                        ]);
                    }
                }

                if ($product->priceTiers()->doesntExist()) $this->seedPriceTiers($product, $data['price']);
                if ($product->sizeGroups()->doesntExist()) $this->seedSizes($product);
                if ($product->artworkMethods()->doesntExist()) $this->seedArtwork($product);
                if ($product->productionSpeeds()->doesntExist()) $this->seedProduction($product);
                if ($product->faqs()->doesntExist()) $this->seedFaqs($product);
                if ($product->optionGroups()->doesntExist()) $this->seedOptions($product, $index === 0);
            }
        });
    }

    private function seedPriceTiers(Product $product, float $price): void
    {
        $product->priceTiers()->delete();
        foreach ([
            ['label' => '1–11', 'minimum_quantity' => 1, 'maximum_quantity' => 11, 'unit_price' => $price, 'savings_label' => null],
            ['label' => '12–23', 'minimum_quantity' => 12, 'maximum_quantity' => 23, 'unit_price' => round($price * .90, 2), 'savings_label' => 'Save 10%'],
            ['label' => '24–49', 'minimum_quantity' => 24, 'maximum_quantity' => 49, 'unit_price' => round($price * .81, 2), 'savings_label' => 'Save 19%'],
            ['label' => '50+', 'minimum_quantity' => 50, 'maximum_quantity' => null, 'unit_price' => round($price * .72, 2), 'savings_label' => 'Save 28%'],
        ] as $index => $tier) {
            $product->priceTiers()->create([...$tier, 'sort_order' => $index]);
        }
    }

    private function seedSizes(Product $product): void
    {
        $product->sizeGroups()->delete();
        foreach ([
            'Adult Unisex' => ['XS', 'S', 'M', 'L', 'XL', '2XL', '3XL', '4XL'],
            'Women' => ['WXS', 'WS', 'WM', 'WL', 'WXL', 'W2XL'],
            'Youth' => ['YXS', 'YS', 'YM', 'YL', 'YXL'],
        ] as $groupIndex => $sizes) {
            $name = is_string($groupIndex) ? $groupIndex : 'Sizes';
            $group = $product->sizeGroups()->create(['name' => $name, 'code' => Str::slug($name), 'is_active' => true, 'sort_order' => $product->sizeGroups()->count()]);
            foreach ($sizes as $sizeIndex => $size) {
                $group->sizes()->create(['label' => $size, 'code' => Str::slug($size), 'price_adjustment' => str_contains($size, '3XL') || str_contains($size, '4XL') ? 2 : 0, 'is_active' => true, 'sort_order' => $sizeIndex]);
            }
        }
    }

    private function seedArtwork(Product $product): void
    {
        $product->artworkMethods()->delete();
        foreach ([
            ['Upload Design', 'upload-design', '⇧', 'Upload logo or finished artwork now.', 0, true],
            ['Design Later', 'design-later', '◷', 'Send files after checkout.', 0, false],
            ['Free Design Help', 'design-help', '✦', 'Ask our art team to prepare a proof.', 0, false],
            ['Blank Product', 'blank-product', '□', 'No logo, print, or personalization.', -3, false],
        ] as $index => [$name, $code, $icon, $description, $price, $requiresUpload]) {
            $product->artworkMethods()->create(['name' => $name, 'code' => $code, 'icon' => $icon, 'description' => $description, 'price_adjustment' => $price, 'requires_upload' => $requiresUpload, 'is_active' => true, 'sort_order' => $index]);
        }
    }

    private function seedProduction(Product $product): void
    {
        $product->productionSpeeds()->delete();
        foreach ([
            ['Standard Production', 'standard', 'Standard production schedule.', 0, 14, 18],
            ['Priority Production', 'priority', 'Faster production for approved artwork.', 3.5, 10, 13],
            ['Rush Production', 'rush', 'Rush service where capacity allows.', 7.5, 7, 9],
        ] as $index => [$name, $code, $description, $price, $min, $max]) {
            $product->productionSpeeds()->create(['name' => $name, 'code' => $code, 'description' => $description, 'price_adjustment' => $price, 'minimum_days' => $min, 'maximum_days' => $max, 'is_active' => true, 'sort_order' => $index]);
        }
    }

    private function seedFaqs(Product $product): void
    {
        $product->faqs()->delete();
        foreach ([
            ['Can this product have different customization options?', 'Yes. Administrators can add, remove, reorder, require, or disable product-specific option groups and values.'],
            ['Can the quantity pricing table be different?', 'Yes. Both live pricing tiers and the visible customer table are controlled independently per product.'],
            ['Will I receive a proof?', 'The available artwork and proof options are configured per product. When enabled, artwork is reviewed before production.'],
        ] as $index => [$question, $answer]) {
            $product->faqs()->create(['question' => $question, 'answer' => $answer, 'is_active' => true, 'sort_order' => $index]);
        }
    }

    private function seedOptions(Product $product, bool $full): void
    {
        $product->optionGroups()->delete();
        $groups = [
            ['Fabric', 'fabric', 'product', 'image', true, [
                ['Pro Mesh', 'pro-mesh', 'Breathable performance mesh.', '#15345d', 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=600&q=80', 0],
                ['Elite Interlock', 'elite-interlock', 'Smooth premium knit.', '#111827', 'https://images.unsplash.com/photo-1598032895397-b9472444bf93?auto=format&fit=crop&w=600&q=80', 4.5],
            ]],
            ['Primary Color', 'primary-color', 'product', 'swatch', true, [
                ['Navy', 'navy', null, '#15345d', null, 0], ['Royal Blue', 'royal-blue', null, '#1756a9', null, 0], ['Power Red', 'power-red', null, '#d81e35', null, 0], ['Black', 'black', null, '#17191c', null, 0],
            ]],
            ['Neck Style', 'neck-style', 'product', 'buttons', true, [
                ['Round Neck', 'round-neck', null, null, null, 0], ['V-Neck', 'v-neck', null, null, null, 1.5], ['Polo Collar', 'polo-collar', null, null, null, 4],
            ]],
            ['Print Locations', 'print-locations', 'decoration', 'checkbox', true, [
                ['Front Chest', 'front-chest', 'Team logo or main graphic.', null, null, 0], ['Back', 'back', 'Player name and number.', null, null, 1.25], ['Left Sleeve', 'left-sleeve', 'Sponsor or flag.', null, null, 1], ['Right Sleeve', 'right-sleeve', 'Secondary logo.', null, null, 1],
            ]],
            ['Customization Notes', 'customization-notes', 'decoration', 'textarea', false, []],
        ];

        if (! $full) {
            $groups = array_slice($groups, 1, 4);
        }

        foreach ($groups as $groupIndex => [$name, $code, $section, $type, $required, $values]) {
            $group = $product->optionGroups()->create([
                'name' => $name, 'code' => $code, 'section' => $section, 'type' => $type,
                'description' => $type === 'textarea' ? 'Share special instructions for the art team.' : 'Choose the option that applies to your order.',
                'placeholder' => $type === 'textarea' ? 'Team colors, sponsor positions, roster notes...' : null,
                'is_required' => $required, 'minimum_selections' => $type === 'checkbox' && $required ? 1 : null,
                'maximum_selections' => $type === 'checkbox' ? 4 : null, 'is_active' => true, 'sort_order' => $groupIndex,
            ]);
            foreach ($values as $valueIndex => [$label, $valueCode, $description, $color, $image, $price]) {
                $group->values()->create([
                    'label' => $label, 'code' => $valueCode, 'description' => $description, 'color_hex' => $color,
                    'image_url' => $image, 'price_adjustment' => $price, 'is_default' => $valueIndex === 0,
                    'is_active' => true, 'sort_order' => $valueIndex,
                ]);
            }
        }
    }

    private function products(): array
    {
        return [
            ['name' => 'Custom Pro Team Jersey', 'slug' => 'custom-pro-team-jersey', 'sku' => 'NPS-JER-PRO-001', 'category_slug' => 'custom-jerseys', 'subcategory_slug' => 'basketball-jerseys', 'product_type' => 'Performance Team Jersey', 'price' => 44, 'minimum_quantity' => 6, 'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=1000&q=85', 'image_secondary' => 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1000&q=85', 'summary' => 'A performance team jersey built around your colors, logo, player names, numbers, fabric, neckline, and decoration requirements.', 'description' => 'Create a professional jersey for leagues, schools, clubs, tournaments, fan programs, and corporate events.', 'features' => ['Free digital proof before production', 'Adult, women, and youth size groups', 'Names, numbers, logos, and sponsors', 'Bulk quantity pricing', 'Artwork upload now or later', 'Product-specific customization'], 'specifications' => ['Product Type' => 'Performance Team Jersey', 'Fabric' => 'Admin-selectable', 'Decoration' => 'Sublimation / print / embroidery', 'Minimum Order' => '6 pieces', 'Production' => '7–18 business days', 'Fit' => 'Adult, women and youth'], 'tags' => ['custom jersey','basketball jersey','team uniform']],
            ['name' => 'Custom Football Jersey with Name & Number', 'slug' => 'custom-football-jersey-name-number', 'sku' => 'NPS-FBL-JER-002', 'category_slug' => 'custom-jerseys', 'subcategory_slug' => 'football-jerseys', 'product_type' => 'Football Jersey', 'price' => 39, 'minimum_quantity' => 1, 'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Personalized football jersey with player name, number, team colors, and logo placement.', 'description' => 'A durable custom football jersey for teams, schools, clubs, events, and supporters.', 'features' => ['Player name and number', 'Adult and youth sizes', 'Team colors and logos', 'Digital proof support'], 'specifications' => ['Product Type' => 'Football Jersey', 'Fabric' => 'Pro-Wick Polyester', 'Decoration' => 'Full sublimation', 'Minimum Order' => '1 piece'], 'tags' => ['football jersey','team jersey']],
            ['name' => 'Baseball Uniform Set for Teams', 'slug' => 'baseball-uniform-set-for-teams', 'sku' => 'NPS-BSB-SET-003', 'category_slug' => 'team-uniforms', 'subcategory_slug' => 'baseball-uniforms', 'product_type' => 'Baseball Uniform Set', 'price' => 49, 'minimum_quantity' => 6, 'image' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Coordinated baseball jersey and uniform set for school, club, and league team orders.', 'description' => 'Team-ready baseball uniform set with roster sizing, design review, and bulk pricing.', 'features' => ['Jersey and pants options', 'Roster sizing support', 'Logo and number placement', 'Bulk team workflow'], 'specifications' => ['Product Type' => 'Baseball Uniform Set', 'Decoration' => 'Print or embroidery', 'Minimum Order' => '6 sets'], 'tags' => ['baseball uniform','team set']],
            ['name' => 'Sublimated Soccer Kit', 'slug' => 'sublimated-soccer-kit-for-teams', 'sku' => 'NPS-SOC-KIT-004', 'category_slug' => 'team-uniforms', 'subcategory_slug' => 'soccer-kits', 'product_type' => 'Soccer Kit', 'price' => 46, 'minimum_quantity' => 6, 'image' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Custom soccer jersey and shorts kit with crest, sponsor, names, and numbers.', 'description' => 'Complete club kit for schools, leagues, tournaments, and soccer programs.', 'features' => ['Jersey and shorts', 'Club crest and sponsor', 'Player roster details', 'Team quantity pricing'], 'specifications' => ['Product Type' => 'Soccer Kit', 'Fabric' => 'Dry-fit polyester', 'Decoration' => 'Sublimation'], 'tags' => ['soccer kit','club uniform']],
            ['name' => 'Custom Team Hoodie', 'slug' => 'custom-team-hoodie', 'sku' => 'NPS-HOD-005', 'category_slug' => 'hoodies-sweatshirts', 'product_type' => 'Team Hoodie', 'price' => 42, 'minimum_quantity' => 1, 'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Custom hoodie for team travel, staff, schools, events, and fan merchandise.', 'description' => 'Comfortable branded hoodie with print or embroidery customization.', 'features' => ['Team logos', 'Print or embroidery', 'Adult and youth sizing', 'Individual or bulk order'], 'specifications' => ['Product Type' => 'Hoodie', 'Fabric' => 'Cotton-poly fleece', 'Decoration' => 'Print / embroidery'], 'tags' => ['hoodie','team apparel']],
            ['name' => 'Custom Embroidered Cap', 'slug' => 'custom-embroidered-cap', 'sku' => 'NPS-CAP-006', 'category_slug' => 'caps-headwear', 'product_type' => 'Embroidered Cap', 'price' => 24, 'minimum_quantity' => 6, 'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Structured cap with front, side, or back embroidery options.', 'description' => 'Custom cap for teams, events, businesses, schools, and supporters.', 'features' => ['Logo embroidery', 'Multiple cap colors', 'Adjustable sizing', 'Bulk availability'], 'specifications' => ['Product Type' => 'Cap', 'Fabric' => 'Cotton twill', 'Decoration' => 'Embroidery'], 'tags' => ['cap','embroidered hat']],
            ['name' => 'Personalized Sports Duffel Bag', 'slug' => 'personalized-sports-duffel-bag', 'sku' => 'NPS-BAG-007', 'category_slug' => 'sports-bags', 'product_type' => 'Duffel Bag', 'price' => 38, 'minimum_quantity' => 1, 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Sports duffel bag with team logo, player name, number, and color customization.', 'description' => 'Durable team bag for travel, training, schools, and club programs.', 'features' => ['Logo and player name', 'Team color options', 'Multiple compartments', 'Individual or bulk order'], 'specifications' => ['Product Type' => 'Duffel Bag', 'Material' => 'Heavy-duty polyester', 'Decoration' => 'Print / embroidery'], 'tags' => ['sports bag','duffel bag']],
            ['name' => 'Custom Fan Jersey', 'slug' => 'custom-fan-jersey', 'sku' => 'NPS-FAN-008', 'category_slug' => 'custom-jerseys', 'product_type' => 'Fan Jersey', 'price' => 32, 'minimum_quantity' => 1, 'image' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=1000&q=85', 'summary' => 'Custom fan jersey for supporters, events, spirit wear, and promotional campaigns.', 'description' => 'Flexible fan apparel with names, numbers, colors, logos, and campaign artwork.', 'features' => ['Fan name and number', 'Event colors', 'Spirit wear ready', 'Small and bulk orders'], 'specifications' => ['Product Type' => 'Fan Jersey', 'Fabric' => 'Performance polyester', 'Decoration' => 'Sublimation'], 'tags' => ['fan jersey','spirit wear']],
        ];
    }
}

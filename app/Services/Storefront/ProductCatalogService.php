<?php

namespace App\Services\Storefront;

use Illuminate\Support\Str;

class ProductCatalogService
{
    public function all(): array
    {
        return collect($this->products())
            ->map(fn (array $product): array => $this->hydrateProduct($product))
            ->values()
            ->all();
    }

    public function search(?string $query = null): array
    {
        $products = collect($this->all());

        if (filled($query)) {
            $needle = Str::lower((string) $query);

            $products = $products->filter(function (array $product) use ($needle): bool {
                return Str::contains(Str::lower($product['title']), $needle)
                    || Str::contains(Str::lower($product['category']), $needle)
                    || Str::contains(Str::lower(implode(' ', $product['tags'])), $needle)
                    || Str::contains(Str::lower($product['sport']), $needle)
                    || Str::contains(Str::lower($product['sku']), $needle);
            });
        }

        return $products->values()->all();
    }

    public function findBySlug(string $slug): ?array
    {
        return collect($this->all())->firstWhere('slug', $slug);
    }

    public function relatedFor(array $product, int $limit = 4): array
    {
        return collect($this->all())
            ->reject(fn (array $item): bool => $item['slug'] === $product['slug'])
            ->sortByDesc(function (array $item) use ($product): int {
                $score = 0;

                if ($item['sport'] === $product['sport']) {
                    $score += 3;
                }

                if ($item['category'] === $product['category']) {
                    $score += 2;
                }

                if (! empty(array_intersect($item['tags'], $product['tags']))) {
                    $score += 1;
                }

                return $score;
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function featured(): array
    {
        return collect($this->all())->take(8)->values()->all();
    }

    private function hydrateProduct(array $product): array
    {
        $product['url'] = route('products.show', $product['slug']);
        $product['gallery'] = $product['gallery'] ?? [$product['image']];
        $product['customizable_options'] = $product['customizable_options'] ?? [$this->defaultDesignOption($product)];
        $product['size_quantity_groups'] = $product['size_quantity_groups'] ?? $this->defaultSizeQuantityGroups();
        $product['size_selector'] = $product['size_selector'] ?? $this->defaultSizeSelector($product);
        $product['size_chart'] = $product['size_chart'] ?? $this->defaultSizeChart($product);
        $product['price_tiers'] = $product['price_tiers'] ?? $this->defaultPriceTiers((float) ($product['base_price'] ?? 39));
        $product['detail_information'] = $product['detail_information'] ?? $this->defaultDetailInformation($product);
        $product['details'] = $product['details'] ?? $this->legacyDetails($product);
        $product['option_steps'] = $product['option_steps'] ?? $this->defaultOptionSteps();
        $product['faqs'] = $product['faqs'] ?? $this->defaultFaqs();

        return $product;
    }

    private function products(): array
    {
        return [
            [
                'slug' => 'custom-cool-shapes-adult-youth-unisex-football-jersey',
                'title' => 'Custom Cool Shapes Adult Youth Unisex Football Jersey',
                'short_title' => 'Cool Shapes Football Jersey',
                'summary' => 'A fully customizable football jersey for adult and youth teams with design proof support, names, numbers, logos, and team colors.',
                'description' => 'Build a football jersey from a design template, then customize colors, logo placement, player names, roster numbers, fit notes, and artwork instructions before production.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-AYU-001',
                'rating' => 5,
                'reviews_count' => 34,
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom football jersey with name and number',
                'gallery' => [
                    'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=900&q=80',
                    'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=900&q=80',
                ],
                'tags' => ['football jersey', 'cool shapes', 'custom football uniform', 'adult youth unisex', 'sublimation football jersey', 'team football uniform'],
                'features' => [
                    'Adult, women, youth, and toddler quantity entry',
                    'Name, number, logo, and roster options',
                    'Digital proof before production',
                    'Moisture-wicking polyester performance fabric',
                    'No minimum quantity for selected custom products',
                ],
                'detail_information' => [
                    'SKU' => 'NPS-FBL-JER-AYU-001',
                    'Product Type' => 'Football Jersey',
                    'Fabric' => '220gsm Pro-Wick Polyester Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'V-Neck / Football Collar',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Football',
            ],
            [
                'slug' => 'custom-football-jersey-name-number',
                'title' => 'Custom Football Jersey with Name & Number',
                'short_title' => 'Football Jersey',
                'summary' => 'Personalized football jersey with player name, number, team colors, and optional logo placement.',
                'description' => 'A clean football jersey product for teams, fans, school events, and player uniforms.',
                'price' => 'From $39',
                'base_price' => 39,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Football',
                'category' => 'Football Jerseys',
                'sku' => 'NPS-FBL-JER-NN-002',
                'rating' => 5,
                'reviews_count' => 28,
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom football jersey',
                'tags' => ['football jersey', 'custom name number', 'team jersey', 'sportswear'],
                'features' => ['Player name and number', 'Team color direction', 'Logo placement notes', 'Team roster support'],
                'brand' => 'Football',
            ],
            [
                'slug' => 'baseball-uniform-set-for-teams',
                'title' => 'Baseball Uniform Set for Teams',
                'short_title' => 'Baseball Uniform Set',
                'summary' => 'Coordinated baseball jersey and uniform set for school, club, and league team orders.',
                'description' => 'Team-ready baseball uniform set with quote-based sizing, roster, and design review.',
                'price' => 'Request Quote',
                'base_price' => 49,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Baseball',
                'category' => 'Baseball Uniforms',
                'sku' => 'NPS-BSB-UNI-SET-003',
                'rating' => 5,
                'reviews_count' => 21,
                'image' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Baseball uniform set',
                'tags' => ['baseball uniform', 'team set', 'jersey pants', 'club uniform'],
                'features' => ['Team roster sizing', 'Jersey and pants direction', 'Logo and number support', 'Bulk quote ready'],
                'brand' => 'Baseball',
            ],
            [
                'slug' => 'custom-basketball-jersey',
                'title' => 'Custom Basketball Jersey',
                'short_title' => 'Basketball Jersey',
                'summary' => 'Breathable basketball jersey with team color, name, number, and logo customization.',
                'description' => 'A lightweight basketball jersey suitable for clubs, tournaments, training, and fan gear.',
                'price' => 'From $34',
                'base_price' => 34,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Basketball',
                'category' => 'Basketball Jerseys',
                'sku' => 'NPS-BSK-JER-UR-001',
                'rating' => 5,
                'reviews_count' => 17,
                'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom basketball jersey',
                'tags' => ['basketball club uniform', 'birdseye mesh jersey', 'breathable basketball jersey', 'Custom Basketball Jersey', 'custom sportswear', 'custom team jersey', 'elite basketball jersey', 'round neck basketball jersey', 'sublimation basketball jersey', 'team basketball uniform', 'unisex basketball jersey', 'youth basketball jersey'],
                'features' => ['Sleeveless jersey style', 'Name and number support', 'Team color direction', 'Bulk team order ready'],
                'detail_information' => [
                    'SKU' => 'NPS-BSK-JER-UR-001',
                    'Product Type' => 'Basketball Jersey',
                    'Fabric' => '160gsm Birdseye Mesh',
                    'Collection Tier' => 'Elite',
                    'Neckline' => 'Round Neck',
                    'Customization' => 'Full Sublimation Printing',
                    'MOQ' => '1 Piece',
                    'Lead Time' => '18–22 business days + shipping',
                ],
                'brand' => 'Basketball',
                'size_chart' => [
                    'title' => 'Basketball Jersey Size Chart',
                    'note' => 'Measurements are garment guidance. Choose one size up for a looser game fit.',
                    'groups' => [
                        [
                            'label' => 'Adult Unisex',
                            'columns' => ['Size', 'Chest', 'Length', 'Shoulder'],
                            'rows' => [
                                ['XS', '32-34"', '27"', '15.5"'],
                                ['S', '34-37"', '28"', '16.5"'],
                                ['M', '37-40"', '29"', '17.5"'],
                                ['L', '40-43"', '30"', '18.5"'],
                                ['XL', '43-46"', '31"', '19.5"'],
                                ['2XL', '46-49"', '32"', '20.5"'],
                                ['3XL', '49-52"', '33"', '21.5"'],
                                ['4XL', '52-55"', '34"', '22.5"'],
                            ],
                        ],
                        [
                            'label' => 'Youth Unisex',
                            'columns' => ['Size', 'Age', 'Chest', 'Length'],
                            'rows' => [
                                ['YXS', '4-5', '23-24"', '20"'],
                                ['YS', '6-7', '25-26"', '21"'],
                                ['YM', '8-9', '27-28"', '23"'],
                                ['YL', '10-11', '29-31"', '25"'],
                                ['YXL', '12-14', '32-34"', '27"'],
                            ],
                        ],
                    ],
                    'tips' => ['For team orders, collect sizes before submitting the roster.', 'Custom sizing can be requested in the notes field.'],
                ],
            ],
            [
                'slug' => 'sublimated-soccer-kit-for-teams',
                'title' => 'Sublimated Soccer Kit',
                'short_title' => 'Soccer Kit',
                'summary' => 'Custom soccer kit for clubs, school teams, and fan groups with jersey and team details.',
                'description' => 'Soccer kit page with custom logo, player list, and quote-oriented production support.',
                'price' => 'Request Quote',
                'base_price' => 42,
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'sport' => 'Soccer',
                'category' => 'Soccer Kits',
                'sku' => 'NPS-SCR-KIT-TM-005',
                'rating' => 5,
                'reviews_count' => 24,
                'image' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Sublimated soccer kit',
                'tags' => ['soccer kit', 'uniform', 'club team', 'sublimated'],
                'features' => ['Club and school team ready', 'Logo and sponsor notes', 'Player roster support', 'Bulk sizing table support'],
                'brand' => 'Soccer',
            ],
            [
                'slug' => 'custom-team-hoodie',
                'title' => 'Custom Team Hoodie',
                'short_title' => 'Team Hoodie',
                'summary' => 'Team hoodie for travel, sideline, spirit wear, coaches, staff, and fan apparel.',
                'description' => 'Custom hoodie with logo, embroidery/print method, and bulk ordering support.',
                'price' => 'From $45',
                'base_price' => 45,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Training',
                'category' => 'Apparel',
                'sku' => 'NPS-HOD-APP-TM-006',
                'rating' => 5,
                'reviews_count' => 18,
                'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom team hoodie',
                'tags' => ['hoodie', 'apparel', 'team', 'spirit wear'],
                'features' => ['Team logo support', 'Front/back print notes', 'Bulk apparel pricing', 'Coach and fan gear ready'],
                'brand' => 'Team Apparel',
            ],
            [
                'slug' => 'custom-embroidered-cap',
                'title' => 'Custom Embroidered Cap',
                'short_title' => 'Embroidered Cap',
                'summary' => 'Custom cap with embroidered logo direction for teams, businesses, events, and fan shops.',
                'description' => 'Cap customization with logo file upload, embroidery position notes, and bulk pricing support.',
                'price' => 'From $18',
                'base_price' => 18,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Accessories',
                'category' => 'Caps & Hats',
                'sku' => 'NPS-CAP-EMB-007',
                'rating' => 5,
                'reviews_count' => 14,
                'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom embroidered cap',
                'tags' => ['cap', 'hat', 'embroidery', 'team logo'],
                'features' => ['Front logo placement', 'Embroidery quote support', 'Event and team order ready', 'Bulk available'],
                'brand' => 'Accessories',
            ],
            [
                'slug' => 'personalized-sports-duffel-bag',
                'title' => 'Personalized Sports Duffel Bag',
                'short_title' => 'Sports Duffel Bag',
                'summary' => 'Personalized sports bag for team travel, gym use, events, and promotional programs.',
                'description' => 'Duffel bag product page with logo, name, and bulk promotional order notes.',
                'price' => 'From $32',
                'base_price' => 32,
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'sport' => 'Accessories',
                'category' => 'Bags',
                'sku' => 'NPS-BAG-DUF-008',
                'rating' => 5,
                'reviews_count' => 12,
                'image' => 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Personalized sports duffel bag',
                'tags' => ['bag', 'duffel', 'team', 'travel'],
                'features' => ['Team logo direction', 'Player name notes', 'Travel-friendly product', 'Promotional order support'],
                'brand' => 'Bags',
            ],
            [
                'slug' => 'custom-fan-jersey',
                'title' => 'Custom Fan Jersey',
                'short_title' => 'Fan Jersey',
                'summary' => 'Personalized fan jersey for supporters, school events, tournaments, and spirit wear.',
                'description' => 'Custom fan jersey with name, number, color, and event-specific design support.',
                'price' => 'From $36',
                'base_price' => 36,
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'sport' => 'Fan Gear',
                'category' => 'Custom Jerseys',
                'sku' => 'NPS-FAN-JER-009',
                'rating' => 5,
                'reviews_count' => 16,
                'image' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=900&q=80',
                'alt' => 'Custom fan jersey',
                'tags' => ['fan jersey', 'customizable', 'spirit wear', 'event'],
                'features' => ['Fan name and number', 'Event color direction', 'Spirit wear ready', 'Small and bulk order support'],
                'brand' => 'Fan Gear',
            ],
        ];
    }

    private function defaultDesignOption(array $product = []): array
    {
        return [
            'id' => 'design-style',
            'step' => '01',
            'title' => 'Choose Design Style',
            'subtitle' => 'Pick a starting point',
            'description' => 'One admin-defined customizable option is shown now. The same reusable component can render more options later.',
            'type' => 'visual-radio',
            'required' => true,
            'help' => 'Final colors, logos, names, numbers, and artwork can be adjusted after proof review.',
            'values' => [
                [
                    'label' => 'Default Team Style',
                    'value' => 'default-team-style',
                    'price_delta' => '$0',
                    'badge' => 'Default',
                    'preview' => 'linear-gradient(135deg,#15345d 0%,#2467b7 50%,#e91d33 50%,#e91d33 100%)',
                    'description' => 'Clean team look using the product default template.',
                ],
                [
                    'label' => 'Modern Graphic',
                    'value' => 'modern-graphic',
                    'price_delta' => '+$3',
                    'badge' => 'Popular',
                    'preview' => 'radial-gradient(circle at 30% 30%,#e91d33,#2467b7 45%,#0d2545 100%)',
                    'description' => 'Bolder modern design with high-impact color movement.',
                ],
                [
                    'label' => 'Upload Custom Artwork',
                    'value' => 'upload-custom-artwork',
                    'price_delta' => 'Quote',
                    'badge' => 'Custom',
                    'preview' => 'linear-gradient(45deg,#f8fafc 25%,#e2e8f0 25%,#e2e8f0 50%,#f8fafc 50%,#f8fafc 75%,#e2e8f0 75%)',
                    'description' => 'Upload your own artwork or ask the design team to recreate it.',
                ],
            ],
        ];
    }

    private function defaultSizeSelector(array $product): array
    {
        return [
            'step' => '07',
            'title' => 'Choose Your Sizes',
            'note' => $product['sport'] === 'Football'
                ? 'We suggest going up one size if wearing on top of shoulder or elbow pads.'
                : 'Choose the best fit for each player. Add custom size notes if needed.',
            'base_price' => (float) ($product['base_price'] ?? 39),
        ];
    }

    private function defaultSizeQuantityGroups(): array
    {
        return [
            [
                'key' => 'men',
                'label' => 'Men',
                'sizes' => ['S', 'M', 'L', 'XL', '2XL', '3XL', '4XL', 'CUSTOM'],
            ],
            [
                'key' => 'women',
                'label' => 'Women',
                'sizes' => ['WS', 'WM', 'WL', 'WXL', 'W2XL', 'W3XL', 'W4XL', 'CUSTOM'],
            ],
            [
                'key' => 'youth',
                'label' => 'Youth',
                'sizes' => ['YXS', 'YS', 'YM', 'YL', 'YXL', 'CUSTOM'],
            ],
            [
                'key' => 'toddler',
                'label' => 'Toddler',
                'sizes' => ['2T', '3T', '4T', 'CUSTOM'],
            ],
        ];
    }

    private function defaultOptionSteps(): array
    {
        return [
            ['title' => 'Choose Apparel', 'description' => 'Select the jersey, uniform set, hoodie, cap, bag, or accessory variation.'],
            ['title' => 'Choose Design', 'description' => 'Pick a template, modern graphic, or custom artwork direction.'],
            ['title' => 'Upload Artwork', 'description' => 'Attach logo, roster, design sample, or old order reference.'],
            ['title' => 'Choose Sizes', 'description' => 'Enter size quantities for men, women, youth, toddler, or custom sizes.'],
            ['title' => 'Review Proof', 'description' => 'Approve spelling, artwork placement, colors, and final layout before production.'],
            ['title' => 'Confirm Order', 'description' => 'Production and delivery timeline is confirmed after proof approval.'],
        ];
    }

    private function defaultDetailInformation(array $product): array
    {
        $sport = $product['sport'] ?? 'Sportswear';
        $type = Str::contains(Str::lower($product['title'] ?? ''), 'hoodie') ? 'Team Hoodie' : ($sport . ' Product');

        if (Str::contains(Str::lower($product['title'] ?? ''), 'jersey')) {
            $type = $sport . ' Jersey';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'kit')) {
            $type = $sport . ' Kit';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'cap')) {
            $type = 'Embroidered Cap';
        } elseif (Str::contains(Str::lower($product['title'] ?? ''), 'bag')) {
            $type = 'Duffel Bag';
        }

        return [
            'SKU' => $product['sku'] ?? 'NPS-CUS-001',
            'Product Type' => $type,
            'Fabric' => $this->defaultFabricFor($product),
            'Collection Tier' => 'Elite',
            'Neckline' => Str::contains(Str::lower($type), 'hoodie') ? 'Hooded' : 'Sport-specific neckline',
            'Customization' => 'Full Sublimation / Print / Embroidery Quote',
            'MOQ' => '1 Piece',
            'Lead Time' => '18–22 business days + shipping',
        ];
    }

    private function legacyDetails(array $product): array
    {
        return [
            'Brand' => $product['brand'] ?? config('storefront.name'),
            'Category' => $product['category'] ?? 'Custom Sportswear',
            'SKU' => $product['sku'] ?? 'NPS-CUS-001',
            'Order Type' => 'Custom / bulk / quote-ready',
            'Proof' => 'Digital proof before production',
            'Artwork' => 'PNG, JPG, PDF, AI, or design notes',
            'Production' => 'Timeline confirmed after proof approval',
        ];
    }

    private function defaultFabricFor(array $product): string
    {
        $sport = Str::lower($product['sport'] ?? '');
        $title = Str::lower($product['title'] ?? '');

        return match (true) {
            Str::contains($sport, 'basketball') => '160gsm Birdseye Mesh',
            Str::contains($sport, 'football') => '220gsm Pro-Wick Polyester Mesh',
            Str::contains($sport, 'soccer') => '180gsm Dry-Fit Polyester',
            Str::contains($title, 'hoodie') => 'Fleece Cotton-Poly Blend',
            Str::contains($title, 'cap') => 'Structured Cotton Twill',
            Str::contains($title, 'bag') => 'Heavy-Duty Polyester',
            default => 'Performance Polyester',
        };
    }

    private function defaultPriceTiers(float $basePrice): array
    {
        $tiers = [
            ['qty' => '1-5', 'discount' => 0, 'shipping' => 8, 'note' => 'Small custom order'],
            ['qty' => '6-24', 'discount' => 0.08, 'shipping' => 6, 'note' => 'Small team order'],
            ['qty' => '25-99', 'discount' => 0.15, 'shipping' => 5, 'note' => 'Team pricing'],
            ['qty' => '100+', 'discount' => 0.22, 'shipping' => 4, 'note' => 'Bulk quote recommended'],
        ];

        return array_map(function (array $tier) use ($basePrice): array {
            $productPrice = round($basePrice * (1 - $tier['discount']), 2);

            return [
                'quantity' => $tier['qty'],
                'product_price' => '$' . number_format($productPrice, 2),
                'shipping' => '$' . number_format($tier['shipping'], 2),
                'estimated_each' => '$' . number_format($productPrice + $tier['shipping'], 2),
                'estimated_order_total' => $tier['qty'] === '100+'
                    ? 'Quote'
                    : '$' . number_format(($productPrice + $tier['shipping']) * (int) explode('-', $tier['qty'])[0], 2) . '+',
                'note' => $tier['note'],
            ];
        }, $tiers);
    }

    private function defaultSizeChart(array $product): array
    {
        return [
            'title' => ($product['sport'] ?? 'Product') . ' Size Chart',
            'note' => 'Measurements are garment guidance. Allow small production tolerance for custom apparel.',
            'groups' => [
                [
                    'label' => 'Adult Unisex',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['XS', '32-34"', '27"', '16.5"', '8.5"'],
                        ['S', '34-37"', '28"', '17.5"', '9"'],
                        ['M', '37-40"', '29"', '18.5"', '9.5"'],
                        ['L', '40-43"', '30"', '19.5"', '10"'],
                        ['XL', '43-46"', '31"', '20.5"', '10.5"'],
                        ['2XL', '46-49"', '32"', '21.5"', '11"'],
                        ['3XL', '49-52"', '33"', '22.5"', '11.5"'],
                        ['4XL', '52-55"', '34"', '23.5"', '12"'],
                    ],
                ],
                [
                    'label' => 'Women',
                    'columns' => ['Size', 'Chest', 'Length', 'Shoulder', 'Sleeve'],
                    'rows' => [
                        ['WS', '30-32"', '25"', '14.5"', '7.5"'],
                        ['WM', '32-35"', '26"', '15.5"', '8"'],
                        ['WL', '35-38"', '27"', '16.5"', '8.5"'],
                        ['WXL', '38-41"', '28"', '17.5"', '9"'],
                        ['W2XL', '41-44"', '29"', '18.5"', '9.5"'],
                        ['W3XL', '44-47"', '30"', '19.5"', '10"'],
                    ],
                ],
                [
                    'label' => 'Youth Unisex',
                    'columns' => ['Size', 'Age', 'Chest', 'Length', 'Height'],
                    'rows' => [
                        ['YXS', '4-5', '23-24"', '20"', '100-110 cm'],
                        ['YS', '6-7', '25-26"', '21"', '110-122 cm'],
                        ['YM', '8-9', '27-28"', '23"', '122-135 cm'],
                        ['YL', '10-11', '29-31"', '25"', '135-150 cm'],
                        ['YXL', '12-14', '32-34"', '27"', '150-163 cm'],
                    ],
                ],
                [
                    'label' => 'Toddler',
                    'columns' => ['Size', 'Age', 'Chest', 'Length'],
                    'rows' => [
                        ['2T', '2 years', '20-21"', '15"'],
                        ['3T', '3 years', '21-22"', '16"'],
                        ['4T', '4 years', '22-23"', '17"'],
                    ],
                ],
            ],
            'tips' => [
                'For a looser fit, choose one size up.',
                'For team orders, collect sizes before submitting the roster.',
                'Custom sizing can be requested in the order notes.',
            ],
        ];
    }

    private function defaultFaqs(): array
    {
        return [
            [
                'question' => 'Can the admin add more customizable options later?',
                'answer' => 'Yes. The product page loops through customizable option groups, so the admin can later add fabric, print method, collar, sleeve fit, belt, delivery, or accessories using the same reusable component.',
            ],
            [
                'question' => 'Can customers upload artwork?',
                'answer' => 'Yes. The product page includes an artwork upload field and order notes field. Backend upload processing can be connected when the cart/order module is ready.',
            ],
            [
                'question' => 'Will customers see a proof before production?',
                'answer' => 'The page is designed around proof review. Production should start only after the customer approves the final design proof.',
            ],
        ];
    }
}

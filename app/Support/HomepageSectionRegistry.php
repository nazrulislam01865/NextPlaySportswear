<?php

namespace App\Support;

use App\Models\HomepageSection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class HomepageSectionRegistry
{
    private const RETIRED_KEYS = [
        'customization_options',
        'support',
        'final_cta',
        'popular_categories',
        'use_cases',
    ];

    /** @return array<int, string> */
    public static function retiredKeys(): array
    {
        return self::RETIRED_KEYS;
    }

    public static function isRetired(string $key): bool
    {
        return in_array($key, self::RETIRED_KEYS, true);
    }

    /** @return array<string, array<string, mixed>> */
    public static function definitions(): array
    {
        $sections = [
            [
                'key' => 'slider',
                'name' => 'Homepage Slider',
                'component' => 'slider',
                'sort_order' => 10,
                'description' => 'Controls whether the top homepage slider is shown and where it appears. Slider images and text are managed from the slider items page.',
                'fields' => ['publishing'],
            ],
            [
                'key' => 'hero',
                'name' => 'Hero Banner',
                'component' => 'hero',
                'sort_order' => 20,
                'eyebrow' => 'Custom sportswear USA',
                'title' => 'Custom Sportswear for Teams, Schools, Events, and Fans',
                'description' => 'Design your own jerseys, uniforms, hoodies, caps, bags, and sports gear. Order online for regular items or request a custom quote for team and bulk orders.',
                'primary_label' => 'Start Your Order',
                'primary_url' => '#jersey',
                'secondary_label' => 'Request Bulk Quote',
                'secondary_url' => '#bulk',
                'image_path' => null,
                'image_url' => '/storage/storefront/home/hero.webp',
                'image_alt' => 'Custom jerseys, caps, hoodies, and sports bag arranged for a team order',
                'mobile_image_path' => null,
                'mobile_image_url' => null,
                'mobile_image_alt' => 'Custom sportswear mobile hero banner',
                'items' => [
                    ['title' => 'Custom names, numbers, logos, and colors'],
                    ['title' => 'Team uniforms for football, baseball, basketball, soccer, and more'],
                    ['title' => 'Bulk pricing available for schools, clubs, leagues, and businesses'],
                    ['title' => 'Design support before production'],
                ],
                'fields' => ['text', 'buttons', 'image', 'items'],
                'item_label' => 'Checklist Lines',
                'item_fields' => ['title'],
            ],
            [
                'key' => 'categories',
                'name' => 'Featured Categories',
                'component' => 'categories',
                'sort_order' => 30,
                'eyebrow' => 'Find it fast',
                'title' => 'What Are You Looking For?',
                'description' => 'Start with admin-selected categories, subcategories, or sub-subcategories and find the right product faster.',
                'fields' => ['text', 'items'],
                'item_label' => 'Homepage Categories',
                'item_fields' => ['category_id'],
            ],
            [
                'key' => 'buyer_paths',
                'name' => 'Buyer Paths',
                'component' => 'buyer_paths',
                'sort_order' => 40,
                'eyebrow' => 'Order by need',
                'title' => 'Shop by Who You’re Ordering For',
                'description' => 'Choose the path that fits your order.',
                'items' => [
                    ['icon' => '♜', 'title' => 'Teams & Leagues', 'description' => 'Uniforms and gear for full teams, clubs, and local leagues.', 'url' => '/bulk-quote', 'label' => 'Start Your Order'],
                    ['icon' => '★', 'title' => 'Schools & Colleges', 'description' => 'Custom jerseys, PE uniforms, event apparel, and spirit wear.', 'url' => '/bulk-quote', 'label' => 'Start Your Order'],
                    ['icon' => '▣', 'title' => 'Businesses & Events', 'description' => 'Branded apparel, caps, bags, and giveaway items.', 'url' => '/bulk-quote', 'label' => 'Request Bulk Quote'],
                    ['icon' => '✓', 'title' => 'Individual Buyers', 'description' => 'Shop selected products online and customize where available.', 'url' => '/products', 'label' => 'Shop Now'],
                ],
                'fields' => ['text', 'items'],
                'item_label' => 'Buyer Cards',
                'item_fields' => ['icon', 'title', 'description', 'url', 'label'],
            ],
            [
                'key' => 'design_jersey',
                'name' => 'Design Jersey',
                'component' => 'design_jersey',
                'sort_order' => 60,
                'eyebrow' => 'Design your own',
                'title' => 'Design Your Own Jersey',
                'description' => 'Add your team logo, player names, numbers, colors, and style preferences. Whether you need one custom jersey or a full team set, we make the process simple. Share your design idea, upload your logo, or tell us what style you want. Our team can help prepare the mockup before production.',
                'primary_label' => 'Start Your Order',
                'primary_url' => '#products',
                'secondary_label' => 'Request Bulk Quote',
                'secondary_url' => '#bulk',
                'image_url' => '/storage/storefront/home/hero.webp',
                'image_alt' => 'Custom jersey mockup with team colors',
                'items' => [
                    ['title' => 'Choose product style', 'description' => 'Select jersey type, sport, fabric direction, and quantity.'],
                    ['title' => 'Add logo, name, number, and colors', 'description' => 'Send the exact details you want on each jersey.'],
                    ['title' => 'Review design proof', 'description' => 'Artwork or mockup can be reviewed before custom production.'],
                    ['title' => 'Confirm order and production', 'description' => 'Approve the details, price, timeline, and shipping needs.'],
                ],
                'fields' => ['text', 'buttons', 'image', 'items'],
                'item_label' => 'Design Steps',
                'item_fields' => ['title', 'description'],
            ],
            [
                'key' => 'bulk_order',
                'name' => 'Bulk Order',
                'component' => 'bulk_order',
                'sort_order' => 70,
                'eyebrow' => 'Team, School, League & Event',
                'title' => 'Ordering for a team, school, league, or event?',
                'description' => 'Larger orders need a little more care. Share your quantity, sizes, artwork, delivery date, and shipping needs. Our team will review everything and send a clear bulk quote.',
                'primary_label' => 'Request Bulk Quote →',
                'primary_url' => '/bulk-quote',
                'secondary_label' => 'Explore Team Products',
                'secondary_url' => '/products',
                'items' => [
                    ['title' => 'Products & quantity', 'description' => 'Item type, estimated quantity, sizes, and player details.'],
                    ['title' => 'Artwork & branding', 'description' => 'Logo, colors, names, numbers, placement, and references.'],
                    ['title' => 'Delivery timing', 'description' => 'Needed-by date, event date, and production urgency.'],
                    ['title' => 'Shipping details', 'description' => 'Address, preferred shipping method, and special notes.'],
                ],
                'fields' => ['text', 'buttons', 'items'],
                'item_label' => 'Quote Checklist',
                'item_fields' => ['title', 'description'],
            ],
            [
                'key' => 'process',
                'name' => 'Ordering Process',
                'component' => 'process',
                'sort_order' => 80,
                'eyebrow' => 'How it works',
                'title' => 'Simple Ordering Process',
                'description' => 'A clear process from product selection to delivery.',
                'primary_label' => 'Start Your Order',
                'primary_url' => '#products',
                'items' => [
                    ['title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
                    ['title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
                    ['title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
                    ['title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
                    ['title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
                ],
                'fields' => ['text', 'buttons', 'items'],
                'item_label' => 'Process Steps',
                'item_fields' => ['title', 'description'],
            ],
            [
                'key' => 'featured_products',
                'name' => 'Featured Products',
                'component' => 'featured_products',
                'sort_order' => 90,
                'eyebrow' => 'Shop online',
                'title' => 'Featured Products',
                'description' => 'Products marked as featured by the admin appear here automatically.',
                'fields' => ['text'],
            ],
            [
                'key' => 'latest_products',
                'name' => 'Latest Products',
                'component' => 'latest_products',
                'sort_order' => 95,
                'eyebrow' => 'New arrivals',
                'title' => 'Latest',
                'description' => 'The latest created or updated active products appear here automatically.',
                'fields' => ['text'],
            ],
            [
                'key' => 'best_selling_products',
                'name' => 'Best Selling Products',
                'component' => 'best_selling_products',
                'sort_order' => 98,
                'eyebrow' => 'Customer favorites',
                'title' => 'Best Selling',
                'description' => 'Products are ranked automatically from paid order quantities. New products are used as a fallback until sales are recorded.',
                'fields' => ['text'],
            ],
            [
                'key' => 'best_selling_gear',
                'name' => 'Best-Selling Gear',
                'component' => 'best_selling_gear',
                'sort_order' => 100,
                'eyebrow' => 'POPULAR GEAR',
                'title' => 'BEST-SELLING TEAM GEAR',
                'description' => 'Built for teams. Designed to perform.',
                'fields' => ['text', 'items'],
                'item_label' => 'Best-Selling Gear Categories',
                'item_fields' => ['category_id', 'title', 'description', 'image_url', 'image_alt', 'url', 'label'],
            ],
            [
                'key' => 'shop_by_sport',
                'name' => 'Shop by Sport',
                'component' => 'shop_by_sport',
                'sort_order' => 15,
                'eyebrow' => 'Find your sport',
                'title' => 'Shop by Sport',
                'description' => 'Browse uniforms, apparel and gear by sport.',
                'fields' => ['text', 'items'],
                'item_label' => 'Sport Categories',
                'item_fields' => ['category_id'],
            ],
            [
                'key' => 'why_choose',
                'name' => 'Why Choose Us',
                'component' => 'why_choose',
                'sort_order' => 130,
                'eyebrow' => 'Why NextPlay',
                'title' => 'Why Teams Choose NextPlay Sportswear',
                'description' => 'Practical support, clear ordering, and sportswear made around your needs.',
                'items' => [
                    ['icon' => '✓', 'title' => 'Custom Orders Made Clear', 'description' => 'We help you understand what is needed before production starts.'],
                    ['icon' => '✓', 'title' => 'Support for Teams and Bulk Buyers', 'description' => 'Good for clubs, schools, businesses, leagues, and events.'],
                    ['icon' => '✓', 'title' => 'Design Review Before Production', 'description' => 'Your artwork or mockup can be checked before the order moves forward.'],
                    ['icon' => '✓', 'title' => 'Wide Product Range', 'description' => 'Jerseys, uniforms, hoodies, caps, bags, and promotional products in one place.'],
                    ['icon' => '✓', 'title' => 'Online and Bulk Ordering Options', 'description' => 'Order regular products online or contact us for team and bulk pricing.'],
                    ['icon' => '✓', 'title' => 'USA-Focused Shopping Experience', 'description' => 'Clear product pages, size details, order notes, and quote support.'],
                ],
                'fields' => ['text', 'items'],
                'item_label' => 'Reason Cards',
                'item_fields' => ['icon', 'title', 'description'],
            ],
            [
                'key' => 'testimonials',
                'name' => 'Testimonials',
                'component' => 'testimonials',
                'sort_order' => 160,
                'eyebrow' => 'Customer Words',
                'title' => 'What Teams and Customers Say',
                'description' => 'See how clubs, schools, businesses, and event teams use NextPlay for custom sportswear, team uniforms, event kits, and bulk orders.',
                'primary_label' => 'Read all testimonials',
                'primary_url' => '/testimonials',
                'secondary_label' => 'Share your experience',
                'secondary_url' => '/contact-us?topic=testimonial',
                'items' => [
                    ['icon' => 'JM', 'title' => 'Jason Miller', 'subtitle' => 'River Valley Baseball Club · Ohio, USA', 'description' => 'The jerseys came out clean and the ordering process was simple. We shared our team logo and size list, and the team helped us prepare the final order.', 'label' => 'Jerseys'],
                    ['icon' => 'EC', 'title' => 'Emily Carter', 'subtitle' => 'Community Run Event · Texas, USA', 'description' => 'We needed shirts and bags for a weekend event. The quote was clear, and they asked the right questions before moving ahead.', 'label' => 'Event kits'],
                    ['icon' => 'MR', 'title' => 'Marcus Reed', 'subtitle' => 'Northside High Boosters · Georgia, USA', 'description' => 'Good option for our school spirit wear. The hoodie colors matched what we requested, and the design proof helped a lot.', 'label' => 'Artwork'],
                    ['icon' => 'OG', 'title' => 'Olivia Grant', 'subtitle' => 'Grant Family Dental · Arizona, USA', 'description' => 'Ordering caps for our business team was easy. We had a few logo questions, and they helped us clean that up before production.', 'label' => 'Caps'],
                    ['icon' => 'DR', 'title' => 'Daniel Ruiz', 'subtitle' => 'South Bay FC · California, USA', 'description' => 'The soccer kits looked sharp. Not overcomplicated. We sent names, numbers, and sizes, then reviewed the mockup.', 'label' => 'Football kits'],
                    ['icon' => 'LB', 'title' => 'Lauren Brooks', 'subtitle' => 'Lakeview Youth League · Florida, USA', 'description' => 'We used them for a league order. The bulk quote made more sense than ordering every piece one by one.', 'label' => 'Bulk order'],
                ],
                'fields' => ['text', 'buttons', 'items'],
                'item_label' => 'Testimonials',
                'item_fields' => ['icon', 'title', 'subtitle', 'description', 'label'],
            ],
            [
                'key' => 'faq',
                'name' => 'FAQ',
                'component' => 'faq',
                'sort_order' => 170,
                'eyebrow' => 'Help center',
                'title' => 'Common Questions',
                'items' => [
                    ['title' => 'Can I order one custom jersey?', 'description' => 'Yes, selected products can be ordered directly online. Some custom products may have a minimum order quantity.'],
                    ['title' => 'Do you offer bulk pricing?', 'description' => 'Yes. For larger orders, especially 500+ or 1,000+ pieces, please contact us for a custom quotation.'],
                    ['title' => 'Can I add player names and numbers?', 'description' => 'Yes. For jerseys and team uniforms, you can usually add names, numbers, logos, and team colors.'],
                    ['title' => 'Do you help with artwork or mockups?', 'description' => 'Yes. You can send your logo or design idea. A proof or mockup may be reviewed before production.'],
                    ['title' => 'How long does production take?', 'description' => 'Production time depends on the product, customization method, quantity, and order season. The timeline should be confirmed before production.'],
                    ['title' => 'Do you ship across the USA?', 'description' => 'Yes, shipping options should be shown or confirmed based on the order and delivery location.'],
                ],
                'fields' => ['text', 'items'],
                'item_label' => 'Questions',
                'item_fields' => ['title', 'description'],
            ],
        ];

        return collect($sections)->keyBy('key')->all();
    }

    /** @return array<int, array<string, mixed>> */
    public static function orderedDefinitions(): array
    {
        return collect(self::definitions())
            ->sortBy(fn (array $section): int => (int) ($section['sort_order'] ?? 0))
            ->values()
            ->all();
    }

    public static function definition(string $key): ?array
    {
        return self::definitions()[$key] ?? null;
    }

    public static function ensureRows(?int $userId = null): void
    {
        if (! Schema::hasTable('homepage_sections')) {
            return;
        }

        HomepageSection::query()
            ->whereIn('key', self::RETIRED_KEYS)
            ->delete();

        foreach (self::orderedDefinitions() as $definition) {
            HomepageSection::query()->firstOrCreate(
                ['key' => $definition['key']],
                self::payloadForStorage($definition, $userId)
            );
        }
    }

    /** @return array<string, mixed> */
    public static function payloadForStorage(array $definition, ?int $userId = null): array
    {
        return [
            'name' => (string) $definition['name'],
            'eyebrow' => self::nullableString($definition['eyebrow'] ?? null),
            'title' => self::nullableString($definition['title'] ?? null),
            'description' => self::nullableString($definition['description'] ?? null),
            'primary_label' => self::nullableString($definition['primary_label'] ?? null),
            'primary_url' => self::nullableString($definition['primary_url'] ?? null),
            'secondary_label' => self::nullableString($definition['secondary_label'] ?? null),
            'secondary_url' => self::nullableString($definition['secondary_url'] ?? null),
            'image_path' => self::nullableString($definition['image_path'] ?? null),
            'image_url' => self::nullableString($definition['image_url'] ?? null),
            'image_alt' => self::nullableString($definition['image_alt'] ?? null),
            'mobile_image_path' => self::nullableString($definition['mobile_image_path'] ?? null),
            'mobile_image_url' => self::nullableString($definition['mobile_image_url'] ?? null),
            'mobile_image_alt' => self::nullableString($definition['mobile_image_alt'] ?? null),
            'items' => $definition['items'] ?? null,
            'is_active' => true,
            'sort_order' => (int) ($definition['sort_order'] ?? 0),
            'created_by' => $userId,
            'updated_by' => $userId,
        ];
    }

    /** @return array<string, mixed> */
    public static function mergeForView(string $key, ?HomepageSection $section = null): array
    {
        $definition = self::definition($key) ?? [];
        $values = $section ? $section->toArray() : [];
        $merged = array_merge($definition, array_filter($values, static fn ($value): bool => $value !== null));

        // Not every homepage section needs headings, buttons, or media. Keep a
        // predictable view payload so overview/edit screens can safely render
        // publishing-only sections without undefined array-key exceptions.
        foreach ([
            'eyebrow',
            'title',
            'description',
            'primary_label',
            'primary_url',
            'secondary_label',
            'secondary_url',
            'image_path',
            'image_url',
            'image_alt',
            'mobile_image_path',
            'mobile_image_url',
            'mobile_image_alt',
        ] as $nullableKey) {
            $merged[$nullableKey] = $merged[$nullableKey] ?? null;
        }

        $merged['key'] = $key;
        $merged['name'] = (string) ($merged['name'] ?? Str::of($key)->replace('_', ' ')->title());
        $merged['component'] = (string) ($definition['component'] ?? $key);
        $merged['fields'] = $definition['fields'] ?? ['text'];
        $merged['item_fields'] = $definition['item_fields'] ?? ['title', 'description'];
        $merged['item_label'] = $definition['item_label'] ?? 'Items';
        $merged['items'] = is_array($merged['items'] ?? null) ? array_values($merged['items']) : [];
        $merged['is_active'] = (bool) ($merged['is_active'] ?? true);
        $merged['sort_order'] = (int) ($merged['sort_order'] ?? ($definition['sort_order'] ?? 0));
        $merged['image'] = PublicMedia::url($merged['image_path'] ?? null, $merged['image_url'] ?? null, null);
        $merged['mobile_image'] = PublicMedia::url($merged['mobile_image_path'] ?? null, $merged['mobile_image_url'] ?? null, null);
        $merged['mobile_image_alt'] = self::nullableString($merged['mobile_image_alt'] ?? null) ?: ($merged['image_alt'] ?? null);

        return $merged;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

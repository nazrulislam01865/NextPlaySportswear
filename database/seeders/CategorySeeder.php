<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\CategoryTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tags = [
                ['name' => 'Team Uniforms', 'slug' => 'uniforms', 'sort_order' => 10],
                ['name' => 'Custom Jerseys', 'slug' => 'jerseys', 'sort_order' => 20],
                ['name' => 'Apparel', 'slug' => 'apparel', 'sort_order' => 30],
                ['name' => 'Headwear', 'slug' => 'headwear', 'sort_order' => 40],
                ['name' => 'Bags', 'slug' => 'bags', 'sort_order' => 50],
                ['name' => 'Fan Gear', 'slug' => 'fan', 'sort_order' => 60],
                ['name' => 'Promotional Products', 'slug' => 'promo', 'sort_order' => 70],
                ['name' => 'Accessories', 'slug' => 'accessories', 'sort_order' => 80],
            ];

            foreach ($tags as $tag) {
                CategoryTag::query()->updateOrCreate(
                    ['slug' => $tag['slug']],
                    [...$tag, 'is_active' => true],
                );
            }

            foreach ($this->categories() as $data) {
                $tagSlugs = $data['tags'] ?? [];
                unset($data['tags']);

                // Demo catalog seeding is non-destructive. Existing categories may
                // contain administrator-authored content, media, SEO, and hierarchy.
                $category = Category::query()->firstOrCreate(
                    ['slug' => $data['slug']],
                    [...$data, 'is_active' => true],
                );

                $tagIds = CategoryTag::query()
                    ->whereIn('slug', $tagSlugs)
                    ->pluck('id')
                    ->all();

                $category->tags()->sync($tagIds);
            }

            $hierarchy = [
                'football-jerseys' => 'custom-jerseys',
                'basketball-jerseys' => 'custom-jerseys',
                'baseball-uniforms' => 'team-uniforms',
                'soccer-kits' => 'team-uniforms',
            ];

            foreach ($hierarchy as $childSlug => $parentSlug) {
                $parentId = Category::query()->where('slug', $parentSlug)->value('id');
                Category::query()->where('slug', $childSlug)->whereNull('parent_id')->update(['parent_id' => $parentId]);
            }
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function categories(): array
    {
        return [
            [
                'name' => 'Custom Team Uniforms',
                'slug' => 'team-uniforms',
                'display_type' => 'collection',
                'eyebrow' => 'Complete team programs',
                'short_title' => 'Team Uniforms',
                'description' => 'Complete uniform sets for teams, clubs, schools, leagues, and sports events. Choose matching jerseys, shorts, pants, and team colors.',
                'best_for' => 'Football teams, baseball teams, basketball teams, soccer clubs, schools, and local leagues.',
                'image_url' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom team uniforms for schools and clubs',
                'cta_label' => 'View Team Uniforms',
                'meta_title' => 'Custom Team Uniforms',
                'meta_description' => 'Shop complete custom team uniform sets for schools, clubs, leagues, and sporting events.',
                'match_rules' => ['categories' => ['Baseball Uniforms', 'Basketball Jerseys', 'Soccer Kits', 'Football Jerseys']],
                'highlights' => ['Full uniform sets', 'Team colors and logos', 'Roster-friendly ordering'],
                'sort_order' => 10,
                'tags' => ['uniforms', 'apparel'],
            ],
            [
                'name' => 'Custom Jerseys',
                'slug' => 'custom-jerseys',
                'display_type' => 'collection',
                'eyebrow' => 'Personalized for every player',
                'short_title' => 'Custom Jerseys',
                'description' => 'Personalized jerseys with team names, player names, numbers, logos, and colors. Good for players, fans, events, and full team orders.',
                'best_for' => 'Team jerseys, fan jerseys, tournament jerseys, and event jerseys.',
                'image_url' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Personalized jerseys with names and numbers',
                'cta_label' => 'Shop Custom Jerseys',
                'meta_title' => 'Custom Sports Jerseys',
                'meta_description' => 'Shop personalized sports jerseys with names, numbers, logos, colors, and team artwork.',
                'match_rules' => ['categories' => ['Custom Jerseys', 'Football Jerseys', 'Basketball Jerseys'], 'tag_terms' => ['jersey']],
                'highlights' => ['Player names and numbers', 'Team logos and colors', 'Adult and youth sizing'],
                'sort_order' => 20,
                'tags' => ['jerseys', 'fan', 'uniforms'],
            ],
            [
                'name' => 'Football Jerseys',
                'slug' => 'football-jerseys',
                'display_type' => 'collection',
                'eyebrow' => 'Built for game day',
                'short_title' => 'Football Jerseys',
                'description' => 'Custom football jerseys made for teams, school programs, fan groups, and events. Add names, numbers, logos, and team colors.',
                'best_for' => 'Youth teams, school teams, local clubs, and fan orders.',
                'image_url' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom football jerseys for teams',
                'cta_label' => 'Shop Football Jerseys',
                'meta_title' => 'Custom Football Jerseys',
                'meta_description' => 'Shop customizable football jerseys for teams, schools, clubs, events, and supporters.',
                'match_rules' => ['sports' => ['Football'], 'categories' => ['Football Jerseys']],
                'highlights' => ['Names and numbers', 'Adult and youth sizing', 'Digital artwork review'],
                'sort_order' => 30,
                'tags' => ['jerseys', 'uniforms', 'fan'],
            ],
            [
                'name' => 'Baseball Uniforms',
                'slug' => 'baseball-uniforms',
                'display_type' => 'collection',
                'eyebrow' => 'Club and league ready',
                'short_title' => 'Baseball Uniforms',
                'description' => 'Baseball jerseys and uniform sets for teams, schools, clubs, and tournaments. Customize with logo, player name, number, and team colors.',
                'best_for' => 'Baseball teams, softball teams, school teams, and league orders.',
                'image_url' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Baseball uniforms and team apparel',
                'cta_label' => 'Shop Baseball Uniforms',
                'meta_title' => 'Custom Baseball Uniforms',
                'meta_description' => 'Shop custom baseball uniforms and jerseys for schools, clubs, tournaments, and leagues.',
                'match_rules' => ['sports' => ['Baseball'], 'categories' => ['Baseball Uniforms']],
                'highlights' => ['Jerseys and uniform sets', 'Roster sizing support', 'Bulk team workflow'],
                'sort_order' => 40,
                'tags' => ['uniforms', 'apparel', 'headwear'],
            ],
            [
                'name' => 'Basketball Jerseys',
                'slug' => 'basketball-jerseys',
                'display_type' => 'collection',
                'eyebrow' => 'Fast and breathable',
                'short_title' => 'Basketball Jerseys',
                'description' => 'Custom basketball jerseys and team sets for games, practice, tournaments, and fan wear. Choose your color, logo, name, and number.',
                'best_for' => 'School teams, clubs, leagues, tournaments, and fan groups.',
                'image_url' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom basketball jerseys',
                'cta_label' => 'Shop Basketball Jerseys',
                'meta_title' => 'Custom Basketball Jerseys',
                'meta_description' => 'Shop custom basketball jerseys and team sets with logos, names, numbers, and colors.',
                'match_rules' => ['sports' => ['Basketball'], 'categories' => ['Basketball Jerseys']],
                'highlights' => ['Performance fabric', 'Custom player details', 'Team and fan orders'],
                'sort_order' => 50,
                'tags' => ['jerseys', 'uniforms', 'apparel'],
            ],
            [
                'name' => 'Soccer Kits',
                'slug' => 'soccer-kits',
                'display_type' => 'collection',
                'eyebrow' => 'Complete club kits',
                'short_title' => 'Soccer Kits',
                'description' => 'Custom soccer jerseys, shorts, and full kits for teams and clubs. Add your team crest, sponsor logo, player names, and numbers.',
                'best_for' => 'Soccer clubs, school teams, amateur leagues, and tournaments.',
                'image_url' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom soccer kits',
                'cta_label' => 'Shop Soccer Kits',
                'meta_title' => 'Custom Soccer Kits',
                'meta_description' => 'Shop custom soccer jerseys, shorts, and full club kits with crests, sponsors, names, and numbers.',
                'match_rules' => ['sports' => ['Soccer'], 'categories' => ['Soccer Kits']],
                'highlights' => ['Full kit options', 'Crests and sponsor logos', 'Player roster support'],
                'sort_order' => 60,
                'tags' => ['jerseys', 'uniforms', 'apparel'],
            ],
            [
                'name' => 'Hoodies & Sweatshirts',
                'slug' => 'hoodies-sweatshirts',
                'display_type' => 'collection',
                'eyebrow' => 'Travel and casual teamwear',
                'short_title' => 'Hoodies',
                'description' => 'Custom hoodies and sweatshirts for teams, staff, fans, schools, and businesses. Good for travel, training, events, and casual wear.',
                'best_for' => 'Team travel gear, school apparel, staff apparel, and fan merchandise.',
                'image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom hoodies for team travel gear',
                'cta_label' => 'Shop Hoodies',
                'meta_title' => 'Custom Hoodies and Sweatshirts',
                'meta_description' => 'Shop custom hoodies and sweatshirts for teams, schools, staff, events, and supporters.',
                'match_rules' => ['categories' => ['Apparel'], 'tag_terms' => ['hoodie', 'sweatshirt']],
                'highlights' => ['Team logos', 'Travel and training use', 'Bulk order support'],
                'sort_order' => 70,
                'tags' => ['apparel', 'fan'],
            ],
            [
                'name' => 'Performance T-Shirts',
                'slug' => 'performance-t-shirts',
                'display_type' => 'collection',
                'eyebrow' => 'Lightweight branded apparel',
                'short_title' => 'Performance T-Shirts',
                'description' => 'Lightweight custom t-shirts for training, events, teams, and promotions. Add your logo, event name, team design, or sponsor branding.',
                'best_for' => 'Practice wear, running events, gym groups, school events, and business promotions.',
                'image_url' => 'https://images.unsplash.com/photo-1517649763962-0c623066013b?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Performance t-shirts for sports and events',
                'cta_label' => 'Shop T-Shirts',
                'meta_title' => 'Custom Performance T-Shirts',
                'meta_description' => 'Shop lightweight customized performance shirts for teams, training, events, schools, and promotions.',
                'match_rules' => ['categories' => ['Apparel'], 'tag_terms' => ['shirt', 'training']],
                'highlights' => ['Lightweight fabrics', 'Logo and event printing', 'Team and promotional orders'],
                'sort_order' => 80,
                'tags' => ['apparel', 'promo'],
            ],
            [
                'name' => 'Caps & Headwear',
                'slug' => 'caps-headwear',
                'display_type' => 'collection',
                'eyebrow' => 'Branded finishing touches',
                'short_title' => 'Caps & Headwear',
                'description' => 'Custom caps, hats, and headwear for teams, events, businesses, and fan groups. Add your logo, team name, or event design.',
                'best_for' => 'Baseball teams, school merch, corporate events, giveaways, and fan gear.',
                'image_url' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom caps and sports headwear',
                'cta_label' => 'Shop Caps',
                'meta_title' => 'Custom Caps and Headwear',
                'meta_description' => 'Shop personalized caps and headwear for teams, events, businesses, schools, and fan groups.',
                'match_rules' => ['categories' => ['Caps & Hats'], 'tag_terms' => ['cap', 'hat']],
                'highlights' => ['Logo embroidery', 'Team and event branding', 'Bulk availability'],
                'sort_order' => 90,
                'tags' => ['headwear', 'fan', 'promo', 'accessories'],
            ],
            [
                'name' => 'Sports Bags',
                'slug' => 'sports-bags',
                'display_type' => 'collection',
                'eyebrow' => 'Carry the complete kit',
                'short_title' => 'Sports Bags',
                'description' => 'Custom sports bags, duffel bags, backpacks, and drawstring bags for teams, schools, gyms, and events.',
                'best_for' => 'Team gear, player bags, school events, tournaments, and promotional giveaways.',
                'image_url' => 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom sports bags for teams',
                'cta_label' => 'Shop Bags',
                'meta_title' => 'Custom Sports Bags',
                'meta_description' => 'Shop customized duffel bags, backpacks, sports bags, and drawstring bags for teams and events.',
                'match_rules' => ['categories' => ['Bags'], 'tag_terms' => ['bag', 'duffel']],
                'highlights' => ['Player personalization', 'Team logos', 'Travel-ready sizes'],
                'sort_order' => 100,
                'tags' => ['bags', 'promo', 'accessories'],
            ],
            [
                'name' => 'Fan Gear',
                'slug' => 'fan-gear',
                'display_type' => 'collection',
                'eyebrow' => 'Made for supporters',
                'short_title' => 'Fan Gear',
                'description' => 'Custom apparel and accessories for supporters, family members, and fan groups. Create matching gear for game day or special events.',
                'best_for' => 'Fans, parents, alumni groups, supporters, and tournament events.',
                'image_url' => 'https://images.unsplash.com/photo-1577223625816-7546f13df25d?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom fan gear and team apparel',
                'cta_label' => 'Shop Fan Gear',
                'meta_title' => 'Custom Fan Gear',
                'meta_description' => 'Shop custom fan apparel, jerseys, caps, and accessories for game day, alumni groups, and supporters.',
                'match_rules' => ['sports' => ['Fan Gear'], 'categories' => ['Custom Jerseys'], 'tag_terms' => ['fan']],
                'highlights' => ['Supporter jerseys', 'Matching apparel', 'Event-ready customization'],
                'sort_order' => 110,
                'tags' => ['fan', 'apparel', 'jerseys', 'headwear'],
            ],
            [
                'name' => 'Promotional Products',
                'slug' => 'promotional-products',
                'display_type' => 'collection',
                'eyebrow' => 'Business and event branding',
                'short_title' => 'Promotional Products',
                'description' => 'Branded apparel, caps, bags, and custom items for businesses, events, campaigns, and giveaways.',
                'best_for' => 'Corporate events, school programs, trade shows, local campaigns, and brand promotions.',
                'image_url' => 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Promotional sportswear for events',
                'cta_label' => 'View Promotional Products',
                'meta_title' => 'Custom Promotional Products',
                'meta_description' => 'Browse branded apparel, caps, bags, and promotional items for businesses, schools, events, and campaigns.',
                'match_rules' => ['categories' => ['Apparel', 'Caps & Hats', 'Bags'], 'tag_terms' => ['bulk']],
                'highlights' => ['Business branding', 'Event merchandise', 'High-volume quote support'],
                'sort_order' => 120,
                'tags' => ['promo', 'apparel', 'bags', 'headwear'],
            ],
            [
                'name' => 'Training Wear',
                'slug' => 'training-wear',
                'display_type' => 'collection',
                'eyebrow' => 'Practice-ready apparel',
                'short_title' => 'Training Wear',
                'description' => 'Comfortable apparel for practice, warmups, fitness, and daily training. Add your team logo or brand design where available.',
                'best_for' => 'Sports teams, gyms, coaches, training programs, and fitness groups.',
                'image_url' => 'https://images.unsplash.com/photo-1518611012118-696072aa579a?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Training wear for sports teams',
                'cta_label' => 'Shop Training Wear',
                'meta_title' => 'Custom Training Wear',
                'meta_description' => 'Shop customized training apparel for teams, coaches, gyms, warmups, practice, and fitness programs.',
                'match_rules' => ['sports' => ['Training'], 'categories' => ['Apparel'], 'tag_terms' => ['training']],
                'highlights' => ['Practice and warmup use', 'Team logo options', 'Comfort-focused styles'],
                'sort_order' => 130,
                'tags' => ['apparel', 'uniforms'],
            ],
            [
                'name' => 'Outerwear & Jackets',
                'slug' => 'outerwear-jackets',
                'display_type' => 'collection',
                'eyebrow' => 'For sidelines and travel',
                'short_title' => 'Outerwear & Jackets',
                'description' => 'Custom jackets and outerwear for teams, coaches, staff, and supporters. Useful for travel, sidelines, school events, and colder weather.',
                'best_for' => 'Team travel, coaches, staff apparel, school spirit wear, and club merchandise.',
                'image_url' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Outerwear and jackets for team travel',
                'cta_label' => 'Shop Outerwear',
                'meta_title' => 'Custom Team Outerwear and Jackets',
                'meta_description' => 'Shop customized jackets and outerwear for teams, coaches, staff, schools, clubs, and supporters.',
                'match_rules' => ['categories' => ['Apparel'], 'tag_terms' => ['jacket', 'outerwear', 'hoodie']],
                'highlights' => ['Team travel gear', 'Staff and coach apparel', 'Logo customization'],
                'sort_order' => 140,
                'tags' => ['apparel', 'fan'],
            ],
            [
                'name' => 'Apparel',
                'slug' => 'apparel',
                'display_type' => 'navigation',
                'eyebrow' => 'Custom team apparel',
                'short_title' => 'Apparel',
                'description' => 'Browse custom hoodies, performance shirts, training wear, jackets, and branded clothing for teams, schools, businesses, and events.',
                'best_for' => 'Teams, schools, staff, businesses, events, travel, training, and supporter apparel.',
                'image_url' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom sports apparel and hoodies',
                'cta_label' => 'Shop Apparel',
                'meta_title' => 'Custom Sports Apparel',
                'meta_description' => 'Browse custom hoodies, shirts, training wear, outerwear, and branded sports apparel.',
                'match_rules' => ['categories' => ['Apparel'], 'tag_terms' => ['hoodie', 'shirt', 'training', 'jacket']],
                'highlights' => ['Team and staff apparel', 'Logo customization', 'Bulk order support'],
                'sort_order' => 145,
                'tags' => ['apparel'],
            ],
            [
                'name' => 'Accessories',
                'slug' => 'accessories',
                'display_type' => 'collection',
                'eyebrow' => 'Complete your order',
                'short_title' => 'Accessories',
                'description' => 'Useful custom accessories for sports teams, events, schools, and promotional use. Choose items that support your order or complete your team package.',
                'best_for' => 'Team add-ons, event extras, giveaways, and branded merchandise.',
                'image_url' => 'https://images.unsplash.com/photo-1511556532299-8f662fc26c06?auto=format&fit=crop&w=700&q=80',
                'image_alt' => 'Custom accessories for sports teams and events',
                'cta_label' => 'View Accessories',
                'meta_title' => 'Custom Sports Accessories',
                'meta_description' => 'Browse custom sports accessories, team add-ons, branded extras, event items, and promotional merchandise.',
                'match_rules' => ['sports' => ['Accessories'], 'categories' => ['Caps & Hats', 'Bags']],
                'highlights' => ['Team add-ons', 'Event extras', 'Branded merchandise'],
                'sort_order' => 150,
                'tags' => ['accessories', 'promo', 'fan'],
            ],

            ...$this->sports(),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sports(): array
    {
        $sports = [
            ['Football', 'football', 'Football', 'Custom football jerseys, team uniforms, fan gear, and practice apparel.', 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?auto=format&fit=crop&w=500&q=80', 'Football gear', 10],
            ['Baseball', 'baseball', 'Baseball', 'Baseball jerseys, caps, uniforms, bags, and team apparel.', 'https://images.unsplash.com/photo-1508344928928-7165b67de128?auto=format&fit=crop&w=500&q=80', 'Baseball gear', 20],
            ['Basketball', 'basketball', 'Basketball', 'Basketball jerseys, shorts, hoodies, warmups, and fan wear.', 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=500&q=80', 'Basketball gear', 30],
            ['Soccer', 'soccer', 'Soccer', 'Soccer jerseys, full kits, training wear, and supporter gear.', 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=500&q=80', 'Soccer gear', 40],
            ['Softball', 'softball', 'Softball', 'Softball jerseys, uniforms, caps, bags, and team apparel.', 'https://images.unsplash.com/photo-1562771242-a02d9090c90c?auto=format&fit=crop&w=500&q=80', 'Softball gear', 50],
            ['Volleyball', 'volleyball', 'Volleyball', 'Volleyball jerseys, team shirts, hoodies, and training apparel.', 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=500&q=80', 'Volleyball gear', 60],
            ['Ice Hockey', 'ice-hockey', 'Ice Hockey', 'Custom hockey jerseys and team gear for players and supporters.', 'https://images.unsplash.com/photo-1515703407324-5f753afd8be8?auto=format&fit=crop&w=500&q=80', 'Hockey gear', 70],
            ['Track & Field', 'track-field', 'Track & Field', 'Custom training shirts, team apparel, bags, and event gear.', 'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?auto=format&fit=crop&w=500&q=80', 'Track gear', 80],
            ['Cheerleading', 'cheerleading', 'Cheerleading', 'Custom apparel, warmups, bags, and spirit wear for cheer teams.', 'https://images.unsplash.com/photo-1547347298-4074fc3086f0?auto=format&fit=crop&w=500&q=80', 'Cheerleading gear', 90],
            ['Training & Fitness', 'training-fitness', 'Training', 'Performance shirts, hoodies, bags, and branded fitness apparel.', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=500&q=80', 'Training and fitness gear', 100],
        ];

        return array_map(static fn (array $sport): array => [
            'name' => $sport[0],
            'slug' => $sport[1],
            'display_type' => 'sport',
            'eyebrow' => 'Sport-specific',
            'short_title' => $sport[0] . ' Gear',
            'description' => $sport[3],
            'best_for' => null,
            'image_url' => $sport[4],
            'image_alt' => $sport[5],
            'cta_label' => 'Shop ' . $sport[0] . ' Gear',
            'meta_title' => 'Custom ' . $sport[0] . ' Sportswear',
            'meta_description' => $sport[3],
            'match_rules' => ['sports' => [$sport[2]], 'tag_terms' => [strtolower($sport[2])]],
            'highlights' => ['Custom logos and colors', 'Names and numbers', 'Team and bulk ordering'],
            'sort_order' => $sport[6],
            'tags' => [],
        ], $sports);
    }
}

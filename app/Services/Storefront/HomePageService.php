<?php

namespace App\Services\Storefront;

class HomePageService
{
    public function getHomePageData(): array
    {
        return [
            'seo' => $this->seo(),
            'slides' => $this->slides(),
            'categories' => $this->categories(),
            'buyerPaths' => $this->buyerPaths(),
            'featuredProducts' => $this->featuredProducts(),
            'sports' => $this->sports(),
            'processSteps' => $this->processSteps(),
            'faqs' => $this->faqs(),
        ];
    }

    private function seo(): array
    {
        return [
            'title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'description' => 'Shop custom sportswear, team uniforms, jerseys, hoodies, caps, bags, and promotional products. Bulk quotes available for teams and events.',
            'robots' => 'index, follow',
            'canonical' => route('home'),
            'og_title' => 'Custom Sportswear, Team Uniforms & Jerseys | ' . config('storefront.name'),
            'og_description' => 'Custom sportswear, jerseys, uniforms, hoodies, caps, bags, and promotional products for teams, schools, businesses, and events.',
            'og_image' => 'https://images.unsplash.com/photo-1517466787929-bc90951d0974?auto=format&fit=crop&w=1200&q=80',
        ];
    }

    private function slides(): array
    {
        return [
            [
                'eyebrow' => 'Custom Sportswear USA',
                'title' => 'Custom Jerseys for Teams, Schools, and Fans',
                'description' => 'Design jerseys with names, numbers, logos, colors, and team details.',
                'image' => 'https://images.unsplash.com/photo-1519861531473-9200262188bf?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Custom jerseys and sportswear arranged for a team order',
                'primary_label' => 'Start Your Order',
                'primary_url' => route('products.index'),
                'secondary_label' => 'Request Bulk Quote',
                'secondary_url' => route('quote.request'),
            ],
            [
                'eyebrow' => 'Team Uniforms',
                'title' => 'Uniforms for Clubs, Schools, and Leagues',
                'description' => 'Order full team sets with size lists, logos, colors, and design support.',
                'image' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Team uniforms hanging in a locker room',
                'primary_label' => 'Shop Uniforms',
                'primary_url' => route('products.index'),
                'secondary_label' => 'Get a Quote',
                'secondary_url' => route('quote.request'),
            ],
            [
                'eyebrow' => 'Bulk Orders',
                'title' => 'Bulk Apparel for Events and Businesses',
                'description' => 'Caps, hoodies, bags, promotional products, and branded team gear.',
                'image' => 'https://images.unsplash.com/photo-1526232761682-d26e03ac148e?auto=format&fit=crop&w=1600&q=80',
                'alt' => 'Players on a sports field during a team event',
                'primary_label' => 'Request Quote',
                'primary_url' => route('quote.request'),
                'secondary_label' => 'View Products',
                'secondary_url' => route('products.index'),
            ],
        ];
    }

    private function categories(): array
    {
        return [
            [
                'title' => 'Custom Team Uniforms',
                'description' => 'Full team sets for game day, travel, practice, and league play.',
                'image' => 'https://images.unsplash.com/photo-1551958219-acbc608c6377?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Custom team uniforms hanging in a locker room',
                'url' => route('products.index'),
                'link_label' => 'Shop Now',
            ],
            [
                'title' => 'Custom Jerseys',
                'description' => 'Names, numbers, logos, colors, and player details.',
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Custom jerseys on display',
                'url' => route('products.index'),
                'link_label' => 'Customize Yours',
            ],
            [
                'title' => 'Hoodies & Outerwear',
                'description' => 'Warm team gear for players, coaches, fans, and staff.',
                'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Sports hoodie on a clean background',
                'url' => route('products.index'),
                'link_label' => 'Shop Now',
            ],
            [
                'title' => 'Caps & Headwear',
                'description' => 'Custom caps and hats for teams, events, and brand orders.',
                'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Custom cap with embroidered logo',
                'url' => route('products.index'),
                'link_label' => 'Shop Now',
            ],
            [
                'title' => 'Sports Bags',
                'description' => 'Team bags, gym bags, and travel-ready gear options.',
                'image' => 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Sports duffel bag for team travel',
                'url' => route('products.index'),
                'link_label' => 'Shop Now',
            ],
            [
                'title' => 'Promotional Products',
                'description' => 'Branded apparel and giveaway items for schools and events.',
                'image' => 'https://images.unsplash.com/photo-1526045612212-70caf35c14df?auto=format&fit=crop&w=700&q=80',
                'alt' => 'Promotional items and event apparel on a table',
                'url' => route('quote.request'),
                'link_label' => 'Request Bulk Quote',
            ],
        ];
    }

    private function buyerPaths(): array
    {
        return [
            [
                'icon' => '♜',
                'title' => 'Teams & Leagues',
                'description' => 'Uniforms and gear for full teams, clubs, and local leagues.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '★',
                'title' => 'Schools & Colleges',
                'description' => 'Custom jerseys, PE uniforms, event apparel, and spirit wear.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '▣',
                'title' => 'Businesses & Events',
                'description' => 'Branded apparel, caps, bags, and giveaway items.',
                'url' => route('quote.request'),
            ],
            [
                'icon' => '✓',
                'title' => 'Individual Buyers',
                'description' => 'Shop selected products online and customize where available.',
                'url' => route('products.index'),
            ],
        ];
    }

    private function featuredProducts(): array
    {
        return [
            [
                'title' => 'Custom Football Jersey with Name & Number',
                'price' => 'From $39',
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Custom football jersey',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Baseball Uniform Set for Teams',
                'price' => 'Request Quote',
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'image' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Baseball uniform set',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Custom Basketball Jersey',
                'price' => 'From $34',
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Custom basketball jersey',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Sublimated Soccer Kit',
                'price' => 'Request Quote',
                'tag' => 'Team Order',
                'tag_color' => 'navy',
                'image' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Sublimated soccer kit',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Custom Team Hoodie',
                'price' => 'From $45',
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'image' => 'https://images.unsplash.com/photo-1556821840-3a63f95609a7?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Custom team hoodie',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Custom Embroidered Cap',
                'price' => 'From $18',
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'image' => 'https://images.unsplash.com/photo-1521369909029-2afed882baee?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Custom embroidered cap',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Personalized Sports Duffel Bag',
                'price' => 'From $32',
                'tag' => 'Bulk Available',
                'tag_color' => 'blue',
                'image' => 'https://images.unsplash.com/photo-1622560480654-d96214fdc887?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Personalized sports duffel bag',
                'url' => route('products.index'),
            ],
            [
                'title' => 'Custom Fan Jersey',
                'price' => 'From $36',
                'tag' => 'Customizable',
                'tag_color' => 'red',
                'image' => 'https://images.unsplash.com/photo-1599058917212-d750089bc07e?auto=format&fit=crop&w=650&q=80',
                'alt' => 'Custom fan jersey',
                'url' => route('products.index'),
            ],
        ];
    }

    private function sports(): array
    {
        return [
            ['title' => 'Football', 'image' => 'https://images.unsplash.com/photo-1566577739112-5180d4bf9390?auto=format&fit=crop&w=400&q=80', 'alt' => 'Football sportswear', 'url' => route('products.index')],
            ['title' => 'Baseball', 'image' => 'https://images.unsplash.com/photo-1533236897111-3e94666b2edf?auto=format&fit=crop&w=400&q=80', 'alt' => 'Baseball uniform', 'url' => route('products.index')],
            ['title' => 'Basketball', 'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?auto=format&fit=crop&w=400&q=80', 'alt' => 'Basketball jersey', 'url' => route('products.index')],
            ['title' => 'Soccer', 'image' => 'https://images.unsplash.com/photo-1553778263-73a83bab9b0c?auto=format&fit=crop&w=400&q=80', 'alt' => 'Soccer uniform', 'url' => route('products.index')],
            ['title' => 'Volleyball', 'image' => 'https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?auto=format&fit=crop&w=400&q=80', 'alt' => 'Volleyball gear', 'url' => route('products.index')],
            ['title' => 'Training', 'image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=400&q=80', 'alt' => 'Training sportswear', 'url' => route('products.index')],
        ];
    }

    private function processSteps(): array
    {
        return [
            ['title' => 'Choose Product', 'description' => 'Pick the product, sport, category, or apparel type.'],
            ['title' => 'Share Custom Details', 'description' => 'Send your logo, colors, names, numbers, size list, and quantity.'],
            ['title' => 'Review Mockup', 'description' => 'We prepare or review the artwork before production.'],
            ['title' => 'Confirm Order', 'description' => 'Approve the final details, price, and timeline.'],
            ['title' => 'Production & Shipping', 'description' => 'Your order goes into production and ships to your address.'],
        ];
    }

    private function faqs(): array
    {
        return [
            [
                'question' => 'Can I order one custom jersey?',
                'answer' => 'Yes, selected products can be ordered directly online. Some custom products may have a minimum order quantity.',
            ],
            [
                'question' => 'Do you offer bulk pricing?',
                'answer' => 'Yes. For larger orders, please contact us for a custom quotation.',
            ],
            [
                'question' => 'Can I add player names and numbers?',
                'answer' => 'Yes. For jerseys and team uniforms, you can usually add names, numbers, logos, and team colors.',
            ],
            [
                'question' => 'Do you help with artwork or mockups?',
                'answer' => 'Yes. You can send your logo or design idea. A proof or mockup may be reviewed before production.',
            ],
        ];
    }
}

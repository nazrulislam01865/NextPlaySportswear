<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class ContentPageController extends Controller
{
    public function about(): View
    {
        return view('storefront.content.about');
    }

    public function faq(): View
    {
        $categories = [
            'Ordering' => [
                ['question' => 'Can I place a regular order directly online?', 'answer' => 'Yes. Products showing an active price and Add to Cart action can be purchased online. Products marked Request Quote need a custom quotation before payment.'],
                ['question' => 'Can I order only one custom jersey?', 'answer' => 'Some products allow single-piece orders, while others have a minimum order quantity. The product page will show the applicable ordering method.'],
                ['question' => 'Can I reorder a previous team design?', 'answer' => 'Yes. Contact support with the previous order number and the changes you need. Keep player names, sizes, and artwork ready so the reorder can be reviewed accurately.'],
            ],
            'Customization' => [
                ['question' => 'Can I add player names and numbers?', 'answer' => 'Yes. Many jerseys and uniforms support names, numbers, team logos, sponsor marks, and custom colors. Available options vary by product.'],
                ['question' => 'Will I see a proof before production?', 'answer' => 'Custom team and bulk orders may include an artwork or mockup review. Production should begin only after the required approval has been recorded.'],
                ['question' => 'What artwork files should I upload?', 'answer' => 'Vector files such as AI, EPS, SVG, or print-ready PDF are preferred. High-resolution PNG files with transparent backgrounds may also be acceptable.'],
            ],
            'Shipping' => [
                ['question' => 'Do you ship across the United States?', 'answer' => 'Yes. Available methods, costs, and delivery estimates depend on the destination, order size, production status, and carrier availability.'],
                ['question' => 'Does the delivery estimate include production time?', 'answer' => 'Not always. Custom products usually require production before shipment. Review both the production estimate and the carrier transit estimate.'],
                ['question' => 'How can I track my order?', 'answer' => 'Use the Track Order page with your order number and email address. Tracking becomes available after the shipment is created.'],
            ],
            'Returns & Payments' => [
                ['question' => 'Can customized products be returned?', 'answer' => 'Customized products are generally not returnable for preference changes. Contact support promptly if an item is defective, damaged, or does not match the approved order details.'],
                ['question' => 'When will my card be charged?', 'answer' => 'Online orders are charged when payment is confirmed. Quote-based orders follow the payment schedule stated in the accepted quotation.'],
                ['question' => 'Are payment details stored on the website?', 'answer' => 'Sensitive card data should be handled by the configured payment provider. The storefront should retain only permitted references and masked payment details.'],
            ],
        ];

        return view('storefront.content.faq', compact('categories'));
    }

    public function howToOrder(): View
    {
        return view('storefront.content.how-to-order');
    }

    public function sizeGuide(): View
    {
        return view('storefront.content.size-guide');
    }

    public function artworkGuidelines(): View
    {
        return view('storefront.content.artwork-guidelines');
    }

    public function customizationGuide(): View
    {
        return view('storefront.content.customization-guide');
    }

    public function bulkOrdering(): View
    {
        return view('storefront.content.bulk-ordering');
    }

    public function shipping(): View
    {
        return view('storefront.content.shipping');
    }

    public function returns(): View
    {
        return view('storefront.content.returns');
    }

    public function payment(): View
    {
        return view('storefront.content.payment');
    }

    public function privacy(): View
    {
        return $this->policy('privacy');
    }

    public function terms(): View
    {
        return $this->policy('terms');
    }

    public function cookies(): View
    {
        return $this->policy('cookies');
    }

    public function accessibility(): View
    {
        return $this->policy('accessibility');
    }

    private function policy(string $key): View
    {
        $pages = $this->policyPages();
        abort_unless(isset($pages[$key]), 404);

        return view('storefront.content.policy', $pages[$key]);
    }

    private function policyPages(): array
    {
        return [
            'privacy' => [
                'eyebrow' => 'Your information',
                'title' => 'Privacy Policy',
                'description' => 'How NextPlay Sportswear collects, uses, protects, and shares information when you browse, create an account, request a quote, or place an order.',
                'updated' => 'June 22, 2026',
                'sections' => [
                    ['id' => 'information-we-collect', 'title' => 'Information We Collect', 'paragraphs' => ['We may collect account details, contact information, shipping and billing addresses, order information, customization instructions, uploaded artwork, support messages, device information, and website usage data.', 'Payment information is processed through the configured payment provider. We should store only information permitted by that provider, such as a transaction reference, payment status, and masked card details.'], 'items' => ['Information you provide directly', 'Information generated while using the storefront', 'Information supplied by payment, shipping, and fraud-prevention providers']],
                    ['id' => 'how-we-use-data', 'title' => 'How We Use Information', 'paragraphs' => ['Information is used to operate the storefront, process purchases and quotes, prepare custom products, provide customer support, prevent fraud, meet legal obligations, and improve the shopping experience.'], 'items' => ['Fulfil and deliver orders', 'Prepare artwork proofs and custom quotations', 'Communicate service and order updates', 'Maintain security and prevent misuse', 'Measure and improve site performance']],
                    ['id' => 'sharing', 'title' => 'How Information Is Shared', 'paragraphs' => ['We may share only the information reasonably needed with service providers that support payment processing, shipping, hosting, email delivery, analytics, fraud prevention, and production. We do not sell personal information.']],
                    ['id' => 'retention-security', 'title' => 'Retention and Security', 'paragraphs' => ['Information is retained only for as long as reasonably necessary for order fulfilment, customer support, accounting, legal compliance, dispute resolution, and security. Administrative, technical, and organizational safeguards should be used to protect stored information.']],
                    ['id' => 'choices', 'title' => 'Your Choices and Rights', 'paragraphs' => ['Depending on your location, you may have rights to access, correct, delete, restrict, or receive a copy of certain personal information. You may also opt out of non-essential marketing communications.']],
                    ['id' => 'children', 'title' => 'Children’s Privacy', 'paragraphs' => ['The storefront is not directed to children under 13. Orders involving youth teams should be managed by an adult, school, club, or authorized organization representative.']],
                    ['id' => 'contact', 'title' => 'Contact Us About Privacy', 'paragraphs' => ['Questions or requests about this policy can be submitted through the Contact Us page or sent to the support email shown in the footer.']],
                ],
            ],
            'terms' => [
                'eyebrow' => 'Store rules',
                'title' => 'Terms and Conditions',
                'description' => 'The rules that apply when you use the NextPlay Sportswear website, submit artwork, request a quote, or purchase products.',
                'updated' => 'June 22, 2026',
                'sections' => [
                    ['id' => 'acceptance', 'title' => 'Acceptance of Terms', 'paragraphs' => ['By using the storefront or placing an order, you agree to these terms and all policies referenced within them. If you do not agree, do not submit an order or upload content.']],
                    ['id' => 'accounts', 'title' => 'Accounts and Accurate Information', 'paragraphs' => ['You are responsible for safeguarding account credentials and providing accurate contact, sizing, customization, delivery, and payment information. Notify support promptly if you believe an account has been compromised.']],
                    ['id' => 'orders-quotes', 'title' => 'Orders, Quotes, and Availability', 'paragraphs' => ['An online order or quote request is an offer to purchase. Acceptance may depend on product availability, artwork suitability, minimum quantities, payment approval, and production capacity.', 'A quotation may have an expiration date and may change if quantities, materials, customization, shipping requirements, or deadlines change.']],
                    ['id' => 'artwork', 'title' => 'Artwork and Customer Content', 'paragraphs' => ['You confirm that you have permission to use all logos, names, marks, photographs, and artwork you submit. You remain responsible for infringement claims arising from unauthorized content.', 'Minor production variations may occur between screens, proofs, printed samples, fabric lots, and finished products.']],
                    ['id' => 'pricing-payment', 'title' => 'Pricing and Payment', 'paragraphs' => ['Prices, taxes, shipping, discounts, and payment schedules are shown during checkout or stated in an accepted quote. Orders may be paused or cancelled when payment cannot be confirmed.']],
                    ['id' => 'shipping-risk', 'title' => 'Shipping and Delivery', 'paragraphs' => ['Delivery dates are estimates unless expressly guaranteed in writing. Customers are responsible for providing a complete, deliverable address and reviewing tracking updates.']],
                    ['id' => 'returns', 'title' => 'Returns, Cancellations, and Refunds', 'paragraphs' => ['Eligibility is governed by the Returns, Refunds, and Exchanges Policy. Customized products may have limited cancellation and return options once artwork is approved or production begins.']],
                    ['id' => 'liability', 'title' => 'Limitation of Liability', 'paragraphs' => ['To the extent permitted by law, liability is limited to the amount paid for the affected product or service. We are not responsible for indirect losses, lost profits, or delays outside reasonable control.']],
                    ['id' => 'changes', 'title' => 'Changes to These Terms', 'paragraphs' => ['We may update these terms as the storefront, services, or legal requirements change. The revised date at the top identifies the current version.']],
                ],
            ],
            'cookies' => [
                'eyebrow' => 'Browser controls',
                'title' => 'Cookie Policy',
                'description' => 'An overview of the cookies and similar technologies used to keep the storefront secure, remember preferences, and understand site performance.',
                'updated' => 'June 22, 2026',
                'sections' => [
                    ['id' => 'what-are-cookies', 'title' => 'What Cookies Are', 'paragraphs' => ['Cookies are small text files stored by your browser. Similar technologies may use local storage, pixels, or device identifiers to support website functions.']],
                    ['id' => 'essential', 'title' => 'Strictly Necessary Cookies', 'paragraphs' => ['These support security, authentication, session management, cart continuity, checkout, fraud prevention, and other functions required for the storefront to operate. They cannot always be disabled through the site.']],
                    ['id' => 'preferences', 'title' => 'Preference Cookies', 'paragraphs' => ['Preference cookies may remember selected language, region, display choices, saved settings, and other conveniences.']],
                    ['id' => 'analytics', 'title' => 'Analytics Cookies', 'paragraphs' => ['Analytics tools may help measure page usage, traffic sources, errors, and performance. Non-essential analytics should be used only according to applicable consent requirements.']],
                    ['id' => 'advertising', 'title' => 'Advertising Cookies', 'paragraphs' => ['Advertising or remarketing tools may be used only when configured and permitted. They can help measure campaigns or show relevant promotions on other services.']],
                    ['id' => 'control', 'title' => 'Managing Cookies', 'paragraphs' => ['You can adjust browser settings to delete or block cookies. Blocking essential cookies may prevent login, cart, checkout, or saved preference features from working correctly.']],
                    ['id' => 'updates', 'title' => 'Policy Updates', 'paragraphs' => ['This policy may be updated when technology, providers, or legal requirements change. Review the updated date for the current version.']],
                ],
            ],
            'accessibility' => [
                'eyebrow' => 'Inclusive shopping',
                'title' => 'Accessibility Statement',
                'description' => 'Our commitment to making NextPlay Sportswear usable by customers with a wide range of abilities, devices, and assistive technologies.',
                'updated' => 'June 22, 2026',
                'sections' => [
                    ['id' => 'commitment', 'title' => 'Our Commitment', 'paragraphs' => ['We aim to provide a clear, consistent, keyboard-accessible, and understandable shopping experience. Accessibility is considered when designing navigation, forms, product information, account features, and support content.']],
                    ['id' => 'measures', 'title' => 'Measures We Take', 'paragraphs' => ['The storefront uses semantic HTML, visible focus indicators, descriptive labels, responsive layouts, color contrast, alternative text, and error messaging intended to support assistive technologies.'], 'items' => ['Keyboard navigation', 'Screen-reader-friendly structure', 'Responsive text and layouts', 'Form labels and validation feedback', 'Reduced reliance on color alone']],
                    ['id' => 'limitations', 'title' => 'Known Limitations', 'paragraphs' => ['Some third-party payment, shipping, embedded media, uploaded artwork, or legacy content may not fully meet the same accessibility standard. We work to provide alternatives where reasonably possible.']],
                    ['id' => 'feedback', 'title' => 'Accessibility Feedback', 'paragraphs' => ['Please contact us if you encounter a barrier. Include the page address, the problem, the device or assistive technology used, and a preferred way for us to respond.']],
                    ['id' => 'response', 'title' => 'Response and Improvement', 'paragraphs' => ['We review accessibility feedback and prioritize fixes based on severity, customer impact, and technical feasibility. Alternative support may be provided while a permanent improvement is prepared.']],
                ],
            ],
        ];
    }
}

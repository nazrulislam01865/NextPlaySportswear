@include('errors.storefront-status', [
    'code' => '404',
    'title' => 'Page Not Found',
    'messageText' => 'We couldn’t find the page you’re looking for.',
    'primaryLabel' => 'Go to Homepage',
    'primaryUrl' => route('home'),
    'secondaryLabel' => 'Browse All Products',
    'secondaryUrl' => route('products.index'),
])

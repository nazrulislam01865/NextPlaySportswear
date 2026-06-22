@include('errors.error', [
    'code' => '404',
    'status' => 'Page not found',
    'title' => 'We Couldn’t Find That Page',
    'message' => 'The page may have moved, the address may be incomplete, or the product or category is no longer available. Use the links below to continue shopping or return to the homepage.',
    'showSupport' => true,
    'showShop' => true,
])

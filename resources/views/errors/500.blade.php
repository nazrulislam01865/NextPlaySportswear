@include('errors.storefront-status', [
    'code' => '500',
    'title' => 'Something Went Wrong',
    'messageText' => 'Please try again in a moment.',
    'primaryLabel' => 'Try Again',
    'primaryAction' => 'retry',
    'secondaryLabel' => 'Go to Homepage',
    'secondaryUrl' => route('home'),
])

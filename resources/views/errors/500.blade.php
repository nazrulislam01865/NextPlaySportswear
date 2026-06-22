@include('errors.error', [
    'code' => '500',
    'status' => 'Server error',
    'title' => 'Something Went Wrong',
    'message' => 'The storefront could not complete this request. Your payment should not be submitted again unless the order and payment status have been checked. Return to the homepage or contact support if the problem continues.',
    'showSupport' => true,
    'showShop' => false,
])

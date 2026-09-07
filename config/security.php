<?php

return [

    'email_verification' => [
        'expire_minutes' => (int) env('EMAIL_VERIFICATION_LINK_EXPIRE_MINUTES', 60),
        'resend' => [
            'ip_per_minute' => (int) env('EMAIL_VERIFICATION_RESEND_IP_PER_MINUTE', 6),
            'account_per_minute' => (int) env('EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_MINUTE', 3),
            'account_per_hour' => (int) env('EMAIL_VERIFICATION_RESEND_ACCOUNT_PER_HOUR', 10),
        ],
    ],

    'password_reset' => [

        /*
         * Reset-link requests are limited independently by source IP and by
         * normalized email fingerprint. The email buckets stop distributed
         * IPs from flooding one account, while IP buckets slow enumeration and
         * bot traffic across many addresses.
         */
        'request' => [
            'ip_per_minute' => (int) env('PASSWORD_RESET_REQUEST_IP_PER_MINUTE', 3),
            'ip_per_hour' => (int) env('PASSWORD_RESET_REQUEST_IP_PER_HOUR', 12),
            'email_per_minute' => (int) env('PASSWORD_RESET_REQUEST_EMAIL_PER_MINUTE', 2),
            'email_per_hour' => (int) env('PASSWORD_RESET_REQUEST_EMAIL_PER_HOUR', 5),
        ],

        /*
         * Token submission is separately limited. A valid token is already a
         * strong secret, but these limits reduce automated guessing and noisy
         * malformed submissions without affecting normal recovery.
         */
        'submit' => [
            'ip_per_minute' => (int) env('PASSWORD_RESET_SUBMIT_IP_PER_MINUTE', 5),
            'ip_per_hour' => (int) env('PASSWORD_RESET_SUBMIT_IP_PER_HOUR', 20),
            'email_per_minute' => (int) env('PASSWORD_RESET_SUBMIT_EMAIL_PER_MINUTE', 3),
            'email_per_hour' => (int) env('PASSWORD_RESET_SUBMIT_EMAIL_PER_HOUR', 10),
        ],
    ],

];

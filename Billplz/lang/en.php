<?php 
return [
    'name'                               => 'Billplz',
    'invoice-name'                       => 'Billplz',
    'option-name'                        => 'Billplz',
    'description'                        => '',

    'redirect-message'                   => 'Redirecting to Billplz...',
    'redirect-thankyou'                  => 'Redirecting...',
    'pay-now'                            => 'Pay Now',

    'settings' => [
        'api-key' => [
            'label'                      => 'API Key',
            'description'                => 'API key can be obtained from Settings, under Keys & Integration section.',
        ],
        'xsignature-key' => [
            'label'                      => 'X-Signature Key',
            'description'                => 'X-Signature key can be obtained from Settings, under Keys & Integration section.',
        ],
        'collection-id' => [
            'label'                      => 'Collection ID',
            'description'                => 'Collection ID can be obtained from Billplz dashboard > Billing.',
        ],
        'sandbox' => [
            'label'                      => 'Sandbox Mode',
            'description'                => 'Enable sandbox mode <p class="description" style="margin-left: 25px;">Billplz sandbox can be used to test payments. <strong>Sign up for a <a href=":sandbox_url" target="_blank">sandbox account</a></strong>.</p>',
        ],
        'commission-rate' => [
            'label'                      => 'Commission Rate (%)',
            'description'                => 'You can define the payment commission fee as %. Leave blank to override.',
        ],
    ],

    'error' => [
        'required'                       => ':attribute is required',

        'invalid-api-key'                => 'Invalid API key',
        'invalid-collection-id'          => 'Invalid collection ID',

        'missing-api-key'                => 'Missing API key',
        'missing-collection-id'          => 'Missing collection ID',

        // Payment error
        'missing-checkout-data'          => 'Missing checkout data',
        'missing-checkout-items'         => 'Missing checkout items',
        'missing-user-data'              => 'Missing user data',
        'missing-user-name'              => 'Missing user name',
        'missing-user-email'             => 'Missing user email',
        'missing-user-phone'             => 'Missing user phone',

        // Payment callback error
        'missing-checkout-id'            => 'Missing order ID',
        'checkout-not-found'             => 'Order not found',
        'missing-checkout-checksum'      => 'Missing checkout checksum',
        'invalid-checkout-checksum'      => 'Invalid checkout checksum',

        'payment-error'                  => 'Payment error: :error. Please try again or contact admin.',
        'callback-error'                 => 'Callback error: :error',

        'invalid-request'                => 'Invalid request',
    ],

    'checkout-id'                        => 'Order ID',

    // Callback message
    'payment-pending'                    => 'Order #:checkout_id has been marked as Pending',
    'payment-success'                    => 'Order #:checkout_id has been marked as Paid',
    'payment-failed'                     => 'Order #:checkout_id has been marked as Failed',

    // Payment reference
    'bill-id'                            => 'Bill ID',
    'transaction-id'                     => 'Transaction ID',
    'sandbox'                            => 'Sandbox',
    'yes'                                => 'Yes',
    'no'                                 => 'No',

    // Redirect message
    'thankyou-payment-pending'           => 'Payment pending. Redirecting in 5 seconds...',

    'save-settings'                      => 'Save Settings',
    'settings-success'                   => 'Settings saved successfully.',
];

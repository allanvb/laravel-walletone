<?php

return [
    /**
     * Merchant id from wallet one panel
     *
     */
    'merchant_id' => env('WALLETONE_MERCHANT', null),

    /**
     * Secret key from wallet one panel
     *
     */
    'secret' => env('WALLETONE_SECRET', null),

    /**
     * Signature method for encrypting data
     *
     * sha1 or md5
     */
    'signatureMethod' => env('WALLETONE_SIGNATURE', 'sha1'),

    /**
     * Payment currency
     *
     * Available currencies:
     * RUB, ZAR, USD, EUR, UAH, KZT, BYR, TJS, PLN
     *
     */
    'currency' => env('WALLETONE_CURRENCY', 'RUB'),

    /**
     * Link to redirect user after payment success
     *
     */
    'success_url' => env('WALLETONE_SUCCESS', null),

    /**
     * Link to redirect user after payment fail
     *
     */
    'fail_url' => env('WALLETONE_FAIL', null),
];

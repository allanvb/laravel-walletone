<?php

return [
    'merchant_id' => env('WALLETONE_MERCHANT', null),
    'secret' => env('WALLETONE_SECRET', null),
    'signatureMethod' => env('WALLETONE_SIGNATURE', 'sha1'),
    'currency' => env('WALLETONE_CURRENCY', 'RUB'),
    'success_url' => env('WALLETONE_SUCCESS', null),
    'fail_url' => env('WALLETONE_FAIL', null),
];

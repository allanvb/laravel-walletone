<?php

namespace Allanvb\LaravelWalletOne\Http\Middleware;

use Closure;
use Allanvb\LaravelWalletOne\Events\SuccessPayment;
use Allanvb\LaravelWalletOne\Facades\WalletOne;
use Illuminate\Http\Request;
use Allanvb\LaravelWalletOne\Events\FailedPayment;
use Allanvb\LaravelWalletOne\Exceptions\WalletOneException;

class WalletonePay
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $signatureSuccess = WalletOne::checkSignature(config('wallet-one.secret'),
                $request->all(), config('wallet-one.signatureMethod')
            );
        } catch (WalletOneException $e) {
            event(new FailedPayment($request->all(), $e));
            return WalletOne::response("Retry", $e->getMessage());
        }

        if (!$signatureSuccess) {
            event(new FailedPayment($request->all()));
            return WalletOne::response("Retry", "Unknown error");
        }

        event(new SuccessPayment($request->all()));

        return $next($request);
    }
}

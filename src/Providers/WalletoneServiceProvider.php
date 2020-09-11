<?php

namespace Allanvb\LaravelWalletOne\Providers;

use Illuminate\Support\ServiceProvider;
use Allanvb\LaravelWalletOne\WalletOne;

class WalletoneServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->publishes([__DIR__ . '/../../config/wallet-one.php' => config_path('wallet-one.php')], 'config');
    }

    public function register()
    {
        $this->app->singleton('walletone', function () {
            return new WalletOne(
                config('wallet-one.merchant_id'),
                config('wallet-one.secret'),
                config('wallet-one.signatureMethod'),
                config('wallet-one.currency'),
                config('wallet-one.success_url'),
                config('wallet-one.fail_url')
            );
        });
    }

    public function provides()
    {
        return ['walletone'];
    }

}

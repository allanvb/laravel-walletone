<h2 align="center">
    Laravel package for integrating WalletOne payment gateway into laravel app
</h2>

<p align="center">
    <a href="https://packagist.org/packages/allanvb/laravel-walletone"><img src="https://img.shields.io/packagist/v/allanvb/laravel-walletone?color=orange&style=flat-square" alt="Packagist Version"></a>
    <a href="https://packagist.org/packages/allanvb/laravel-walletone"><img src="https://img.shields.io/github/last-commit/allanvb/laravel-walletone?color=blue&style=flat-square" alt="GitHub last commit"></a>
    <a href="https://packagist.org/packages/allanvb/laravel-walletone"><img src="https://img.shields.io/packagist/l/allanvb/laravel-walletone?color=brightgreen&style=flat-square" alt="License"></a>
</p>

Package that integrates [WalletOne](https://www.walletone.com/) API into your Laravel app.

## Install

Via Composer

``` bash
$ composer require allanvb/laravel-walletone
```

If you're using Laravel 5.5 or above, the package will automatically register provider and facade.

#### Laravel 5.4 and below

Add `Allanvb\LaravelWalletOne\Providers\WalletoneServiceProvider` to the `providers` array in your `config/app.php`:

```php
'providers' => [
    // Other service providers...

    Allanvb\LaravelWalletOne\Providers\WalletoneServiceProvider::class,
],
```

Add an alias in your `config/app.php`:

```php
'aliases' => [
    ...
    'WalletOne' => Allanvb\LaravelWalletOne\Facades\WalletOne::class,
],
```

Or you can `use` the facade class when needed:

```php
use Allanvb\LaravelWalletOne\Facades\WalletOne;
```

## Configuration

You can use `php artisan vendor:publish` to copy the configuration file to your app's config directory:

```sh
$ php artisan vendor:publish --provider="Allanvb\LaravelWalletOne\Providers\WalletoneServiceProvider" --tag="config"
```

Then update `config/wallet-one.php` with your credentials. Also you can update your `.env` file with the following:

```dotenv
WALLETONE_MERCHANT=merchant_id
WALLETONE_SECRET=secret_key
WALLETONE_SIGNATURE=signature_method
WALLETONE_CURRENCY=currency
WALLETONE_SUCCESS=success_url
WALLETONE_FAIL=fail_url
```

## Usage:

To use the WalletOne Library you can access the facade, or request the instance from the service container:

```php
WalletOne::make($orderID, $amount, $description, $options);
```

Or

```php
app('walletone')->make($orderID, $amount, $description, $options);
```
Parameters:
- `$orderID` - (string) ID of user order on your e-commerce **(required)**.
- `$amount` - (float) Amount of money the user has to pay  **(required)**.
- `$description` - (string) Payment description **(required)**.
- `$options` - (array) Any other options you want to save on WalletOne service, or get back in response.

In order to create payment form for user, you have to get all post params by using `getParams()` method. 

```$php
$params = WalletOne::getParams();
```
Then send this params to your view and create form.

As form action use `WalletOne::API_URL`.

In order to get response from WalletOne service, you have to define a `post` route inside your `web.php`.
This route should use `\Allanvb\LaravelWalletOne\Http\Middleware\WalletonePay::class` middleware.

You can add this middleware to your `Kernel.php` inside `App\Http` folder.

```php
    protected $routeMiddleware = [
        // Other service providers...
        
        'walletone-payment' => \Allanvb\LaravelWalletOne\Http\Middleware\WalletonePay::class
    ];
```

Then you can use it as following:
```php
Route::post('/payment-webhook', 'YourController')->middleware('walletone-payment');
```
**NOTE:** 
- Your controller should return `WalletOne::response()` method !
- Don't forget to add your route into `$except` param of `VerifyCsrfToken` middleware !

Each request to your route will generate a `SuccessPayment` or `FailedPayment` event,
so all you have to do is to define a event listener for each of them.

## Security

If you discover any security related issues, please email [alan.vb@mail.ru](mailto:alan.vb@mail.ru) instead of using the issue tracker.

## Credits
This package is actually a continuity of [pdazcom/laravel-walletone](https://github.com/pdazcom/laravel-walletone) whose author is [Konstantin A.](https://github.com/pdazcom)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

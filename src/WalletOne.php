<?php

namespace Allanvb\LaravelWalletOne;

use Allanvb\LaravelWalletOne\Exceptions\WalletOneException;

class WalletOne
{
    const BASE64_PREFIX = 'BASE64:';
    const MD5 = 'md5';
    const SHA1 = 'sha1';
    const ERROR_SIGNATURE = 101;
    const ERROR_UNKNOWN = 201;
    const ERROR_HAVE_NOT_CURRENCY = 301;
    const API_URL = 'https://wl.walletone.com/checkout/checkout/Index';

    /**
     * @var integer
     */
    private $merchantID;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $signatureMethod;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var string
     */
    private $successURL;

    /**
     * @var string
     */
    private $failURL;

    /**
     * @var array
     */
    private $walletOptions;

    /**
     * @var array
     */
    private $paymentsAllowed = [];

    /**
     * @var array
     */
    private $paymentsExcept = [];

    /**
     * @var array
     */
    private static $CURRENCY_ID = [
        'RUB' => 643,
        'ZAR' => 710,
        'USD' => 840,
        'EUR' => 978,
        'UAH' => 980,
        'KZT' => 398,
        'BYR' => 974,
        'TJS' => 972,
        'PLN' => 985
    ];

    public function __construct($merchantID, $secret, $signatureMethod, $currency, $successURL, $failURL)
    {
        $this->merchantID = (int) $merchantID;
        $this->secret = (string) $secret;
        $this->signatureMethod = $signatureMethod ?? self::SHA1;
        $this->currency = $currency ?? 'RUB';
        $this->successURL = $successURL;
        $this->failURL = $failURL;

        $this->resolveWalletOptions();
    }

    /**
     * @param array $paymentMethods
     * @return $this
     * @throws WalletOneException
     */
    public function only(array $paymentMethods)
    {
        if (!empty($this->paymentsExcept)) {
            throw new WalletOneException('You cannot use `only` method along with `except` method');
        }

        $this->paymentsAllowed = $paymentMethods;
        $methods['WMI_PTENABLED'] = $paymentMethods;

        $this->walletOptions = self::array_merge_recursive_distinct($methods, $this->walletOptions);

        return $this;
    }

    /**
     * @param array $paymentMethods
     * @return $this
     * @throws WalletOneException
     */
    public function except(array $paymentMethods)
    {
        if (!empty($this->paymentsAllowed)) {
            throw new WalletOneException('You cannot use `except` methods along with `only` method');
        }

        $this->paymentsExcept = $paymentMethods;
        $methods['WMI_PTDISABLED'] = $paymentMethods;

        $this->walletOptions = self::array_merge_recursive_distinct($methods, $this->walletOptions);

        return $this;
    }

    /**
     * @param string $paymentNumber
     * @param float $amount
     * @param string $description
     * @param array $options
     * @return $this
     */
    public function make(string $paymentNumber, float $amount, string $description, array $options = [])
    {
        $paymentOptions = [
            'WMI_PAYMENT_NO' => $paymentNumber,
            'WMI_PAYMENT_AMOUNT' => $amount,
            'WMI_DESCRIPTION' => $description
        ];

        if (!empty($options)) {
            $paymentOptions = self::array_merge_recursive_distinct($options, $paymentOptions);
        }

        $this->walletOptions = self::array_merge_recursive_distinct($paymentOptions, $this->walletOptions);

        return $this;
    }

    /**
     * @return mixed
     * @throws \ErrorException
     */
    public function getParams()
    {
        $parameters = $this->walletOptions;

        $this->checkOptions($parameters);

        if (isset($parameters['WMI_DESCRIPTION'])) {
            $parameters['WMI_DESCRIPTION'] = self::BASE64_PREFIX . base64_encode($parameters['WMI_DESCRIPTION']);
        }
        if (isset($parameters['WMI_SUCCESS_URL'])) {
            $parameters['WMI_SUCCESS_URL'] = url($parameters['WMI_SUCCESS_URL']);
        }
        if (isset($parameters['WMI_FAIL_URL'])) {
            $parameters['WMI_FAIL_URL'] = url($parameters['WMI_FAIL_URL']);
        }

        $parameters['WMI_PAYMENT_AMOUNT'] = number_format($parameters['WMI_PAYMENT_AMOUNT'], 2, '.', '');

        if (isset($this->secret) && isset($this->signatureMethod)) {
            $signature = self::getSignature($this->secret, $parameters, $this->signatureMethod);
            $parameters["WMI_SIGNATURE"] = $signature;
        }

        return $parameters;
    }

    /**
     * @param $secret
     * @param array $data
     * @param string $signatureMethod
     * @return bool
     * @throws WalletOneException
     */
    public function checkSignature($secret, array $data, $signatureMethod = self::SHA1)
    {
        if (empty($data)) {
            throw new WalletOneException('Empty POST data received', self::ERROR_UNKNOWN);
        }

        if (empty($secret) || empty($signatureMethod)) {
            throw new WalletOneException('Secret key or signature method is not configured', self::ERROR_UNKNOWN);
        }

        if (in_array($signatureMethod, [self::SHA1, self::MD5])) {

            $signature = self::getSignature($secret, $data, $signatureMethod);
            if (!isset($data['WMI_SIGNATURE'])) {
                throw new WalletOneException('Response data does not have signature', self::ERROR_UNKNOWN);
            }
            if ($signature === $data["WMI_SIGNATURE"]) {
                return static::checkPayment($data);
            }

            throw new WalletOneException("Signature is wrong", self::ERROR_SIGNATURE);
        }

        throw new WalletOneException("Wrong signature method", self::ERROR_UNKNOWN);
    }

    /**
     * @param null|string $currency
     * @return int
     * @throws WalletOneException
     */
    public function setCurrency($currency = null)
    {
        $this->currency = $currency ?? $this->currency;

        $currencyExists = isset(self::$CURRENCY_ID[$this->currency]);

        if (!$currencyExists) {
            throw new WalletOneException('WalletOne do not support ' . $this->currency, self::ERROR_HAVE_NOT_CURRENCY);
        }

        return self::$CURRENCY_ID[$this->currency];
    }

    /**
     * @param string $state
     * @param string $description
     * @param int $code
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function response($state = "OK", $description = "", $code = 200)
    {
        $content = "WMI_RESULT=" . strtoupper($state);
        if ($description != "") {
            $content .= "&WMI_DESCRIPTION=" . urlencode($description);
        }

        return response($content, $code);
    }

    /**
     * @throws WalletOneException
     */
    private function resolveWalletOptions(): void
    {
        $options = [
            'WMI_MERCHANT_ID' => $this->merchantID,
            'WMI_CURRENCY_ID' => $this->setCurrency(),
            'WMI_SUCCESS_URL' => $this->successURL,
            'WMI_FAIL_URL' => $this->failURL,
        ];

        $this->walletOptions = $options;
    }

    /**
     * @param array $options
     * @throws \ErrorException
     */
    private function checkOptions(array $options = [])
    {
        $walletOptions = $options ?: $this->walletOptions;

        $required = [
            'WMI_MERCHANT_ID',
            'WMI_CURRENCY_ID',
            'WMI_SUCCESS_URL',
            'WMI_FAIL_URL',
            'WMI_PAYMENT_AMOUNT',
            'WMI_DESCRIPTION',
        ];

        if ($walletOptions) {
            foreach ($required as $key => $val) {
                if (array_key_exists($val, $walletOptions)) {
                    unset($required[$key]);
                }
            }
        }

        if (count($required)) {
            throw new \ErrorException('Error configuration WalletOne, need set - ' . implode(', ', $required));
        }
    }

    /**
     * @param $secretKey
     * @param $data
     * @param string $signatureMethod
     * @return string
     */
    private function getSignature($secretKey, $data, $signatureMethod = self::SHA1)
    {
        unset($data['WMI_SIGNATURE']);

        foreach ($data as $name => $val) {
            if (is_array($val)) {
                usort($val, "strcasecmp");
                $data[$name] = $val;
            }
        }
        uksort($data, "strcasecmp");
        $fieldValues = "";
        foreach ($data as $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $v = iconv("utf-8", "windows-1251", $v);
                    $fieldValues .= urldecode($v);
                }
            } else {
                $value = iconv("utf-8", "windows-1251", $value);
                $fieldValues .= urldecode($value);
            }
        }

        return base64_encode(pack("H*", $signatureMethod($fieldValues . $secretKey)));
    }

    /**
     * @param array $post
     * @return bool
     * @throws WalletOneException
     */
    private function checkPayment(array $post)
    {
        if (isset($post["WMI_ORDER_STATE"]) and strtoupper($post["WMI_ORDER_STATE"]) === "ACCEPTED") {
            return true;
        }

        throw new WalletOneException($post["WMI_ORDER_STATE"] ?? "Unknown order state", self::ERROR_UNKNOWN);
    }

    /**
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private function array_merge_recursive_distinct(array &$array1, array &$array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = self::array_merge_recursive_distinct($merged [$key], $value);
            } else {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }
}

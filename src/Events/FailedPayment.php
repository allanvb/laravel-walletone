<?php

namespace Allanvb\LaravelWalletOne\Events;

class FailedPayment
{
    public $postData = [];
    public $exception;

    public function __construct(array $postData, \Exception $e = null)
    {
        $this->exception = $e;
        $this->postData = $postData;
    }
}
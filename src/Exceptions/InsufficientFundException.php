<?php

namespace Tunv\Wallet\Exceptions;

use Exception;

class InsufficientFundException extends Exception
{
    protected $message = "insufficient fund";

}

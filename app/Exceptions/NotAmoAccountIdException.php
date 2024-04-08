<?php

namespace App\Exceptions;

use Exception;

class NotAmoAccountIdException extends Exception
{
    protected $message = 'Not amoCRM account id';
}

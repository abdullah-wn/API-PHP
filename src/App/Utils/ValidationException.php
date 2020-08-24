<?php
namespace App\Utils;

use Exception;

class ValidationException extends Exception
{
    public function __construct($errors, $code = 0, $previous = null)
    {
        parent::__construct(json_encode($errors), $code, $previous);
    }
}

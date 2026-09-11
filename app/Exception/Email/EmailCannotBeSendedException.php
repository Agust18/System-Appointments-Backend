<?php

namespace App\Exception\Email;

use Exception;

final class EmailCannotBeSendedException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message, 500);
    }
}

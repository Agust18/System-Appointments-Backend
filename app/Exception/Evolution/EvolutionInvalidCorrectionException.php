<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionInvalidCorrectionException extends Exception
{
    public function __construct(string $reason)
    {
        parent::__construct($reason, 422);
    }
}

<?php
namespace App\Exception\DayOff;

use App\Exception\HasErrorCode;
use Exception;

class DayOffException extends Exception implements HasErrorCode
{
    public function errorCode(): string
    {
        return 'DAY_OFF';
    }

    public function __construct()
    {
        parent::__construct(
            'La fecha seleccionada no es un día laborable. Por favor, elija otra fecha.',
            409
        );
    }
}

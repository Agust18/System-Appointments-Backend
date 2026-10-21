<?php
namespace App\Exception\DayOff;

use Exception;

class DayOffOverlapException extends Exception
{
    public function __construct(bool $clinicWide)
    {
        $scope = $clinicWide ? 'la clínica' : 'el médico';
        parent::__construct("Ya hay un día no laborable cargado para {$scope} que se superpone con esas fechas.", 422);
    }
}

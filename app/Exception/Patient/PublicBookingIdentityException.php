<?php
namespace App\Exception\Patient;

use Exception;

/**
 * Error único y deliberadamente neutral de `/consulta`: se devuelve igual si el DNI no existe, el código
 * o el token no coinciden, el turno es de otro paciente o el DNI está bloqueado por demasiados intentos,
 * para que la respuesta no revele cuál de esas cosas pasó.
 */
class PublicBookingIdentityException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'No encontramos un turno con esos datos. Verificá el DNI y el código, o comunicate con el consultorio.',
            404
        );
    }
}

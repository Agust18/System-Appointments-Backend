<?php
namespace App\Exception\Patient;

use Exception;

/**
 * Mensaje único y deliberadamente neutral: la reserva pública lo devuelve tanto
 * cuando el DNI existe con otro teléfono como cuando el email ya es de otro usuario,
 * para que la respuesta no revele cuál de las dos cosas pasó.
 */
class PublicBookingIdentityException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'No pudimos validar tus datos. Si ya sos paciente, ingresá con tu teléfono habitual. Si no, contactá al consultorio.',
            409
        );
    }
}

<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

/**
 * Mail con las credenciales nuevas de `/consulta` cuando recepción regenera el código de un turno.
 */
final class AppointmentAccessCodeEmailTemplate
{
    public function render(AppointmentProjection $projection, ?string $consultaUrl, string $accessCode): string
    {
        $paciente = htmlspecialchars($projection->getPacienteNombre(), ENT_QUOTES, 'UTF-8');
        $medico   = htmlspecialchars($projection->getMedicoNombre(),   ENT_QUOTES, 'UTF-8');
        $fecha    = $projection->getFecha()->format('d/m/Y');
        $hora     = $projection->getHora()->format('H:i');
        $acceso   = AppointmentAccessEmailBlock::render($consultaUrl, $accessCode);

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Nuevo código para tu turno</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4F46E5;">Nuevo código para tu turno</h1>
            <p>Hola, {$paciente}. Generamos un código nuevo para tu turno con {$medico} del {$fecha} a las {$hora}. El código anterior ya no sirve.</p>
            {$acceso}
        </body>
        </html>
        HTML;
    }
}

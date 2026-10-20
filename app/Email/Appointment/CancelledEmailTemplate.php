<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

final class CancelledEmailTemplate
{
    public function render(AppointmentProjection $projection): string
    {
        $paciente = htmlspecialchars($projection->getPacienteNombre(), ENT_QUOTES, 'UTF-8');
        $medico   = htmlspecialchars($projection->getMedicoNombre(),   ENT_QUOTES, 'UTF-8');
        $fecha    = $projection->getFecha()->format('d/m/Y');
        $hora     = $projection->getHora()->format('H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Turno cancelado</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #dc2626;">Turno cancelado</h1>
            <p>Hola, {$paciente}. Lamentamos informarte que tu turno programado para el <strong>{$fecha} a las {$hora}</strong> con <strong>{$medico}</strong> ha sido cancelado.</p>
            <p>Pedimos disculpas por los inconvenientes que esto pueda ocasionarte. Nuestro equipo estará en contacto para coordinar una nueva fecha a la brevedad.</p>
            <p>Si querés reprogramar tu turno de manera inmediata, podés comunicarte con nosotros por nuestros canales de atención.</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #888; font-size: 12px;">
                Gracias por tu comprensión.
            </p>
        </body>
        </html>
        HTML;
    }
}

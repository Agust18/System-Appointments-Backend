<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

final class ReminderEmailTemplate
{
    public function render(AppointmentProjection $projection): string
    {
        $paciente = htmlspecialchars($projection->getPacienteNombre(), ENT_QUOTES, 'UTF-8');
        $medico   = htmlspecialchars($projection->getMedicoNombre(),   ENT_QUOTES, 'UTF-8');
        $servicio = htmlspecialchars($projection->getServicioNombre(), ENT_QUOTES, 'UTF-8');
        $fecha    = $projection->getFecha()->format('d/m/Y');
        $hora     = $projection->getHora()->format('H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Recordatorio de turno</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4F46E5;">Recordatorio de turno</h1>
            <p>Hola, {$paciente}. Te recordamos que mañana tenés un turno programado.</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #eef2ff;">
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Profesional</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$medico}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Servicio</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$servicio}</td>
                </tr>
                <tr style="background-color: #eef2ff;">
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Fecha</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Hora</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$hora}</td>
                </tr>
            </table>
            <p>Por favor, presentate 10 minutos antes de tu turno.</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #888; font-size: 12px;">
                Si necesitás cancelar o reprogramar tu turno, comunicate con nosotros a la brevedad.
            </p>
        </body>
        </html>
        HTML;
    }
}

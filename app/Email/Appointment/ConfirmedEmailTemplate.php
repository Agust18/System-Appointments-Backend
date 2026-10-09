<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

final class ConfirmedEmailTemplate
{
    public function render(AppointmentProjection $projection): string
    {
        $paciente  = htmlspecialchars($projection->getPacienteNombre(), ENT_QUOTES, 'UTF-8');
        $medico    = htmlspecialchars($projection->getMedicoNombre(),   ENT_QUOTES, 'UTF-8');
        $servicio  = htmlspecialchars($projection->getServicioNombre(), ENT_QUOTES, 'UTF-8');
        $fecha     = $projection->getFecha()->format('d/m/Y');
        $hora      = $projection->getHora()->format('H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Turno confirmado</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #16a34a;">Turno confirmado</h1>
            <p>Hola, {$paciente}. Tu turno ha sido confirmado exitosamente.</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #f0fdf4;">
                    <td style="padding: 10px; border: 1px solid #d1fae5; font-weight: bold;">Profesional</td>
                    <td style="padding: 10px; border: 1px solid #d1fae5;">{$medico}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #d1fae5; font-weight: bold;">Servicio</td>
                    <td style="padding: 10px; border: 1px solid #d1fae5;">{$servicio}</td>
                </tr>
                <tr style="background-color: #f0fdf4;">
                    <td style="padding: 10px; border: 1px solid #d1fae5; font-weight: bold;">Fecha</td>
                    <td style="padding: 10px; border: 1px solid #d1fae5;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #d1fae5; font-weight: bold;">Hora</td>
                    <td style="padding: 10px; border: 1px solid #d1fae5;">{$hora}</td>
                </tr>
            </table>
            <p>Por favor, presentate 10 minutos antes de tu turno.</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #888; font-size: 12px;">
                Si tenés alguna consulta, comunicate con nosotros por nuestros canales de atención.
            </p>
        </body>
        </html>
        HTML;
    }
}

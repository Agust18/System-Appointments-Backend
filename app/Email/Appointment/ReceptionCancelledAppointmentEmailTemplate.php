<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

/**
 * Aviso a recepción de un turno que el propio paciente canceló desde `/consulta`.
 */
final class ReceptionCancelledAppointmentEmailTemplate
{
    public function render(AppointmentProjection $projection): string
    {
        $paciente = htmlspecialchars($projection->getPacienteNombre(), ENT_QUOTES, 'UTF-8');
        $medico   = htmlspecialchars($projection->getMedicoNombre(),   ENT_QUOTES, 'UTF-8');
        $servicio = htmlspecialchars($projection->getServicioNombre(), ENT_QUOTES, 'UTF-8');
        $telefono = htmlspecialchars($projection->getTelefono() ?? 'No informado', ENT_QUOTES, 'UTF-8');
        $fecha    = $projection->getFecha()->format('d/m/Y');
        $hora     = $projection->getHora()->format('H:i');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Turno cancelado por el paciente</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #dc2626;">Turno cancelado por el paciente</h1>
            <p>El paciente canceló su turno desde la consulta online. El horario quedó libre.</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #fef2f2;">
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Fecha</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Hora</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$hora}</td>
                </tr>
                <tr style="background-color: #fef2f2;">
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Paciente</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$paciente}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Teléfono</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$telefono}</td>
                </tr>
                <tr style="background-color: #fef2f2;">
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Profesional</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$medico}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #fecaca; font-weight: bold;">Servicio</td>
                    <td style="padding: 10px; border: 1px solid #fecaca;">{$servicio}</td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}

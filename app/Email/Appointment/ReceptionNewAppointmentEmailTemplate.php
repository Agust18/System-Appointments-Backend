<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

/**
 * Aviso a recepción de un turno nacido confirmado por reserva pública.
 */
final class ReceptionNewAppointmentEmailTemplate
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
            <title>Nuevo turno agendado</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #4F46E5;">Nuevo turno agendado</h1>
            <p>Se agendó un nuevo turno desde la reserva online. Ya está confirmado.</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #eef2ff;">
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Fecha</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Hora</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$hora}</td>
                </tr>
                <tr style="background-color: #eef2ff;">
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Paciente</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$paciente}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Teléfono</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$telefono}</td>
                </tr>
                <tr style="background-color: #eef2ff;">
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Profesional</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$medico}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #c7d2fe; font-weight: bold;">Servicio</td>
                    <td style="padding: 10px; border: 1px solid #c7d2fe;">{$servicio}</td>
                </tr>
            </table>
        </body>
        </html>
        HTML;
    }
}

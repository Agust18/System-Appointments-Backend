<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

final class RescheduledEmailTemplate
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
            <title>Turno reprogramado</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1 style="color: #d97706;">Turno reprogramado</h1>
            <p>Hola, {$paciente}. Queremos avisarte que tu turno ha sido reprogramado. Pedimos disculpas por los inconvenientes generados.</p>
            <p>Los nuevos datos de tu turno son:</p>
            <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
                <tr style="background-color: #fffbeb;">
                    <td style="padding: 10px; border: 1px solid #fde68a; font-weight: bold;">Profesional</td>
                    <td style="padding: 10px; border: 1px solid #fde68a;">{$medico}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #fde68a; font-weight: bold;">Servicio</td>
                    <td style="padding: 10px; border: 1px solid #fde68a;">{$servicio}</td>
                </tr>
                <tr style="background-color: #fffbeb;">
                    <td style="padding: 10px; border: 1px solid #fde68a; font-weight: bold;">Nueva fecha</td>
                    <td style="padding: 10px; border: 1px solid #fde68a;">{$fecha}</td>
                </tr>
                <tr>
                    <td style="padding: 10px; border: 1px solid #fde68a; font-weight: bold;">Nueva hora</td>
                    <td style="padding: 10px; border: 1px solid #fde68a;">{$hora}</td>
                </tr>
            </table>
            <p>Ante cualquier consulta o inconveniente, no dudes en comunicarte con nosotros por nuestros canales de atención.</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #888; font-size: 12px;">
                Gracias por tu comprensión y confianza.
            </p>
        </body>
        </html>
        HTML;
    }
}

<?php

namespace App\Email\Appointment;

use App\Entity\Appointment\Projection\AppointmentProjection;

final class AppointmentEmailTemplateRenderer
{
    public function renderConfirmedBody(AppointmentProjection $projection): string
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

    /**
     * Aviso a recepción de un turno nacido confirmado por reserva pública.
     */
    public function renderReceptionNewAppointmentBody(AppointmentProjection $projection): string
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

    public function renderCancelledBody(AppointmentProjection $projection): string
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

    public function renderReminderBody(AppointmentProjection $projection): string
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

    public function renderRescheduledBody(AppointmentProjection $projection): string
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

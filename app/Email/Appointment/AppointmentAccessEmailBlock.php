<?php

namespace App\Email\Appointment;

use App\Services\Appointment\AppointmentAccessService;

/**
 * Bloque HTML de los mails de turno que explica cómo ver o cancelar el turno en `/consulta`.
 * Devuelve '' si no hay link ni código.
 */
final class AppointmentAccessEmailBlock
{
    public static function render(?string $consultaUrl, ?string $accessCode): string
    {
        if ($consultaUrl === null && $accessCode === null) {
            return '';
        }

        $html = '<div style="margin: 20px 0; padding: 15px; background-color: #f9fafb; border: 1px solid #e5e7eb; border-radius: 6px;">'
            . '<p style="margin: 0 0 8px 0; font-weight: bold;">¿Necesitás ver o cancelar tu turno?</p>';

        if ($consultaUrl !== null) {
            $url = htmlspecialchars($consultaUrl, ENT_QUOTES, 'UTF-8');
            $html .= '<p style="margin: 0 0 8px 0;"><a href="' . $url . '">Ingresá desde este link</a>.</p>';
        }

        if ($accessCode !== null) {
            $code = htmlspecialchars(AppointmentAccessService::formatCode($accessCode), ENT_QUOTES, 'UTF-8');
            $html .= '<p style="margin: 0;">También podés hacerlo desde la página de consulta con tu DNI y este código: '
                . '<strong style="font-size: 18px; letter-spacing: 2px;">' . $code . '</strong></p>';
        }

        return $html . '</div>';
    }
}

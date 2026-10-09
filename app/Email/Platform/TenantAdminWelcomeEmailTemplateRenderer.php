<?php

namespace App\Email\Platform;

final class TenantAdminWelcomeEmailTemplateRenderer
{
    public function renderWelcomeBody(string $firstName, string $tenantName, string $url, int $ttlHours): string
    {
        $firstName  = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
        $tenantName = htmlspecialchars($tenantName, ENT_QUOTES, 'UTF-8');
        $url        = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Bienvenido a {$tenantName}</title>
    </head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Hola, {$firstName}</h1>
        <p>Tu cuenta de administrador de <strong>{$tenantName}</strong> ya está creada. Elegí tu contraseña desde el siguiente enlace:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{$url}"
               style="background-color: #4F46E5; color: white; padding: 12px 24px;
                      text-decoration: none; border-radius: 6px; display: inline-block;">
                Elegir mi contraseña
            </a>
        </p>
        <p>O copiá y pegá este link en tu navegador:</p>
        <p style="word-break: break-all; color: #666;">{$url}</p>
        <p>Este link expira en {$ttlHours} horas.</p>
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #888; font-size: 12px;">
            Si no esperabas este mensaje, podés ignorarlo de manera segura.
        </p>
    </body>
    </html>
    HTML;
    }
}

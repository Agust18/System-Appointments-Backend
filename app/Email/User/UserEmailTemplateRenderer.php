<?php

namespace App\Email\User;

use App\Entity\User\User;

final class UserEmailTemplateRenderer
{
    public function renderVerificationBody(User $user, string $verificationUrl): string
    {
        $firstName = htmlspecialchars($user->getFirstName(), ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($verificationUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Verificá tu cuenta</title>
        </head>
        <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
            <h1>Hola, {$firstName}</h1>
            <p>Gracias por registrarte. Para activar tu cuenta, hacé clic en el siguiente link:</p>
            <p style="text-align: center; margin: 30px 0;">
                <a href="{$url}"
                   style="background-color: #4F46E5; color: white; padding: 12px 24px;
                          text-decoration: none; border-radius: 6px; display: inline-block;">
                    Verificar mi cuenta
                </a>
            </p>
            <p>O copiá y pegá este link en tu navegador:</p>
            <p style="word-break: break-all; color: #666;">{$url}</p>
            <p>Este link expira en 24 horas.</p>
            <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
            <p style="color: #888; font-size: 12px;">
                Si no creaste esta cuenta, ignorá este mensaje.
            </p>
        </body>
        </html>
        HTML;
    }
}
<?php
namespace App\Email\User;
use App\Entity\User\User;
final class UserEmailTemplateRendererPassword
{
    public function __construct()
    {
    }
    public function renderPasswordResetBody(User $user, string $resetUrl): string
    {
        $username = htmlspecialchars($user->getUsername(), ENT_QUOTES, 'UTF-8');
        $url = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

        return <<<HTML
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Restablecer contraseña</title>
    </head>
    <body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1>Hola, {$username}</h1>
        <p>Recibimos una solicitud para restablecer tu contraseña. Hacé clic en el siguiente enlace para continuar:</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{$url}"
               style="background-color: #4F46E5; color: white; padding: 12px 24px;
                      text-decoration: none; border-radius: 6px; display: inline-block;">
                Restablecer contraseña
            </a>
        </p>
        <p>O copiá y pegá este link en tu navegador:</p>
        <p style="word-break: break-all; color: #666;">{$url}</p>
        <p>Este link expira en 1 hora.</p>
        <hr style="margin: 30px 0; border: none; border-top: 1px solid #eee;">
        <p style="color: #888; font-size: 12px;">
            Si no solicitaste este cambio, podés ignorar este mensaje de manera segura.
        </p>
    </body>
    </html>
    HTML;
    }


}
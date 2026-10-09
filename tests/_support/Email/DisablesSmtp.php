<?php

namespace Tests\Support\Email;

/**
 * El entorno de tests hereda las credenciales SMTP del .env. Con `EMAIL_HOST` vacío, `EmailModel`
 * devuelve SMTP_CONFIG_ERROR antes de conectarse: todo envío falla rápido y nunca sale un mail real.
 *
 * Llamar a disableSmtp() en setUp() y a restoreSmtp() en tearDown().
 */
trait DisablesSmtp
{
    private mixed $previousEmailHost = null;

    protected function disableSmtp(): void
    {
        $this->previousEmailHost = $_ENV['EMAIL_HOST'] ?? null;
        $_ENV['EMAIL_HOST'] = '';
    }

    protected function restoreSmtp(): void
    {
        if ($this->previousEmailHost === null) {
            unset($_ENV['EMAIL_HOST']);
        } else {
            $_ENV['EMAIL_HOST'] = $this->previousEmailHost;
        }
    }
}

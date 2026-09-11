<?php

namespace App\Dto\Response\Email;

enum EmailResponse: int
{
    case OK = 1;

    case INVALID_EMAIL = 2;
    case EMPTY_SUBJECT = 3;
    case EMPTY_BODY = 4;

    case ATTACHMENT_NOT_FOUND = 5;
    case ATTACHMENT_TOO_LARGE = 6;
    case ATTACHMENT_INVALID_TYPE = 7;

    case SMTP_CONFIG_ERROR = 8;
    case SMTP_AUTH_ERROR = 9;
    case SMTP_SEND_ERROR = 10;

    case UNKNOWN_ERROR = 99;

    public function message(): string
    {
        return match ($this) {
            self::OK => 'Email enviado correctamente.',

            self::INVALID_EMAIL => 'La dirección de email no es válida.',
            self::EMPTY_SUBJECT => 'El asunto del email no puede estar vacío.',
            self::EMPTY_BODY => 'El contenido del email no puede estar vacío.',

            self::ATTACHMENT_NOT_FOUND => 'No se encontró uno de los archivos adjuntos.',
            self::ATTACHMENT_TOO_LARGE => 'Uno de los adjuntos excede el tamaño máximo permitido.',
            self::ATTACHMENT_INVALID_TYPE => 'Tipo de archivo adjunto no permitido.',

            self::SMTP_CONFIG_ERROR => 'Error en la configuración del servidor SMTP.',
            self::SMTP_AUTH_ERROR => 'Error de autenticación con el servidor SMTP.',
            self::SMTP_SEND_ERROR => 'No se pudo enviar el email.',

            self::UNKNOWN_ERROR => 'Ocurrió un error inesperado al enviar el email.',
        };
    }

    public function isError(): bool
    {
        return $this->value !== self::OK->value;
    }
}
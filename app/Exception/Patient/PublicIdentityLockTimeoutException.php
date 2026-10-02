<?php
namespace App\Exception\Patient;

use Exception;

class PublicIdentityLockTimeoutException extends Exception
{
    public function __construct()
    {
        parent::__construct(
            'Estamos procesando otra solicitud con tus datos. Por favor, intentá nuevamente en unos segundos.',
            423
        );
    }
}

<?php
namespace App\Exception;

/**
 * Excepción con un código de negocio estable para el cliente.
 * ApiExceptionHandler lo agrega al body como `code`, para que el front no dependa del texto del mensaje.
 */
interface HasErrorCode
{
    public function errorCode(): string;
}

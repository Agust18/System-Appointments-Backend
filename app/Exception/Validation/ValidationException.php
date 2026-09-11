<?php

namespace App\Exception\Validation;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /**
     * @param array<string, string> $errors mapa campo => mensaje
     */
    public function __construct(private array $errors)
    {
        parent::__construct('Datos invalidos', 422);
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}

<?php
namespace App\Validation;

use App\Exception\Validation\ValidationException;
use Config\Services;

final class RequestValidator
{
    public static function validate(array $parameters, array $rules, array $messages = []): void
    {
        $validation = Services::validation();
        $validation->reset();
        $validation->setRules($rules, $messages);

        // Las reglas estrictas de CI4 rechazan bool nativo (JSON true/false); se validan como 0/1.
        foreach ($parameters as $field => $value) {
            if (is_bool($value)) {
                $parameters[$field] = (int) $value;
            }
        }

        if ($validation->run($parameters)) {
            return;
        }

        $errors = [];
        foreach ($validation->getErrors() as $field => $message) {
            $errors[$field] = $message;
        }

        throw new ValidationException($errors);
    }
}
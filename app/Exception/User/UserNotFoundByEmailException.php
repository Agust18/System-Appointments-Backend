<?php 

namespace App\Exception\User;

use Exception;

class UserNotFoundByEmailException extends Exception {
    public function __construct(string $email) {
        parent::__construct("No se encontro el user con email: $email", 404);
    }
}
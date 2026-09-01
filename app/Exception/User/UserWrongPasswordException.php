<?php 

namespace App\Exception\User;

use Exception;

class UserWrongPasswordException extends Exception {
    public function __construct() {
        parent::__construct("Contraseña incorrecta", 500);
    }
}
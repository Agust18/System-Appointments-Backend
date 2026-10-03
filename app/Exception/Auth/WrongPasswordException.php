<?php 

namespace App\Exception\Auth;

use Exception;

class WrongPasswordException extends Exception {
    public function __construct() {
        parent::__construct("Email o contraseña incorrectos", 401);
    }
}
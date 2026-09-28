<?php 

namespace App\Exception\Auth;

use Exception;

class WrongPasswordException extends Exception {
    public function __construct() {
        parent::__construct("La contraseña es incorrecta,intente de nuevo", 401);
    }
}
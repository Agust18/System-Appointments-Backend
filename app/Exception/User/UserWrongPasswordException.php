<?php 

namespace App\Exception\User;

use Exception;

class UserWrongPasswordException extends Exception {
    public function __construct() {
        parent::__construct("La contraseña es incorrecta,intente de nuevo", 401);
    }
}
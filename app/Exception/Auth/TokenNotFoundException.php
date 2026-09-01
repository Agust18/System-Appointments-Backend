<?php 

namespace App\Exception\Auth;

use RuntimeException;

final class TokenNotFoundException extends RuntimeException {
    public function __construct() {
        parent::__construct("Token no encontrado", 401);
    }
}
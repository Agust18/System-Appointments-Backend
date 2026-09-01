<?php

namespace App\Exception\Medical;

use Exception;

class MedicalNotFoundException extends Exception
{
    //constructor padre que recibe el mensaje de error y 
    //lo pasa al constructor de la clase Exception.

    public function __construct(int $id)
    {
        //le pasamos el mensaje de error al constructor de la clase Exception.
        parent::__construct("No se encontro el medico con ID : " . $id, 404);
    }
}

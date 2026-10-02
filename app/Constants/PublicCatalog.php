<?php

namespace App\Constants;

final class PublicCatalog
{
    /**
     * Tope fijo de los listados públicos (especialidades, médicos, servicios). Son catálogos
     * chicos y no se pagina: aceptar page/size de un cliente anónimo permitiría volcar todo
     * con un size enorme o romper el SQL con uno negativo.
     */
    public const MAX_ITEMS = 100;
}

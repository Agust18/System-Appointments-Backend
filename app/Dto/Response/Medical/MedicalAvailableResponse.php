<?php

namespace App\Dto\Response\Medical;

final readonly class MedicalAvailableResponse
{
    public function __construct(
        public int $id,
        public string $medicoNombre,
        public string $especialidadNombre
    ) {
    }
}

<?php
namespace App\Dto\Response\Medical\Projection;

final readonly class MedicalProjectionResponse
{
    public function __construct(
        // IDs (para operaciones)
        public int $id,
        public int $userId,
        public int $specialtyId,
        
        // Proyecciones (nombres para mostrar)
        public string $medicoNombre,
        public ?string $email,
        public string $especialidadNombre,
        
        // Datos del médico
        public string $tuition,
        public ?string $phone,
        public bool $isActive,
        public ?string $createdAt,
        public ?string $updatedAt
    ) {}
}
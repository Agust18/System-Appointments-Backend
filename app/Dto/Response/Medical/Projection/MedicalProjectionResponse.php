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
        public ?string $phone,
        public string $especialidadNombre,
        
        // Datos del médico
        public string $tuition,
        public ?string $createdAt,
        public ?string $updatedAt,

        // Estado del usuario del médico (users.is_active)
        public bool $userIsActive = true
    ) {}
}
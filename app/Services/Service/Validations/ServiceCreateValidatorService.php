<?php
// app/Services/Service/Validations/ServiceCreateValidatorService.php

namespace App\Services\Service\Validations;

use App\Dto\Request\Service\ServiceRequest;
use App\Models\Service\ServiceModel;

final class ServiceCreateValidatorService
{
    private ServiceModel $serviceModel;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
    }

    public function validate(ServiceRequest $request): void
    {
        // 1️⃣ VALIDAR NOMBRE
        if (empty($request->getName())) {
            throw new \InvalidArgumentException("El nombre del servicio es requerido");
        }

        if (strlen($request->getName()) < 3) {
            throw new \InvalidArgumentException("El nombre debe tener al menos 3 caracteres");
        }

        if (strlen($request->getName()) > 100) {
            throw new \InvalidArgumentException("El nombre no puede tener más de 100 caracteres");
        }

        // 2️⃣ VALIDAR DESCRIPCIÓN
        if (empty($request->getDescription())) {
            throw new \InvalidArgumentException("La descripción del servicio es requerida");
        }

        if (strlen($request->getDescription()) > 500) {
            throw new \InvalidArgumentException("La descripción no puede tener más de 500 caracteres");
        }

        // 3️⃣ VALIDAR DURACIÓN
        if ($request->getDurationMinutes() <= 0) {
            throw new \InvalidArgumentException("La duración debe ser mayor a 0 minutos");
        }

        if ($request->getDurationMinutes() > 480) {
            throw new \InvalidArgumentException("La duración no puede exceder 480 minutos (8 horas)");
        }

        // 4️⃣ VALIDAR BUFFER
        if ($request->getBufferMinutes() < 0) {
            throw new \InvalidArgumentException("El tiempo de buffer no puede ser negativo");
        }

        if ($request->getBufferMinutes() > 120) {
            throw new \InvalidArgumentException("El tiempo de buffer no puede exceder 120 minutos");
        }

        // 5️⃣ VALIDAR PRECIO (UNA SOLA VEZ)
        if ($request->getPrice() < 0) {
            throw new \InvalidArgumentException("El precio no puede ser negativo");
        }

        if ($request->getPrice() > 999999.99) {
            throw new \InvalidArgumentException("El precio no puede exceder 999,999.99");
        }

        // 6️⃣ VALIDAR SLOT STEP
        if ($request->getSlotStepMinutes() <= 0) {
            throw new \InvalidArgumentException("El intervalo debe ser mayor a 0 minutos");
        }

        if ($request->getSlotStepMinutes() > 60) {
            throw new \InvalidArgumentException("El intervalo no puede exceder 60 minutos");
        }

        // 7️⃣ 🔍 VALIDAR DUPLICADO POR NOMBRE
        $existingService = $this->serviceModel->findByName($request->getName());
        
        if ($existingService) {
            throw new \InvalidArgumentException("El servicio '{$request->getName()}' ya existe");
        }
    }
}
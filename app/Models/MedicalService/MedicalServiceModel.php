<?php
namespace App\Models\MedicalService;

use App\Converter\MedicalService\PrimitiveToMedicalServiceConverter;
use App\Converter\MedicalService\Projection\PrimitiveToMedicalServiceProjectionConverter;
use App\Entity\MedicalService\MedicalService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class MedicalServiceModel
{
    private BaseConnection $db;
    private PrimitiveToMedicalServiceConverter $converter;
    private PrimitiveToMedicalServiceProjectionConverter $projectionConverter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToMedicalServiceConverter();
        $this->projectionConverter = new PrimitiveToMedicalServiceProjectionConverter();
    }

    public function insertOrUpdate(MedicalService $medicalService): MedicalService
    {
        $query = "INSERT INTO medical_service (medical_id, service_id, custom_price, custom_duration_minutes, is_active, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                  ON DUPLICATE KEY UPDATE 
                    custom_price = VALUES(custom_price),
                    custom_duration_minutes = VALUES(custom_duration_minutes),
                    is_active = VALUES(is_active),
                    updated_at = NOW()";

        $this->db->query($query, [
            $medicalService->getMedicalId(),
            $medicalService->getServiceId(),
            $medicalService->getCustomPrice(),
            $medicalService->getCustomDurationMinutes(),
            $medicalService->isActive()
        ]);

        return $this->findByMedicalAndService($medicalService->getMedicalId(), $medicalService->getServiceId());
    }

    public function findByMedicalAndService(int $medicalId, int $serviceId): ?MedicalService
    {
        $query = "SELECT * FROM medical_service WHERE medical_id = ? AND service_id = ?";
        $result = $this->db->query($query, [$medicalId, $serviceId]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    /**
     * Fuente de verdad para saber si un médico puede ser reservado con este
     * servicio: exige que TANTO la relación como el servicio estén activos.
     */
    public function findActiveByMedicalAndService(int $medicalId, int $serviceId): ?MedicalService
    {
        $query = "SELECT ms.* 
                  FROM medical_service ms
                  INNER JOIN services s ON s.id = ms.service_id
                  WHERE ms.medical_id = ? 
                    AND ms.service_id = ? 
                    AND ms.is_active = 1 
                    AND s.is_active = 1";

        $result = $this->db->query($query, [$medicalId, $serviceId]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }

    /**
     * Proyección con JOIN para listar los servicios (con duración/precio
     * efectivos) que un médico tiene asignados y activos.
     */
    public function findServicesByMedicalIdProjection(int $medicalId): array
    {
        $query = "SELECT 
                    s.id as service_id,
                    s.name,
                    s.description,
                    s.duration_minutes as base_duration_minutes,
                    s.buffer_minutes,
                    s.price as base_price,
                    s.slot_step_minutes,
                    ms.id as medical_service_id,
                    ms.medical_id,
                    ms.custom_price,
                    ms.custom_duration_minutes,
                    COALESCE(ms.custom_duration_minutes, s.duration_minutes) as effective_duration_minutes,
                    COALESCE(ms.custom_price, s.price) as effective_price,
                    ms.is_active
                  FROM services s
                  INNER JOIN medical_service ms ON ms.service_id = s.id
                  WHERE ms.medical_id = ? AND ms.is_active = 1 AND s.is_active = 1
                  ORDER BY s.name ASC";

        $result = $this->db->query($query, [$medicalId]);
        $primitives = $result->getResult();

        $projections = [];
        foreach ($primitives as $primitive) {
            $projections[] = $this->projectionConverter->convert($primitive);
        }

        return $projections;
    }

    public function delete(int $medicalId, int $serviceId): void
    {
        $query = "DELETE FROM medical_service WHERE medical_id = ? AND service_id = ?";
        $this->db->query($query, [$medicalId, $serviceId]);
    }
}
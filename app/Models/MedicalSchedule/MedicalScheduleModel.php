<?php
namespace App\Models\MedicalSchedule;

use App\Converter\MedicalSchedule\PrimitiveToMedicalScheduleConverter;
use App\Converter\MedicalSchedule\Projection\PrimitiveToMedicalScheduleProjectionConverter;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Entity\MedicalSchedule\Projection\MedicalScheduleProjection;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

final class MedicalScheduleModel
{
    private BaseConnection $db;
    private PrimitiveToMedicalScheduleConverter $converter;
    private PrimitiveToMedicalScheduleProjectionConverter $converterProjection;
    public function __construct()
    {
        $this->db = Database::connect();
        $this->converter = new PrimitiveToMedicalScheduleConverter();
        $this->converterProjection = new PrimitiveToMedicalScheduleProjectionConverter();
    }

    public function insert(MedicalSchedule $schedule): MedicalSchedule
    {
        $query = "INSERT INTO medical_schedules (medical_id, day_of_week, start_time, end_time, is_active, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";

        $this->db->query($query, [
            $schedule->getMedicalId(),
            $schedule->getDayOfWeek(),
            $schedule->getStartTime(),
            $schedule->getEndTime(),
            $schedule->isActive()
        ]);

        $id = $this->db->insertID();
        return new MedicalSchedule(
            $id,
            $schedule->getMedicalId(),
            $schedule->getDayOfWeek(),
            $schedule->getStartTime(),
            $schedule->getEndTime(),
            $schedule->isActive(),
            new DateTime(),
            new DateTime()
        );
    }

    public function update(MedicalSchedule $schedule): MedicalSchedule
    {
        $query = "UPDATE medical_schedules 
                  SET medical_id = ?, day_of_week = ?, start_time = ?, end_time = ?, is_active = ?, updated_at = NOW() 
                  WHERE id = ?";

        $this->db->query($query, [
            $schedule->getMedicalId(),
            $schedule->getDayOfWeek(),
            $schedule->getStartTime(),
            $schedule->getEndTime(),
            $schedule->isActive(),
            $schedule->getId()
        ]);

        return $this->find($schedule->getId());
    }

    public function find(int $id): ?MedicalSchedule
    {
        $query = "SELECT * FROM medical_schedules WHERE id = ?";
        $result = $this->db->query($query, [$id]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }
        return $this->converter->convert($primitive);
    }

    public function findProjection(int $id): ?MedicalScheduleProjection
    {
        $query = "SELECT 
                ms.id, 
                ms.medical_id, 
                u.username as medico_nombre,
                ms.day_of_week, 
                ms.start_time, 
                ms.end_time, 
                ms.is_active,
                ms.created_at, 
                ms.updated_at
              FROM medical_schedules ms
              INNER JOIN medicals m ON m.id = ms.medical_id
              INNER JOIN users u ON u.id = m.user_id
              WHERE ms.id = ?";

        $result = $this->db->query($query, [$id]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converterProjection->convert($primitive);
    }
    public function findOverlapping(
        int $medicalId,
        int $dayOfWeek,
        string $startTime,
        string $endTime
    ): ?MedicalSchedule {
        $query = "SELECT * FROM medical_schedules 
              WHERE medical_id = ? 
                AND day_of_week = ? 
                AND is_active = 1
                AND start_time < ?    -- el existente empieza antes de que termine el nuevo
                AND end_time > ?      -- el existente termina después de que empiece el nuevo
              LIMIT 1";

        $result = $this->db->query($query, [
            $medicalId,
            $dayOfWeek,
            $endTime,
            $startTime,
        ]);

        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }
    public function findByMedicalId(int $medicalId): array
    {
        $query = "SELECT * FROM medical_schedules WHERE medical_id = ? ORDER BY day_of_week ASC";
        $result = $this->db->query($query, [$medicalId]);
        $primitives = $result->getResult();

        $schedules = [];
        foreach ($primitives as $primitive) {
            $schedules[] = $this->converter->convert($primitive);
        }
        return $schedules;
    }


    public function findByMedicalIdProjection(int $medicalId): array
    {
        $query = "SELECT 
                ms.id, 
                ms.medical_id, 
                u.username as medico_nombre,
                ms.day_of_week, 
                ms.start_time, 
                ms.end_time, 
                ms.is_active,
                ms.created_at, 
                ms.updated_at
              FROM medical_schedules ms
              INNER JOIN medicals m ON m.id = ms.medical_id
              INNER JOIN users u ON u.id = m.user_id
              WHERE ms.medical_id = ?
              ORDER BY ms.day_of_week";

        $result = $this->db->query($query, [$medicalId]);
        $primitives = $result->getResult();

        $schedules = [];
        foreach ($primitives as $primitive) {
            $schedules[] = $this->converterProjection->convert($primitive);
        }

        return $schedules;
    }

    public function findByMedicalAndDay(int $medicalId, int $dayOfWeek): ?MedicalSchedule
    {
        $query = "SELECT * FROM medical_schedules WHERE medical_id = ? AND day_of_week = ? AND is_active = 1";
        $result = $this->db->query($query, [$medicalId, $dayOfWeek]);
        $primitive = $result->getRow();

        if (!$primitive) {
            return null;
        }

        return $this->converter->convert($primitive);
    }

    public function findActiveRangesByMedicalAndDay(int $medicalId, int $dayOfWeek): array
    {
        $query = "SELECT * FROM medical_schedules
                  WHERE medical_id = ?
                    AND day_of_week = ?
                    AND is_active = 1
                  ORDER BY start_time ASC";
        $result = $this->db->query($query, [$medicalId, $dayOfWeek]);
        $primitives = $result->getResult();

        $schedules = [];
        foreach ($primitives as $primitive) {
            $schedules[] = $this->converter->convert($primitive);
        }

        return $schedules;
    }

    /**
     * Igual que findActiveRangesByMedicalAndDay pero para varios días de la
     * semana en una sola query (usado por el endpoint batch de disponibilidad
     * para no hacer una query por día).
     *
     * @param int[] $daysOfWeek
     * @return \App\Entity\MedicalSchedule\MedicalSchedule[]
     */
    public function findActiveRangesByMedicalAndDays(int $medicalId, array $daysOfWeek): array
    {
        if (empty($daysOfWeek)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($daysOfWeek), '?'));
        $query = "SELECT * FROM medical_schedules
                  WHERE medical_id = ?
                    AND day_of_week IN ({$placeholders})
                    AND is_active = 1
                  ORDER BY day_of_week ASC, start_time ASC";

        $params = array_merge([$medicalId], $daysOfWeek);
        $result = $this->db->query($query, $params);
        $primitives = $result->getResult();

        $schedules = [];
        foreach ($primitives as $primitive) {
            $schedules[] = $this->converter->convert($primitive);
        }

        return $schedules;
    }




    public function delete(int $id): void
    {
        $query = "DELETE FROM medical_schedules WHERE id = ?";
        $this->db->query($query, [$id]);
    }

    public function searchProjection(MedicalScheduleFilterRequest $request): array
    {
        $parameters = [];

        $query = "SELECT 
                ms.id, 
                ms.medical_id, 
                u.username as medico_nombre,
                ms.day_of_week, 
                ms.start_time, 
                ms.end_time, 
                ms.is_active,
                ms.created_at, 
                ms.updated_at
              FROM medical_schedules ms
              INNER JOIN medicals m ON m.id = ms.medical_id
              INNER JOIN users u ON u.id = m.user_id
              WHERE 1=1 ";

        if ($request->hasMedicalId()) {
            $query .= " AND ms.medical_id = ? ";
            $parameters[] = $request->getMedicalId();
        }

        if ($request->hasDayOfWeek()) {
            $query .= " AND ms.day_of_week = ? ";
            $parameters[] = $request->getDayOfWeek();
        }

        if ($request->hasIsActive()) {
            $query .= " AND ms.is_active = ? ";
            $parameters[] = $request->getIsActive() ? 1 : 0;
        }

        if ($request->hasSearch()) {
            $query .= " AND u.username LIKE ? ";
            $parameters[] = "%" . $request->getSearch() . "%";
        }

        $query .= " ORDER BY ms.medical_id ASC, ms.day_of_week ASC ";

        $paginationQuery = "LIMIT ?, ? ";
        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $fullQuery = $query . $paginationQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $primitives = $result->getResult();

        $schedules = [];
        foreach ($primitives as $primitive) {
            $schedules[] = $this->converterProjection->convert($primitive);
        }

        return $schedules;
    }

    public function count(MedicalScheduleFilterRequest $request): int
    {
        $parameters = [];

        $query = "SELECT COUNT(*) as total FROM medical_schedules WHERE 1=1 ";

        if ($request->hasMedicalId()) {
            $query .= " AND medical_id = ? ";
            $parameters[] = $request->getMedicalId();
        }

        if ($request->hasDayOfWeek()) {
            $query .= " AND day_of_week = ? ";
            $parameters[] = $request->getDayOfWeek();
        }

        if ($request->hasIsActive()) {
            $query .= " AND is_active = ? ";
            $parameters[] = $request->getIsActive() ? 1 : 0;
        }

        $result = $this->db->query($query, $parameters);
        $row = $result->getRow();

        return (int) $row->total;
    }


}
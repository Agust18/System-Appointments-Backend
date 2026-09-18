<?php

namespace App\Models\Appointment;

use App\Converter\Appointment\PrimitiveToAppointmentConverter;
use App\Converter\Appointment\Projection\PrimitiveToAppointmentProjectionConverter;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Entity\Appointment\Appointment;
use App\Entity\Appointment\Projection\AppointmentProjection;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class AppointmentModel
{
    private BaseConnection $db;
    private PrimitiveToAppointmentConverter $converter;
    private PrimitiveToAppointmentProjectionConverter $projectionConverter;

    public function __construct()
    {
        $this->converter = new PrimitiveToAppointmentConverter();
        $this->db = Database::connect();
        $this->projectionConverter = new PrimitiveToAppointmentProjectionConverter();
    }

    public function insert(Appointment $appointment): Appointment
    {
        $query = "INSERT INTO appointments (medical_id, patient_id, service_id, fecha, hora, hora_fin, nota, status, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($query, [
            $appointment->getMedicalId(),
            $appointment->getPatientId(),
            $appointment->getServiceId(),
            $appointment->getFecha()->format('Y-m-d'),
            $appointment->getHora()->format('H:i:s'),
            $appointment->getHoraFin() ? $appointment->getHoraFin()->format('H:i:s') : null,
            $appointment->getNota(),
            $appointment->getStatus(),
            $appointment->getCreatedAt()->format('Y-m-d H:i:s'),
            $appointment->getUpdatedAt()->format('Y-m-d H:i:s')
        ]);

        $id = $this->db->insertID();

        return $this->find($id);
    }

    /**
     * Fórmula de intersección: Slot_Inicio < Turno_Fin AND Slot_Fin > Turno_Inicio.
     * $excludeAppointmentId se usa al EDITAR: el propio turno que se está
     * editando no debe chocar contra sí mismo en el chequeo.
     */
    public function hasOverlap(int $medicalId, string $fecha, string $horaInicio, string $horaFin, ?int $excludeAppointmentId = null): bool
    {
        $query = "SELECT COUNT(*) as total 
                  FROM appointments 
                  WHERE medical_id = ? 
                    AND fecha = ? 
                    AND status IN ('pending', 'confirmed', 'completed')
                    AND hora < ? 
                    AND COALESCE(hora_fin, ADDTIME(hora, '00:30:00')) > ?";

        $params = [$medicalId, $fecha, $horaFin, $horaInicio];

        if ($excludeAppointmentId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }

        $result = $this->db->query($query, $params);
        $row = $result->getRow();

        return $row && (int) $row->total > 0;
    }

    public function update(Appointment $appointment): Appointment
    {
        $query = "UPDATE appointments 
                  SET medical_id = ?, patient_id = ?, service_id = ?, fecha = ?, hora = ?, hora_fin = ?, nota = ?, status = ?, updated_at = ? 
                  WHERE id = ?";

        $this->db->query($query, [
            $appointment->getMedicalId(),
            $appointment->getPatientId(),
            $appointment->getServiceId(),
            $appointment->getFecha()->format('Y-m-d'),
            $appointment->getHora()->format('H:i:s'),
            $appointment->getHoraFin()->format('H:i:s'),
            $appointment->getNota(),
            $appointment->getStatus(),
            $appointment->getUpdatedAt()->format('Y-m-d H:i:s'),
            $appointment->getId()
        ]);

        return $this->find($appointment->getId());
    }

    public function find(int $id): ?Appointment
    {
        $query = "SELECT * FROM appointments WHERE id = ?";
        $result = $this->db->query($query, [$id]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }


    public function findByMedicalId(int $medicalId): array
    {
        $query = "SELECT * FROM appointments WHERE medical_id = ? ORDER BY fecha DESC, hora DESC";
        $result = $this->db->query($query, [$medicalId]);
        $primitives = $result->getResult();

        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function findByPatientId(int $patientId): array
    {
        $query = "SELECT * FROM appointments WHERE patient_id = ? ORDER BY fecha DESC, hora DESC";
        $result = $this->db->query($query, [$patientId]);
        $primitives = $result->getResult();

        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM appointments WHERE id = ?";
        $this->db->query($query, [$id]);
    }


    public function findByPatientUserId(int $userId): array
    {
        $query = "SELECT a.* FROM appointments a 
              INNER JOIN patients p ON p.id = a.patient_id 
              WHERE p.user_id = ?";
        $result = $this->db->query($query, [$userId]);
        $primitives = $result->getResult();
        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function findByMedicalUserId(int $userId): array
    {
        $query = "SELECT a.* FROM appointments a 
              INNER JOIN medicals m ON m.id = a.medical_id 
              WHERE m.user_id = ?";
        $result = $this->db->query($query, [$userId]);
        $primitives = $result->getResult();
        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    /**
     * Turnos activos (no cancelados) de un médico en una fecha, con
     * hora/hora_fin ya resueltos (fallback +30 min si hora_fin viniera
     * NULL). Usado por AppointmentAvailabilityService para chequear
     * solapamiento contra cada slot candidato sin repetir el query por
     * cada uno.
     */
    public function findActiveHoursByMedicalAndFecha(int $medicalId, string $fecha): array
    {
        $query = "SELECT hora, COALESCE(hora_fin, ADDTIME(hora, '00:30:00')) as hora_fin
                  FROM appointments
                  WHERE medical_id = ?
                    AND fecha = ?
                    AND status IN ('pending', 'confirmed', 'completed')";

        $result = $this->db->query($query, [$medicalId, $fecha]);

        return $result->getResult();
    }



    public function searchProjection(AppointmentFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $selectQuery = "SELECT 
    A.id, A.medical_id, A.patient_id, A.fecha, A.hora, A.nota, A.status,
    A.service_id,
    SRV.name as servicio_nombre,
    U_med.username as medico_nombre,
    U_pac.username as paciente_nombre
";


        $fromQuery = "FROM appointments A 
      INNER JOIN medicals M ON M.id = A.medical_id
      INNER JOIN users U_med ON U_med.id = M.user_id
      INNER JOIN patients P ON P.id = A.patient_id
      INNER JOIN users U_pac ON U_pac.id = P.user_id
      INNER JOIN services SRV ON SRV.id = A.service_id
      ";
        $orderQuery = "ORDER BY A.fecha DESC, A.hora DESC ";
        $paginationQuery = "LIMIT ?, ? ";

        $parameters[] = $request->getPagination()->getOffset();
        $parameters[] = $request->getPagination()->getLimit();

        $fullQuery = $selectQuery . $fromQuery . $whereQuery . $orderQuery . $paginationQuery;

        $result = $this->db->query($fullQuery, $parameters);
        $primitives = $result->getResult();

        $entities = [];
        foreach ($primitives as $primitive) {
            $entities[] = $this->projectionConverter->convert($primitive);
        }

        return $entities;
    }

    public function countProjection(AppointmentFilterRequest $request): int
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT COUNT(*) as total 
              FROM appointments A 
              INNER JOIN medicals M ON M.id = A.medical_id
              INNER JOIN users U_med ON U_med.id = M.user_id
              INNER JOIN specialties S ON S.id = M.specialty_id
              INNER JOIN patients P ON P.id = A.patient_id
              INNER JOIN users U_pac ON U_pac.id = P.user_id
              {$whereQuery}";

        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    private function buildWhereClause(AppointmentFilterRequest $request): array
    {
        $parameters = [];

        $whereQuery = "WHERE 1 = 1 ";

        if ($request->hasMedicalId()) {
            $whereQuery .= " AND A.medical_id = ? ";
            $parameters[] = $request->getMedicalId();
        }

        if ($request->hasPatientId()) {
            $whereQuery .= " AND A.patient_id = ? ";
            $parameters[] = $request->getPatientId();
        }

        if ($request->hasStatus()) {
            $whereQuery .= " AND A.status = ? ";
            $parameters[] = $request->getStatus();
        }

        if ($request->hasFecha()) {
            $whereQuery .= " AND A.fecha = ? ";
            $parameters[] = $request->getFecha();
        }

        if ($request->hasSearch()) {
            $whereQuery .= " AND (U_pac.username LIKE ? OR U_med.username LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        return [$whereQuery, $parameters];
    }

    //stats
    public function statsByStatus(AppointmentFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT A.status, COUNT(*) as total 
              FROM appointments A 
              INNER JOIN medicals M ON M.id = A.medical_id
              INNER JOIN users U_med ON U_med.id = M.user_id
              INNER JOIN patients P ON P.id = A.patient_id
              INNER JOIN users U_pac ON U_pac.id = P.user_id
              {$whereQuery}
              GROUP BY A.status";

        $result = $this->db->query($query, $parameters);

        // Arranca con todos los estados en 0 para que el frontend
        // siempre reciba las mismas keys
        $stats = [
            'pending'   => 0,
            'confirmed' => 0,
            'cancelled' => 0,
            'completed' => 0,
        ];

        foreach ($result->getResult() as $row) {
            $stats[$row->status] = (int) $row->total;
        }

        return $stats;
    }
}

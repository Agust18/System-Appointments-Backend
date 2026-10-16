<?php

namespace App\Models\Appointment;

use App\Converter\Appointment\PrimitiveToAppointmentConverter;
use App\Converter\Appointment\Projection\PrimitiveToAppointmentProjectionConverter;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Entity\Appointment\Appointment;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use DateTime;

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
        $query = "INSERT INTO appointments (tenant_id, medical_id, patient_id, service_id, fecha, hora, hora_fin, nota, status, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $this->db->query($query, [
            TenantContext::id(),
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
                    AND tenant_id = ?
                    AND fecha = ?
                    AND status IN ('pending', 'confirmed', 'completed')
                    AND hora < ?
                    AND COALESCE(hora_fin, ADDTIME(hora, '00:30:00')) > ?";

        $params = [$medicalId, TenantContext::id(), $fecha, $horaFin, $horaInicio];

        if ($excludeAppointmentId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }

        $result = $this->db->query($query, $params);
        $row = $result->getRow();

        return $row && (int) $row->total > 0;
    }

    /**
     * Turnos pendientes o confirmados del paciente que todavía no ocurrieron.
     */
    public function countActiveFutureByPatient(int $patientId, DateTime $from): int
    {
        $query = "SELECT COUNT(*) as total
                  FROM appointments
                  WHERE patient_id = ?
                    AND tenant_id = ?
                    AND status IN ('pending', 'confirmed')
                    AND (fecha > ? OR (fecha = ? AND hora >= ?))";

        $result = $this->db->query($query, [
            $patientId,
            TenantContext::id(),
            $from->format('Y-m-d'),
            $from->format('Y-m-d'),
            $from->format('H:i:s'),
        ]);
        $row = $result->getRow();

        return $row ? (int) $row->total : 0;
    }

    public function update(Appointment $appointment): Appointment
    {
        $query = "UPDATE appointments
                  SET medical_id = ?, patient_id = ?, service_id = ?, fecha = ?, hora = ?, hora_fin = ?, nota = ?, status = ?, updated_at = ?
                  WHERE id = ? AND tenant_id = ?";

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
            $appointment->getId(),
            TenantContext::id()
        ]);

        return $this->find($appointment->getId());
    }

    public function find(int $id): ?Appointment
    {
        $query = "SELECT * FROM appointments WHERE id = ? AND tenant_id = ?";
        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $data = $result->getRow();

        if (!$data) {
            return null;
        }

        return $this->converter->convert($data);
    }


    public function findByMedicalId(int $medicalId): array
    {
        $query = "SELECT * FROM appointments WHERE medical_id = ? AND tenant_id = ? ORDER BY fecha DESC, hora DESC";
        $result = $this->db->query($query, [$medicalId, TenantContext::id()]);
        $primitives = $result->getResult();

        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function findByPatientId(int $patientId): array
    {
        $query = "SELECT * FROM appointments WHERE patient_id = ? AND tenant_id = ? ORDER BY fecha DESC, hora DESC";
        $result = $this->db->query($query, [$patientId, TenantContext::id()]);
        $primitives = $result->getResult();

        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function delete(int $id): void
    {
        $query = "DELETE FROM appointments WHERE id = ? AND tenant_id = ?";
        $this->db->query($query, [$id, TenantContext::id()]);
    }


    public function findByPatientUserId(int $userId): array
    {
        $query = "SELECT a.* FROM appointments a
              INNER JOIN patients p ON p.id = a.patient_id AND p.tenant_id = a.tenant_id
              WHERE p.user_id = ? AND a.tenant_id = ?";
        $result = $this->db->query($query, [$userId, TenantContext::id()]);
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
              INNER JOIN medicals m ON m.id = a.medical_id AND m.tenant_id = a.tenant_id
              WHERE m.user_id = ? AND a.tenant_id = ?";
        $result = $this->db->query($query, [$userId, TenantContext::id()]);
        $primitives = $result->getResult();
        $appointments = [];
        foreach ($primitives as $primitive) {
            $appointments[] = $this->converter->convert($primitive);
        }
        return $appointments;
    }

    public function hasRelationWithPatient(int $medicalUserId, int $patientId): bool
    {
        $sql = "SELECT EXISTS(
            SELECT 1
            FROM appointments a
            INNER JOIN medicals m ON m.id = a.medical_id AND m.tenant_id = a.tenant_id
            WHERE m.user_id = ?
              AND a.patient_id = ?
              AND a.tenant_id = ?
        ) AS has_relation";

        $result = $this->db->query($sql, [$medicalUserId, $patientId, TenantContext::id()]);
        $row = $result->getRow();

        return (bool) ($row->has_relation ?? false);
    }

    /**
     * Turnos de mañana (en 24 hs) con status pending o confirmed, con datos
     * de paciente (email, nombre), médico y servicio para enviar recordatorios.
     * Solo los del Tenant del contexto: el proceso de recordatorios recorre los Tenants activos.
     * Excluye a los pacientes sin email: no hay a dónde mandarles el recordatorio.
     */
    public function findUpcomingIn24Hours(): array
    {
        $query = "SELECT
            A.id, A.medical_id, A.patient_id, A.service_id, A.fecha, A.hora, A.nota, A.status,
            U_pac.email    AS paciente_email,
            TRIM(CONCAT(U_med.first_name, ' ', U_med.last_name)) AS medico_nombre,
            TRIM(CONCAT(U_pac.first_name, ' ', U_pac.last_name)) AS paciente_nombre,
            SRV.name       AS servicio_nombre
        FROM appointments A
        INNER JOIN medicals M   ON M.id  = A.medical_id AND M.tenant_id = A.tenant_id
        INNER JOIN users U_med  ON U_med.id = M.user_id AND U_med.tenant_id = M.tenant_id
        INNER JOIN patients P   ON P.id  = A.patient_id AND P.tenant_id = A.tenant_id
        INNER JOIN users U_pac  ON U_pac.id = P.user_id AND U_pac.tenant_id = P.tenant_id
        INNER JOIN services SRV ON SRV.id = A.service_id AND SRV.tenant_id = A.tenant_id
        WHERE A.tenant_id = ?
          AND A.fecha = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
          AND A.status IN ('pending', 'confirmed')
          AND U_pac.email IS NOT NULL
        ORDER BY A.hora ASC";

        $result   = $this->db->query($query, [TenantContext::id()]);
        $entities = [];
        foreach ($result->getResult() as $row) {
            $entities[] = $this->projectionConverter->convert($row);
        }
        return $entities;
    }

    public function findProjection(int $id): ?AppointmentProjection
    {
        $query = "SELECT
            A.id, A.medical_id, A.patient_id, A.service_id, A.fecha, A.hora, A.nota, A.status,
            U_pac.email  AS paciente_email,
            U_pac.phone  AS paciente_telefono,
            TRIM(CONCAT(U_med.first_name, ' ', U_med.last_name)) AS medico_nombre,
            TRIM(CONCAT(U_pac.first_name, ' ', U_pac.last_name)) AS paciente_nombre,
            SRV.name       AS servicio_nombre
        FROM appointments A
        INNER JOIN medicals M   ON M.id  = A.medical_id AND M.tenant_id = A.tenant_id
        INNER JOIN users U_med  ON U_med.id = M.user_id AND U_med.tenant_id = M.tenant_id
        INNER JOIN patients P   ON P.id  = A.patient_id AND P.tenant_id = A.tenant_id
        INNER JOIN users U_pac  ON U_pac.id = P.user_id AND U_pac.tenant_id = P.tenant_id
        INNER JOIN services SRV ON SRV.id = A.service_id AND SRV.tenant_id = A.tenant_id
        WHERE A.id = ? AND A.tenant_id = ?";

        $result = $this->db->query($query, [$id, TenantContext::id()]);
        $row    = $result->getRow();

        if (!$row) {
            return null;
        }

        return $this->projectionConverter->convert($row);
    }

    /**
     * Turnos activos (no cancelados) de un médico en una fecha, con
     * hora/hora_fin ya resueltos (fallback +30 min si hora_fin viniera
     * NULL). Usado por AppointmentAvailabilityService para chequear
     * solapamiento contra cada slot candidato sin repetir el query por
     * cada uno.
     */
    public function findActiveHoursByMedicalAndFecha(int $medicalId, string $fecha, ?int $excludeAppointmentId = null): array
    {
        $query = "SELECT hora, COALESCE(hora_fin, ADDTIME(hora, '00:30:00')) as hora_fin
                  FROM appointments
                  WHERE medical_id = ?
                    AND tenant_id = ?
                    AND fecha = ?
                    AND status IN ('pending', 'confirmed', 'completed')";

        $params = [$medicalId, TenantContext::id(), $fecha];

        // Al editar un turno su propio horario no cuenta como ocupado (mismo criterio que hasOverlap).
        if ($excludeAppointmentId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }

        $result = $this->db->query($query, $params);

        return $result->getResult();
    }

    /**
     * Igual que findActiveHoursByMedicalAndFecha pero para un rango de
     * fechas en una sola query (usado por el endpoint batch de
     * disponibilidad). Cada fila trae también `fecha` para poder agrupar
     * en PHP.
     */
    public function findActiveHoursByMedicalAndFechaRange(int $medicalId, string $from, string $to, ?int $excludeAppointmentId = null): array
    {
        $query = "SELECT fecha, hora, COALESCE(hora_fin, ADDTIME(hora, '00:30:00')) as hora_fin
                  FROM appointments
                  WHERE medical_id = ?
                    AND tenant_id = ?
                    AND fecha BETWEEN ? AND ?
                    AND status IN ('pending', 'confirmed', 'completed')";

        $params = [$medicalId, TenantContext::id(), $from, $to];

        if ($excludeAppointmentId !== null) {
            $query .= " AND id != ?";
            $params[] = $excludeAppointmentId;
        }

        $result = $this->db->query($query, $params);

        return $result->getResult();
    }



    public function searchProjection(AppointmentFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $selectQuery = "SELECT
            A.id, A.medical_id, A.patient_id, A.fecha, A.hora, A.nota, A.status,
            A.service_id,
            U_pac.email as paciente_email,
            SRV.name as servicio_nombre,
            TRIM(CONCAT(U_med.first_name, ' ', U_med.last_name)) as medico_nombre,
            TRIM(CONCAT(U_pac.first_name, ' ', U_pac.last_name)) as paciente_nombre
        ";


        $fromQuery = "FROM appointments A
            INNER JOIN medicals M ON M.id = A.medical_id AND M.tenant_id = A.tenant_id
            INNER JOIN users U_med ON U_med.id = M.user_id AND U_med.tenant_id = M.tenant_id
            INNER JOIN patients P ON P.id = A.patient_id AND P.tenant_id = A.tenant_id
            INNER JOIN users U_pac ON U_pac.id = P.user_id AND U_pac.tenant_id = P.tenant_id
            INNER JOIN services SRV ON SRV.id = A.service_id AND SRV.tenant_id = A.tenant_id
            ";
        $orderQuery = "ORDER BY (A.fecha < CURDATE()) ASC, A.fecha ASC, A.hora ASC ";
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
              INNER JOIN medicals M ON M.id = A.medical_id AND M.tenant_id = A.tenant_id
              INNER JOIN users U_med ON U_med.id = M.user_id AND U_med.tenant_id = M.tenant_id
              INNER JOIN specialties S ON S.id = M.specialty_id AND S.tenant_id = M.tenant_id
              INNER JOIN patients P ON P.id = A.patient_id AND P.tenant_id = A.tenant_id
              INNER JOIN users U_pac ON U_pac.id = P.user_id AND U_pac.tenant_id = P.tenant_id
              {$whereQuery}";

        $result = $this->db->query($query, $parameters);
        return $result->getRow()->total ?? 0;
    }

    private function buildWhereClause(AppointmentFilterRequest $request): array
    {
        $parameters = [TenantContext::id()];

        $whereQuery = "WHERE A.tenant_id = ? ";

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
            $whereQuery .= " AND (CONCAT(U_pac.first_name, ' ', U_pac.last_name) LIKE ? OR CONCAT(U_med.first_name, ' ', U_med.last_name) LIKE ?) ";
            $like = "%{$request->getSearch()}%";
            $parameters[] = $like;
            $parameters[] = $like;
        }

        return [$whereQuery, $parameters];
    }


    public function statsByStatus(AppointmentFilterRequest $request): array
    {
        [$whereQuery, $parameters] = $this->buildWhereClause($request);

        $query = "SELECT A.status, COUNT(*) as total
              FROM appointments A
              INNER JOIN medicals M ON M.id = A.medical_id AND M.tenant_id = A.tenant_id
              INNER JOIN users U_med ON U_med.id = M.user_id AND U_med.tenant_id = M.tenant_id
              INNER JOIN patients P ON P.id = A.patient_id AND P.tenant_id = A.tenant_id
              INNER JOIN users U_pac ON U_pac.id = P.user_id AND U_pac.tenant_id = P.tenant_id
              {$whereQuery}
              GROUP BY A.status";

        $result = $this->db->query($query, $parameters);

        // Arranca con todos los estados en 0 para que el frontend
        // siempre reciba las mismas keys
        $stats = [
            'pending' => 0,
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

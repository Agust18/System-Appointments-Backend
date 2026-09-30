<?php

namespace App\Models\Metrics;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

final class MetricsModel
{
    private BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    private function buildDateMedicalWhere(string $dateFrom, string $dateTo, ?int $medicalId): array
    {
        $parameters = [$dateFrom, $dateTo];
        $whereQuery = "WHERE A.fecha BETWEEN ? AND ? ";

        if ($medicalId !== null) {
            $whereQuery .= " AND A.medical_id = ? ";
            $parameters[] = $medicalId;
        }

        return [$whereQuery, $parameters];
    }

    public function getKpis(string $dateFrom, string $dateTo, ?int $medicalId): object
    {
        [$whereQuery, $parameters] = $this->buildDateMedicalWhere($dateFrom, $dateTo, $medicalId);

        $query = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN A.status = 'pending' THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN A.status = 'confirmed' AND CONCAT(A.fecha, ' ', A.hora) >= NOW() THEN 1 ELSE 0 END) AS confirmed,
                SUM(CASE WHEN A.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN A.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN A.status = 'confirmed' AND CONCAT(A.fecha, ' ', A.hora) < NOW() THEN 1 ELSE 0 END) AS noshow,
                SUM(CASE WHEN A.status = 'completed' THEN COALESCE(MS.custom_price, SRV.price, 0) ELSE 0 END) AS revenue
              FROM appointments A
              LEFT JOIN services SRV ON SRV.id = A.service_id
              LEFT JOIN medical_service MS ON MS.medical_id = A.medical_id AND MS.service_id = A.service_id
              {$whereQuery}";

        return $this->db->query($query, $parameters)->getRow();
    }

    public function getByMedical(string $dateFrom, string $dateTo): array
    {
        $query = "SELECT M.id AS medical_id, TRIM(CONCAT(U.first_name, ' ', U.last_name)) AS medical_name,
                COUNT(*) AS total,
                SUM(CASE WHEN A.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN A.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled
              FROM appointments A
              INNER JOIN medicals M ON M.id = A.medical_id
              INNER JOIN users U ON U.id = M.user_id
              WHERE A.fecha BETWEEN ? AND ?
              GROUP BY M.id, U.first_name, U.last_name
              ORDER BY total DESC";

        return $this->db->query($query, [$dateFrom, $dateTo])->getResult();
    }

    public function getMonthlyTrend(?int $medicalId, string $dateFrom, string $dateTo): array
    {
        [$whereQuery, $parameters] = $this->buildDateMedicalWhere($dateFrom, $dateTo, $medicalId);

        $query = "SELECT DATE_FORMAT(A.fecha, '%Y-%m') AS month,
                COUNT(*) AS total,
                SUM(CASE WHEN A.status = 'completed' THEN 1 ELSE 0 END) AS completed,
                SUM(CASE WHEN A.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled,
                SUM(CASE WHEN A.status = 'confirmed' AND CONCAT(A.fecha, ' ', A.hora) < NOW() THEN 1 ELSE 0 END) AS noshow
              FROM appointments A
              {$whereQuery}
              GROUP BY DATE_FORMAT(A.fecha, '%Y-%m')";

        return $this->db->query($query, $parameters)->getResult();
    }

    public function getTopServices(string $dateFrom, string $dateTo, ?int $medicalId): array
    {
        [$whereQuery, $parameters] = $this->buildDateMedicalWhere($dateFrom, $dateTo, $medicalId);
        $whereQuery .= " AND A.status IN ('pending', 'confirmed', 'completed') ";

        $query = "SELECT SRV.id AS service_id, SRV.name AS service_name, COUNT(*) AS total
              FROM appointments A
              INNER JOIN services SRV ON SRV.id = A.service_id
              {$whereQuery}
              GROUP BY SRV.id, SRV.name
              ORDER BY total DESC
              LIMIT 10";

        return $this->db->query($query, $parameters)->getResult();
    }

    public function getScheduleHeatmap(string $dateFrom, string $dateTo, ?int $medicalId): array
    {
        [$whereQuery, $parameters] = $this->buildDateMedicalWhere($dateFrom, $dateTo, $medicalId);
        $whereQuery .= " AND A.status IN ('pending', 'confirmed', 'completed') ";

        $query = "SELECT (WEEKDAY(A.fecha) + 1) AS day_of_week, HOUR(A.hora) AS hour, COUNT(*) AS total
              FROM appointments A
              {$whereQuery}
              GROUP BY day_of_week, HOUR(A.hora)
              HAVING COUNT(*) > 0";

        return $this->db->query($query, $parameters)->getResult();
    }

    public function getRecentAppointments(string $dateFrom, string $dateTo, ?int $medicalId): array
    {
        [$whereQuery, $parameters] = $this->buildDateMedicalWhere($dateFrom, $dateTo, $medicalId);

        $query = "SELECT A.id AS appointment_id, A.fecha, A.hora, A.status, A.created_at, A.updated_at,
                TRIM(CONCAT(U_pac.first_name, ' ', U_pac.last_name)) AS patient_name,
                TRIM(CONCAT(U_med.first_name, ' ', U_med.last_name)) AS medical_name,
                SRV.name AS service_name
              FROM appointments A
              INNER JOIN patients P ON P.id = A.patient_id
              INNER JOIN users U_pac ON U_pac.id = P.user_id
              INNER JOIN medicals M ON M.id = A.medical_id
              INNER JOIN users U_med ON U_med.id = M.user_id
              LEFT JOIN services SRV ON SRV.id = A.service_id
              {$whereQuery}
              ORDER BY A.updated_at DESC, A.id DESC
              LIMIT 10";

        return $this->db->query($query, $parameters)->getResult();
    }
}

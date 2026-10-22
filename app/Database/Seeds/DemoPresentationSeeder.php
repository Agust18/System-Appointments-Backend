<?php

namespace App\Database\Seeds;

use App\Database\Seeds\Support\ResolvesSeedTenant;
use CodeIgniter\Database\Seeder;
use DateTime;

/**
 * SEEDER LOCAL DE DEMO — deja el Tenant de `seed.tenantSlug` (.env) con datos para mostrar y probar. NO commitear.
 *
 * BORRA los datos operativos del Tenant (evoluciones, turnos, profesionales, pacientes, servicios,
 * especialidades y los usuarios) y conserva al admin y al usuario de recepción. Después siembra:
 *   - 3 profesionales (profesional1..3@hotmail.com, password 123456) con horarios de lunes a viernes aleatorios
 *   - 3 pacientes (paciente1..3@hotmail.com)
 *   - turnos aleatorios desde hoy hasta dentro de 45 días, dentro del horario de cada profesional y sin solaparse
 *
 * Uso (dentro del container):
 *   DRY_RUN=1 php spark db:seed DemoPresentationSeeder   → solo imprime lo que haría
 *   php spark db:seed DemoPresentationSeeder             → borra y siembra
 */
final class DemoPresentationSeeder extends Seeder
{
    use ResolvesSeedTenant;

    private const PASSWORD = '123456';
    private const KEEP_EMAILS = ['agustincamera16@hotmail.com', 'recepcion@hotmail.com'];
    private const DAYS_AHEAD = 45;
    // Los turnos de hoy anteriores a esta hora ya "pasaron": quedan completados.
    private const TODAY_DONE_BEFORE = '13:00';

    public function run(): void
    {
        $this->withSeedTenant(fn (int $tenantId) => $this->seed($tenantId));
    }

    private function seed(int $tenantId): void
    {
        mt_srand(20261021);
        $dry = getenv('DRY_RUN') === '1';

        $specialties = ['Clínica médica', 'Cardiología', 'Pediatría'];
        // [nombre, duración en minutos, precio]
        $services = [
            ['Consulta clínica', 30, 8000],
            ['Control', 20, 5000],
            ['Electrocardiograma', 30, 12000],
            ['Consulta pediátrica', 30, 9000],
        ];
        // [nombre, apellido, email, matrícula, especialidad, servicios]
        $medicals = [
            ['Laura', 'Benítez', 'profesional1@hotmail.com', 'MP-1001', 0, [0, 1]],
            ['Diego', 'Sosa', 'profesional2@hotmail.com', 'MP-1002', 1, [0, 1, 2]],
            ['Marta', 'Ríos', 'profesional3@hotmail.com', 'MP-1003', 2, [3, 1]],
        ];
        $patients = [
            ['Ana', 'Gómez', 'paciente1@hotmail.com'],
            ['Bruno', 'Díaz', 'paciente2@hotmail.com'],
            ['Carla', 'Ferrari', 'paciente3@hotmail.com'],
        ];

        $schedules = $this->buildSchedules(count($medicals));
        $appointments = $this->buildAppointments($medicals, $services, $schedules, count($patients));

        if ($dry) {
            $this->printPlan($medicals, $schedules, $appointments);
            return;
        }

        $this->db->transBegin();
        try {
            $this->wipe($tenantId);

            $now = date('Y-m-d H:i:s');
            $hash = password_hash(self::PASSWORD, PASSWORD_BCRYPT);

            $specialtyIds = [];
            foreach ($specialties as $i => $name) {
                $this->db->query("INSERT INTO specialties (tenant_id, name) VALUES (?, ?)", [$tenantId, $name]);
                $specialtyIds[$i] = (int) $this->db->insertID();
            }

            $serviceIds = [];
            foreach ($services as $i => [$name, $duration, $price]) {
                $this->db->query(
                    "INSERT INTO services (tenant_id, name, duration_minutes, buffer_minutes, price, is_active, created_at, updated_at)
                     VALUES (?, ?, ?, 0, ?, 1, ?, ?)",
                    [$tenantId, $name, $duration, $price, $now, $now]
                );
                $serviceIds[$i] = (int) $this->db->insertID();
            }

            $medicalIds = [];
            foreach ($medicals as $i => [$first, $last, $email, $tuition, $specialty, $svc]) {
                $this->db->query(
                    "INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, is_active, is_verified, role_id)
                     VALUES (?, ?, ?, ?, ?, ?, 1, 1, 2)",
                    [$tenantId, $first, $last, $email, $hash, 30000001 + $i]
                );
                $userId = (int) $this->db->insertID();

                $this->db->query(
                    "INSERT INTO medicals (tenant_id, user_id, tuition, specialty_id) VALUES (?, ?, ?, ?)",
                    [$tenantId, $userId, $tuition, $specialtyIds[$specialty]]
                );
                $medicalIds[$i] = (int) $this->db->insertID();

                foreach ($svc as $s) {
                    $this->db->query(
                        "INSERT INTO medical_service (tenant_id, medical_id, service_id, is_active, created_at, updated_at)
                         VALUES (?, ?, ?, 1, ?, ?)",
                        [$tenantId, $medicalIds[$i], $serviceIds[$s], $now, $now]
                    );
                }

                foreach ($schedules[$i] as [$day, $start, $end]) {
                    $this->db->query(
                        "INSERT INTO medical_schedules (tenant_id, medical_id, day_of_week, start_time, end_time, is_active)
                         VALUES (?, ?, ?, ?, ?, 1)",
                        [$tenantId, $medicalIds[$i], $day, $start . ':00', $end . ':00']
                    );
                }
            }

            $patientIds = [];
            foreach ($patients as $i => [$first, $last, $email]) {
                $this->db->query(
                    "INSERT INTO patients (tenant_id, first_name, last_name, email, dni, phone, birth_date)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $first, $last, $email, 40000001 + $i, '115555000' . $i, sprintf('19%02d-0%d-1%d', 80 + $i * 5, 2 + $i, 2 + $i)]
                );
                $patientIds[$i] = (int) $this->db->insertID();
            }

            foreach ($appointments as $a) {
                $this->db->query(
                    "INSERT INTO appointments (tenant_id, medical_id, patient_id, service_id, fecha, hora, hora_fin, status, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$tenantId, $medicalIds[$a['medical']], $patientIds[$a['patient']], $serviceIds[$a['service']],
                     $a['fecha'], $a['hora'], $a['hora_fin'], $a['status'], $now, $now]
                );
            }

            $this->db->transCommit();
            echo 'Demo lista: ' . count($medicals) . ' profesionales, ' . count($patients) . ' pacientes, ' . count($appointments) . " turnos.\n";
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }

    /** Borra los datos operativos del Tenant en orden de dependencias; conserva admin y recepción. */
    private function wipe(int $tenantId): void
    {
        foreach (['evolution_access_log', 'evolutions', 'appointments', 'medicals', 'patients'] as $table) {
            $this->db->query("DELETE FROM {$table} WHERE tenant_id = ?", [$tenantId]);
        }

        $keep = implode(',', array_fill(0, count(self::KEEP_EMAILS), '?'));
        $this->db->query(
            "DELETE FROM users WHERE tenant_id = ? AND (email IS NULL OR email NOT IN ({$keep}))",
            [$tenantId, ...self::KEEP_EMAILS]
        );

        $this->db->query("DELETE FROM services WHERE tenant_id = ?", [$tenantId]);
        $this->db->query("DELETE FROM specialties WHERE tenant_id = ?", [$tenantId]);
    }

    /**
     * Horarios de lunes (1) a viernes (5), distintos por profesional y por día.
     * @return array<int, array<int, array{0: int, 1: string, 2: string}>> [profesional => [[día, desde, hasta], ...]]
     */
    private function buildSchedules(int $medicalCount): array
    {
        $shifts = [
            [['08:00', '12:00']],
            [['14:00', '18:00']],
            [['09:00', '13:00']],
            [['08:00', '12:00'], ['14:00', '18:00']],
            [['10:00', '14:00'], ['15:00', '18:00']],
        ];

        $schedules = [];
        for ($m = 0; $m < $medicalCount; $m++) {
            for ($day = 1; $day <= 5; $day++) {
                foreach ($shifts[mt_rand(0, count($shifts) - 1)] as [$from, $to]) {
                    $schedules[$m][] = [$day, $from, $to];
                }
            }
        }

        return $schedules;
    }

    /** @return array<int, array<string, mixed>> */
    private function buildAppointments(array $medicals, array $services, array $schedules, int $patientCount): array
    {
        $now = new DateTime();
        $today = $now->format('Y-m-d');
        $rows = [];
        $takenByMedical = []; // "m|fecha" => [[ini, fin]]
        $takenByPatient = []; // "p|fecha" => [[ini, fin]]

        for ($offset = 0; $offset <= self::DAYS_AHEAD; $offset++) {
            $date = (clone $now)->modify("+{$offset} days");
            $weekday = (int) $date->format('N');
            if ($weekday > 5) {
                continue;
            }
            $fecha = $date->format('Y-m-d');

            foreach ($medicals as $m => [, , , , , $svc]) {
                $ranges = array_values(array_filter($schedules[$m], fn ($r) => $r[0] === $weekday));
                // hoy siempre hay turnos para que la lista de Turnos no arranque vacía; los demás días, 0 a 2
                $count = $fecha === $today ? mt_rand(2, 4) : [0, 0, 0, 1, 1, 2][mt_rand(0, 5)];

                for ($i = 0; $i < $count; $i++) {
                    $row = $this->place($m, $svc, $services, $ranges, $fecha, $patientCount, $takenByMedical, $takenByPatient, $today);
                    if ($row) {
                        $rows[] = $row;
                    }
                }
            }
        }

        usort($rows, fn ($a, $b) => [$a['fecha'], $a['hora']] <=> [$b['fecha'], $b['hora']]);

        return $rows;
    }

    private function place(int $m, array $svc, array $services, array $ranges, string $fecha, int $patientCount, array &$takenByMedical, array &$takenByPatient, string $today): ?array
    {
        if ($ranges === []) {
            return null;
        }

        for ($try = 0; $try < 40; $try++) {
            $service = $svc[mt_rand(0, count($svc) - 1)];
            $duration = $services[$service][1];
            [, $from, $to] = $ranges[mt_rand(0, count($ranges) - 1)];
            $fromMin = $this->minutes($from);
            $toMin = $this->minutes($to);
            if ($toMin - $fromMin < $duration) {
                continue;
            }

            $start = $fromMin + 15 * mt_rand(0, intdiv($toMin - $fromMin - $duration, 15));
            $end = $start + $duration;
            $patient = mt_rand(0, $patientCount - 1);

            if ($this->overlaps($takenByMedical["{$m}|{$fecha}"] ?? [], $start, $end)
                || $this->overlaps($takenByPatient["{$patient}|{$fecha}"] ?? [], $start, $end)) {
                continue;
            }
            $takenByMedical["{$m}|{$fecha}"][] = [$start, $end];
            $takenByPatient["{$patient}|{$fecha}"][] = [$start, $end];

            $isToday = $fecha === $today;
            $status = $isToday && $start < $this->minutes(self::TODAY_DONE_BEFORE)
                ? 'completed'
                : ([ 'confirmed', 'confirmed', 'confirmed', 'pending'][mt_rand(0, 3)]);

            return [
                'medical' => $m,
                'patient' => $patient,
                'service' => $service,
                'fecha' => $fecha,
                'hora' => $this->hhmmss($start),
                'hora_fin' => $this->hhmmss($end),
                'status' => $status,
            ];
        }

        return null;
    }

    private function overlaps(array $taken, int $start, int $end): bool
    {
        foreach ($taken as [$s, $e]) {
            if ($start < $e && $end > $s) {
                return true;
            }
        }

        return false;
    }

    private function minutes(string $hhmm): int
    {
        [$h, $m] = array_map('intval', explode(':', $hhmm));

        return $h * 60 + $m;
    }

    private function hhmmss(int $minutes): string
    {
        return sprintf('%02d:%02d:00', intdiv($minutes, 60), $minutes % 60);
    }

    private function printPlan(array $medicals, array $schedules, array $appointments): void
    {
        echo "=== DRY RUN (no se borra ni se inserta nada) ===\n\n";
        foreach ($medicals as $m => [$first, $last, $email]) {
            echo "Profesional {$first} {$last} <{$email}> — horarios:\n";
            foreach ($schedules[$m] as [$day, $from, $to]) {
                echo "  " . ['', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie'][$day] . " {$from}-{$to}\n";
            }
        }

        $byStatus = [];
        $byMedical = [];
        foreach ($appointments as $a) {
            $byStatus[$a['status']] = ($byStatus[$a['status']] ?? 0) + 1;
            $byMedical[$a['medical']] = ($byMedical[$a['medical']] ?? 0) + 1;
        }
        echo "\nTurnos: " . count($appointments) . ' | por estado ' . json_encode($byStatus) . ' | por profesional ' . json_encode($byMedical) . "\n";
        echo 'Rango: ' . $appointments[0]['fecha'] . ' a ' . end($appointments)['fecha'] . "\n";
        echo "Hoy:\n";
        foreach ($appointments as $a) {
            if ($a['fecha'] === date('Y-m-d')) {
                echo sprintf("  P%d pac%d %s-%s %s\n", $a['medical'] + 1, $a['patient'] + 1, substr($a['hora'], 0, 5), substr($a['hora_fin'], 0, 5), $a['status']);
            }
        }
    }
}

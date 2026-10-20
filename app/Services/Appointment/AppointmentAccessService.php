<?php
namespace App\Services\Appointment;

use App\Entity\Appointment\AppointmentAccess;
use App\Models\Appointment\AppointmentModel;

/**
 * Credenciales de `/consulta`: un código corto (se usa con el DNI) y un token largo (va en el link del email).
 * En la base solo se guarda el SHA-256 de cada uno; el valor en claro se entrega una única vez, al emitirlo.
 */
final class AppointmentAccessService
{
    /** Sin caracteres ambiguos (I, L, O, 0, 1). */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 8;

    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
    }

    /**
     * Emite credenciales nuevas para el turno. Si ya tenía, las reemplaza (las anteriores dejan de servir).
     */
    public function issue(int $appointmentId): AppointmentAccess
    {
        $access = new AppointmentAccess($this->generateCode(), bin2hex(random_bytes(32)));

        $this->appointmentModel->saveAccessHashes(
            $appointmentId,
            self::hash($access->code),
            self::hash($access->token)
        );

        return $access;
    }

    public function findIdByDniAndCode(int $dni, string $code): ?int
    {
        return $this->appointmentModel->findIdByDniAndAccessCode($dni, self::hash(self::normalizeCode($code)));
    }

    public function findIdByToken(string $token): ?int
    {
        return $this->appointmentModel->findIdByAccessToken(self::hash(trim($token)));
    }

    /** Tolera minúsculas, espacios y guiones al tipear (por ejemplo "k7mq-4xp2"). */
    public static function normalizeCode(string $code): string
    {
        return strtoupper(preg_replace('/[\s\-]/', '', $code));
    }

    /** "K7MQ4XP2" → "K7MQ-4XP2", para mostrarlo legible. */
    public static function formatCode(string $code): string
    {
        return substr($code, 0, 4) . '-' . substr($code, 4);
    }

    private static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    private function generateCode(): string
    {
        $max = strlen(self::CODE_ALPHABET) - 1;
        $code = '';
        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::CODE_ALPHABET[random_int(0, $max)];
        }

        return $code;
    }
}

<?php

namespace App\Services\Patient;

use App\Tenancy\TenantContext;

/**
 * Bloqueo por DNI de `/consulta` (consultar y cancelar un turno con DNI + código).
 *
 * Cuenta los intentos fallidos de un DNI. Al llegar a MAX_FAILURES el DNI queda bloqueado BLOCK_SECONDS:
 * quien lo use recibe el mismo error neutral sin consultar la base, de modo que quien ataca no distingue
 * "bloqueado" de "código incorrecto". Mientras está bloqueado no se suman fallos, así que el bloqueo vence
 * BLOCK_SECONDS después del último fallo. Un acierto limpia el contador.
 *
 * Se guarda en el cache (nunca el DNI en claro). La clave incluye el Tenant: el DNI solo es único
 * por Tenant, y el bloqueo de uno no debe afectar al otro. Si el cache no funciona (handler dummy) no
 * bloquea: falla abierto; Throttle avisa en los logs.
 *
 * Costo asumido: alguien puede bloquear a propósito a un paciente real durante una hora. Ese paciente
 * puede usar el link de su email o llamar al consultorio.
 */
final class PublicIdentityAttemptLimiter
{
    public const MAX_FAILURES = 5;
    public const BLOCK_SECONDS = 3600;

    private function key(int $dni): string
    {
        return 'public_identity_failures_' . TenantContext::id() . '_' . hash('sha256', (string) $dni);
    }

    private function failures(int $dni): int
    {
        return (int) (service('cache')->get($this->key($dni)) ?? 0);
    }

    public function isBlocked(int $dni): bool
    {
        return $this->failures($dni) >= self::MAX_FAILURES;
    }

    public function recordFailure(int $dni): void
    {
        service('cache')->save($this->key($dni), $this->failures($dni) + 1, self::BLOCK_SECONDS);
    }

    public function clear(int $dni): void
    {
        service('cache')->delete($this->key($dni));
    }
}
<?php

namespace App\Services\Patient;

use App\Tenancy\TenantContext;

/**
 * SIN USO desde que el paciente es una ficha identificada solo por DNI (la reserva ya no compara
 * teléfonos). Se reutiliza en `/consulta` (fase 3 de bitacora/pendiente/paciente-sin-usuario.md).
 *
 * Bloqueo por DNI de la reserva pública. El teléfono es el segundo factor que acompaña
 * al DNI; sin un límite se podría adivinar probando teléfonos para un DNI conocido.
 *
 * Cuenta los rechazos por teléfono de un DNI existente. Al llegar a MAX_FAILURES el DNI
 * queda bloqueado BLOCK_SECONDS: quien lo use responde el mismo 409 neutral
 * sin consultar la base, de modo que quien ataca no distingue "bloqueado" de "teléfono
 * incorrecto". Mientras está bloqueado no se suman fallos, así que el bloqueo vence
 * BLOCK_SECONDS después del último fallo.
 *
 * Se guarda en el cache (nunca el DNI en claro). La clave incluye el Tenant: el DNI solo es único
 * por Tenant, y el bloqueo de uno no debe afectar al otro. Si el cache no funciona (handler dummy) no
 * bloquea: falla abierto; Throttle avisa en los logs.
 *
 * Costo asumido: alguien puede bloquear a propósito a un paciente real durante una hora.
 * Ese paciente puede iniciar sesión o llamar al consultorio.
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
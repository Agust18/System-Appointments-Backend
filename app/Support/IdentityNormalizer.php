<?php

namespace App\Support;

/**
 * Normaliza DNI, teléfono y email antes de validar y guardar.
 *
 * Solo elimina separadores (espacios, puntos, guiones, paréntesis, "+"); nunca
 * letras, para que un valor basura llegue intacto a la validación y se rechace.
 * Los valores se guardan normalizados y se formatean solo al mostrarlos.
 */
final class IdentityNormalizer
{
    private const MIN_PHONE_DIGITS = 10;

    /**
     * "23.525.064", "23-525-064", "23525064" → "23525064".
     */
    public static function dni(?string $value): ?string
    {
        $clean = preg_replace('/[\s.\-]/', '', trim((string) $value));

        if ($clean === '') {
            return null;
        }

        if (!ctype_digit($clean)) {
            return $clean;
        }

        $withoutLeadingZeros = ltrim($clean, '0');

        return $withoutLeadingZeros === '' ? '0' : $withoutLeadingZeros;
    }

    /**
     * "2325-506499", "2325 506499", "+54 9 2325 506499" → "2325506499".
     * Quita el prefijo internacional (00 / +54 / 549) y el 0 inicial de área.
     * El "15" móvil no se intenta resolver porque es ambiguo.
     */
    public static function phone(?string $value): ?string
    {
        $clean = preg_replace('/[\s\-().]/', '', trim((string) $value));

        if ($clean === '') {
            return null;
        }

        if (str_starts_with($clean, '+')) {
            $clean = '00' . substr($clean, 1);
        }

        if (!ctype_digit($clean)) {
            return $clean;
        }

        $clean = self::stripPrefix($clean, '00');

        $withoutMobilePrefix = self::stripPrefix($clean, '549');
        $clean = $withoutMobilePrefix !== $clean
            ? $withoutMobilePrefix
            : self::stripPrefix($clean, '54');

        return self::stripPrefix($clean, '0');
    }

    public static function email(?string $value): ?string
    {
        $clean = mb_strtolower(trim((string) $value));

        return $clean === '' ? null : $clean;
    }

    /**
     * Normaliza dni, phone y email de un payload, si están presentes.
     * Otros campos, y valores que no sean string/int, quedan intactos.
     */
    public static function normalizeInput(array $parameters): array
    {
        if (self::isScalarInput($parameters, 'dni')) {
            $parameters['dni'] = self::dni((string) $parameters['dni']);
        }

        if (self::isScalarInput($parameters, 'phone')) {
            $parameters['phone'] = self::phone((string) $parameters['phone']);
        }

        if (self::isScalarInput($parameters, 'email')) {
            $parameters['email'] = self::email((string) $parameters['email']);
        }

        return $parameters;
    }

    private static function isScalarInput(array $parameters, string $field): bool
    {
        return array_key_exists($field, $parameters)
            && (is_string($parameters[$field]) || is_int($parameters[$field]));
    }

    /**
     * Quita el prefijo solo si queda un teléfono de largo plausible.
     */
    private static function stripPrefix(string $digits, string $prefix): string
    {
        if (str_starts_with($digits, $prefix) && strlen($digits) - strlen($prefix) >= self::MIN_PHONE_DIGITS) {
            return substr($digits, strlen($prefix));
        }

        return $digits;
    }
}

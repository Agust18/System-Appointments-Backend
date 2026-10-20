<?php

namespace App\Support;


final class IdentityNormalizer
{
    private const MIN_PHONE_DIGITS = 10;

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

    public static function whatsapp(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits === '' ? null : $digits;
    }

    public static function email(?string $value): ?string
    {
        $clean = mb_strtolower(trim((string) $value));

        return $clean === '' ? null : $clean;
    }

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

    private static function stripPrefix(string $digits, string $prefix): string
    {
        if (str_starts_with($digits, $prefix) && strlen($digits) - strlen($prefix) >= self::MIN_PHONE_DIGITS) {
            return substr($digits, strlen($prefix));
        }

        return $digits;
    }
}

<?php

namespace App\Services\Metrics;

use App\Exception\Validation\ValidationException;
use DateTime;

final class MetricsDateRangeResolverService
{
    /**
     * @return array{0: string, 1: string}
     */
    public function resolve(?string $dateFrom, ?string $dateTo): array
    {
        $resolvedDateFrom = $dateFrom ?? date('Y-m-01');
        $resolvedDateTo = $dateTo ?? date('Y-m-d');

        $errors = [];

        if (!$this->isValidDate($resolvedDateFrom)) {
            $errors['dateFrom'] = 'El formato de dateFrom debe ser YYYY-MM-DD';
        }

        if (!$this->isValidDate($resolvedDateTo)) {
            $errors['dateTo'] = 'El formato de dateTo debe ser YYYY-MM-DD';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return [$resolvedDateFrom, $resolvedDateTo];
    }

    private function isValidDate(string $date): bool
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}

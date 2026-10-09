<?php

namespace App\Dto\Request;

use App\Constants\PublicCatalog;

final readonly class PaginationRequest {
    public function __construct(
        private int $page = 1,
        private int $size = 10
    ) {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLimit(): int
    {
        return $this->size;
    }

    public function getOffset(): int
    {
        if ($this->page === 1) {
            return 0;
        }

        return ($this->page * $this->size) - $this->size;
    }

    /**
     * @param int $maxSize tope de `size`; solo un listado con una necesidad justificada lo sube.
     * @return array<string, string>
     */
    public static function rules(int $maxSize = PublicCatalog::MAX_ITEMS): array
    {
        return [
            'page' => 'permit_empty|is_natural_no_zero',
            'size' => "permit_empty|is_natural_no_zero|less_than_equal_to[{$maxSize}]",
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(int $maxSize = PublicCatalog::MAX_ITEMS): array
    {
        return [
            'page' => [
                'is_natural_no_zero' => 'La página debe ser un número entero mayor que cero.',
            ],
            'size' => [
                'is_natural_no_zero'    => 'El tamaño de página debe ser un número entero mayor que cero.',
                'less_than_equal_to'    => "El tamaño de página no puede superar {$maxSize}.",
            ],
        ];
    }
}

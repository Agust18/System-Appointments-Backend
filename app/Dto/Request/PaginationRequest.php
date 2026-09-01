<?php 

namespace App\Dto\Request;

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
}


<?php
namespace App\Entity\Specialty;

use App\Dto\Request\Specialty\SpecialtyRequest;
use App\Dto\Request\Specialty\SpecialtyUpdateRequest;
use DateTime;

final class Specialty
{
    public function __construct(
        private ?int $id,
        private string $name,
        private ?string $description,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}

      public function modify(SpecialtyUpdateRequest $request): void
    {
       if ($request->getName() !== null) {
            $this->name = $request->getName();
        }
        $this->description = $request->getDescription() ?? $this->description;
       
      
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public static function fromRequest(SpecialtyRequest $request): Specialty
    {
        return new Specialty(
            null,
            $request->getName(),
            $request->getDescription(),
            new DateTime(),                          // createdAt
            new DateTime()      
        );
    }
}
<?php
namespace App\Dto\Request\Evolution;

final readonly class EvolutionUpdateRequest
{
    public function __construct(
        private ?string $diagnosis = null,
        private ?string $observations = null,
        private ?string $treatment = null,
        private ?string $indications = null
    ) {
    }

    public function getDiagnosis(): ?string
    {
        return $this->diagnosis;
    }

    public function getObservations(): ?string
    {
        return $this->observations;
    }

    public function getTreatment(): ?string
    {
        return $this->treatment;
    }

    public function getIndications(): ?string
    {
        return $this->indications;
    }

    public static function rules(): array
    {
        return [
            'diagnosis'    => 'permit_empty|string|max_length[5000]',
            'observations' => 'permit_empty|string|max_length[5000]',
            'treatment'    => 'permit_empty|string|max_length[5000]',
            'indications'  => 'permit_empty|string|max_length[5000]',
        ];
    }

    public static function messages(): array
    {
        return EvolutionRequest::messages();
    }
}

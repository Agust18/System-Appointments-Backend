<?php
namespace App\Dto\Request\Evolution;

final readonly class EvolutionRequest
{
    public function __construct(
        private int $appointmentId,
        private ?string $diagnosis = null,
        private ?string $observations = null,
        private ?string $treatment = null,
        private ?string $indications = null,
        private ?int $correctsId = null
    ) {
    }

    public function getAppointmentId(): int
    {
        return $this->appointmentId;
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

    public function getCorrectsId(): ?int
    {
        return $this->correctsId;
    }

    public static function rules(): array
    {
        return [
            'appointmentId' => 'required|is_natural_no_zero',
            'diagnosis'     => 'permit_empty|string|max_length[5000]',
            'observations'  => 'permit_empty|string|max_length[5000]',
            'treatment'     => 'permit_empty|string|max_length[5000]',
            'indications'   => 'permit_empty|string|max_length[5000]',
            'correctsId'    => 'permit_empty|is_natural_no_zero',
        ];
    }

    public static function messages(): array
    {
        return [
            'appointmentId' => [
                'required'           => 'Indicá el turno de la evolución.',
                'is_natural_no_zero' => 'El turno no es válido.',
            ],
            'diagnosis' => [
                'max_length' => 'El diagnóstico no puede superar los 5000 caracteres.',
            ],
            'observations' => [
                'max_length' => 'Las observaciones no pueden superar los 5000 caracteres.',
            ],
            'treatment' => [
                'max_length' => 'El tratamiento no puede superar los 5000 caracteres.',
            ],
            'indications' => [
                'max_length' => 'Las indicaciones no pueden superar los 5000 caracteres.',
            ],
            'correctsId' => [
                'is_natural_no_zero' => 'La evolución a corregir no es válida.',
            ],
        ];
    }
}

<?php
namespace App\Dto\Request\MedicalSchedule;

final readonly class MedicalScheduleUpdateRequest
{
    public function __construct(
        private ?int $medicalId = null,
        private ?int $dayOfWeek = null,
        private ?string $startTime = null,
        private ?string $endTime = null,
        private ?bool $isActive = null
    ) {
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }
    public function getDayOfWeek(): ?int
    {
        return $this->dayOfWeek;
    }
    public function getStartTime(): ?string
    {
        return $this->startTime;
    }
    public function getEndTime(): ?string
    {
        return $this->endTime;
    }
    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'dayOfWeek' => 'permit_empty|in_list[1,2,3,4,5,6,7]',
            'startTime' => 'permit_empty|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
            'endTime'   => 'permit_empty|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
            'isActive'  => 'permit_empty|in_list[0,1]',
        ];
    }

    public static function messages(): array
    {
        return [
            'medicalId' => [
                'is_natural_no_zero' => 'Elegí un médico válido.',
            ],
            'dayOfWeek' => [
                'in_list' => 'El día de la semana no es válido.',
            ],
            'startTime' => [
                'regex_match' => 'La hora de inicio no es válida.',
            ],
            'endTime' => [
                'regex_match' => 'La hora de fin no es válida.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ];
    }
}
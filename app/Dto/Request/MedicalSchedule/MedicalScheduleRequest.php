<?php
namespace App\Dto\Request\MedicalSchedule;

final readonly class MedicalScheduleRequest
{
    public function __construct(
        private int $medicalId,
        private int $dayOfWeek,
        private string $startTime,
        private string $endTime,
        private bool $isActive = true
    ) {
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }
    public function getDayOfWeek(): int
    {
        return $this->dayOfWeek;
    }
    public function getStartTime(): string
    {
        return $this->startTime;
    }
    public function getEndTime(): string
    {
        return $this->endTime;
    }
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'medicalId' => 'required|is_natural_no_zero',
            'dayOfWeek' => 'required|in_list[1,2,3,4,5,6,7]',
            'startTime' => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
            'endTime'   => 'required|regex_match[/^\d{2}:\d{2}(:\d{2})?$/]',
            'isActive'  => 'permit_empty|in_list[0,1]',
        ];
    }

    public static function messages(): array
    {
        return [
            'medicalId' => [
                'required'           => 'Elegí un médico.',
                'is_natural_no_zero' => 'Elegí un médico válido.',
            ],
            'dayOfWeek' => [
                'required' => 'Elegí un día de la semana.',
                'in_list'  => 'El día de la semana no es válido.',
            ],
            'startTime' => [
                'required'     => 'Ingresá la hora de inicio.',
                'regex_match'  => 'La hora de inicio no es válida.',
            ],
            'endTime' => [
                'required'    => 'Ingresá la hora de fin.',
                'regex_match' => 'La hora de fin no es válida.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ];
    }
}
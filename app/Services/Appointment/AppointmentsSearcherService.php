<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Models\Appointment\AppointmentModel;

final class AppointmentsSearcherService
{
    private AppointmentModel $appointmentModel;
    private AppointmentToAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentToAppointmentResponseConverter();
    }

    public function searchResponses(AppointmentFilterRequest $request): array
    {
        $entities = $this->appointmentModel->search($request);
        
        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        
        return $responses;
    }
}
<?php
namespace App\Services\Appointment\Projection;

use App\Converter\Appointment\Projection\AppointmentProjectionToAppointmentProjectionResponseConverter;
use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Models\Appointment\AppointmentModel;


final readonly class AppointmentsProjectionSearcherService
{
  private AppointmentModel $appointmentModel;
  private AppointmentProjectionToAppointmentProjectionResponseConverter $converter;
  public function __construct()
  {
    $this->appointmentModel = new AppointmentModel();
    $this->converter = new AppointmentProjectionToAppointmentProjectionResponseConverter();
  }
  public function searchResponses(AppointmentFilterRequest $request) : array
  {
      $appointments = $this->appointmentModel->searchProjection($request);
      $total = $this->appointmentModel->countProjection($request);

      $responses = [];
      foreach ($appointments as $appointment) {
        $responses[] = $this->converter->convert($appointment);
      }
      return [
        'data' => $responses,
        'total' => $total,
      ];

  }
}
<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\Projection\AppointmentProjectionToPublicAppointmentResponseConverter;
use App\Dto\Request\Appointment\PublicAppointmentAccessRequest;
use App\Dto\Response\Appointment\PublicAppointmentResponse;

final class PublicAppointmentLookupService
{
    private PublicAppointmentAccessResolverService $resolver;
    private AppointmentProjectionToPublicAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->resolver = new PublicAppointmentAccessResolverService();
        $this->converter = new AppointmentProjectionToPublicAppointmentResponseConverter();
    }

    public function find(PublicAppointmentAccessRequest $request): PublicAppointmentResponse
    {
        return $this->converter->convert($this->resolver->resolve($request));
    }
}

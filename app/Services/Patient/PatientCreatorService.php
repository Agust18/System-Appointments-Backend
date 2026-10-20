<?php
namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Models\Patient\PatientModel;
use App\Services\Patient\Validations\PatientCreateValidatorService;

final class PatientCreatorService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientCreateValidatorService $validator;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->validator = new PatientCreateValidatorService();
    }

    public function create(PatientRequest $request): PatientResponse
    {
        $this->validator->validate($request);

        $patient = $this->patientModel->insert(Patient::fromRequest($request));

        return $this->converter->convert($patient);
    }

    /**
     * Reserva pública: si el DNI ya existe en el Tenant se reutiliza la ficha SIN modificarla
     * (nombre, email y teléfono tipeados se ignoran). Si no, se crea.
     * Dos solicitudes simultáneas del mismo DNI: gana el UNIQUE(tenant_id, dni) y la perdedora
     * recupera la ficha ganadora.
     */
    public function findOrCreateByDni(PatientRequest $request): Patient
    {
        $existing = $this->patientModel->findByDni($request->getDni());

        if ($existing !== null) {
            return $existing;
        }

        try {
            return $this->patientModel->insert(Patient::fromRequest($request));
        } catch (\Throwable $e) {
            $winner = $this->patientModel->findByDni($request->getDni());

            if ($winner === null) {
                throw $e;
            }

            return $winner;
        }
    }
}

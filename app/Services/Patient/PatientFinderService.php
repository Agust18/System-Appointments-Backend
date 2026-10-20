<?php
namespace App\Services\Patient;

use App\Entity\Patient\Patient;
use App\Exception\Patient\PatientNotFoundException;
use App\Models\Patient\PatientModel;

final class PatientFinderService
{
    private PatientModel $patientModel;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
    }

    public function find(int $id): Patient
    {
        $patient = $this->patientModel->find($id);

        if (!$patient) {
            throw new PatientNotFoundException($id);
        }
        return $patient;
    }
}

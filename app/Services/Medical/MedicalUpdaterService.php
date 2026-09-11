<?php

namespace App\Services\Medical;

use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Request\Medical\MedicalUpdateRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Models\Medical\MedicalModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\Medical\Validations\MedicalUpdateValidatorService;

final class MedicalUpdaterService
{
    private MedicalModel $medicalModel;
    private MedicalFinderService $medicalFinderService;
    private MedicalToMedicalResponseConverter $converter;
    private MedicalUpdateValidatorService $validator;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->converter = new MedicalToMedicalResponseConverter();
        $this->validator = new MedicalUpdateValidatorService();
    }

    public function update(int $id, MedicalUpdateRequest $request): MedicalResponse
    {
        $medical = $this->medicalFinderService->find($id);

        $this->validator->validate($request, $medical);

        $medical->modify($request);
        $updatedMedical = $this->medicalModel->update($medical);

        return $this->converter->convert($updatedMedical);
    }
}
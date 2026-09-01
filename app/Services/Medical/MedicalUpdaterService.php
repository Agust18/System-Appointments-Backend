<?php
namespace App\Services\Medical;

use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Request\Medical\MedicalUpdateRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Entity\Medical\Medical;
use App\Exception\Medical\MedicalTuitionAlreadyExistsException;
use App\exception\Specialty\SpecialtyNotFoundException;
use App\Models\Medical\MedicalModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\Specialty\SpecialtyFinderService;

final class MedicalUpdaterService
{
    private MedicalModel $medicalModel;
    private MedicalFinderService $medicalFinderService;
    private MedicalToMedicalResponseConverter $converter;
    private SpecialtyFinderService $specialtyFinderService;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->converter = new MedicalToMedicalResponseConverter();
        $this->specialtyFinderService = new SpecialtyFinderService();
    }

    public function update(int $id, MedicalUpdateRequest $request): MedicalResponse
    {
     
        $medical = $this->medicalFinderService->find($id);
        
        if ($request->getSpecialtyId() && $request->getSpecialtyId() !== $medical->getSpecialtyId()) {
            $specialty = $this->specialtyFinderService->find($request->getSpecialtyId());
            if (!$specialty) {
                throw new SpecialtyNotFoundException($request->getSpecialtyId());
            }
        }
        
        if ($request->getTuition() && $request->getTuition() !== $medical->getTuition()) {
            $existingTuition = $this->medicalModel->findByTuition($request->getTuition());
            if ($existingTuition && $existingTuition->getId() !== $id) {
                throw new MedicalTuitionAlreadyExistsException($request->getTuition());
            }
        }
        
        $medical->modify($request);
        $updatedMedical = $this->medicalModel->update($medical);
        
        return $this->converter->convert($updatedMedical);
    }
}
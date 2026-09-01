<?php
namespace App\Services\Specialty;

use App\Converter\Specialty\SpecialtyToSpecialtyResponseConverter;
use App\Dto\Request\Specialty\SpecialtyUpdateRequest;
use App\Dto\Response\Specialty\SpecialtyResponse;
use App\Models\Specialty\SpecialtyModel;
use App\Services\Specialty\SpecialtyFinderService;

final class SpecialtyUpdaterService
{
    private SpecialtyModel $specialtyModel;
    private SpecialtyToSpecialtyResponseConverter $converter;
    private SpecialtyFinderService $SpecialtyFinderService;

    public function __construct()
    {
        $this->specialtyModel = new SpecialtyModel();
        $this->converter = new SpecialtyToSpecialtyResponseConverter();
        $this->SpecialtyFinderService = new SpecialtyFinderService();
    }

    public function update(SpecialtyUpdateRequest $request, int $id): SpecialtyResponse
    {
        $specialty= $this->SpecialtyFinderService->find($id);
        
        $specialty->modify($request);
        
        $updatedSpecialty = $this->specialtyModel->update($specialty);
        
        $response = $this->converter->convert($updatedSpecialty);
        return $response;   
        
    }
}
<?php
namespace App\Services\Medical;

use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Entity\Medical\Medical;
use App\Models\Medical\MedicalModel;
use App\Services\Medical\Validations\MedicalCreateValidatorService;


final class MedicalCreatorService
{
    private MedicalModel $medicalModel;
    private MedicalToMedicalResponseConverter $converter;
    private MedicalCreateValidatorService $validator;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalToMedicalResponseConverter();
        $this->validator = new MedicalCreateValidatorService();
    }

    public function create(MedicalRequest $request): MedicalResponse
    {
        $this->validator->validate($request);
      
        $medical = Medical::convertFromRequest($request);

        $newMedical = $this->medicalModel->insert($medical);
        
        $medicalResponse = $this->converter->convert($newMedical);

        return $medicalResponse;
    }
}
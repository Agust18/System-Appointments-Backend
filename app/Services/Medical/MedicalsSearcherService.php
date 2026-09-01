<?php 
namespace App\Services\Medical;

use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Models\Medical\MedicalModel;

final class MedicalsSearcherService 
{
    private MedicalModel $medicalModel;
    private MedicalToMedicalResponseConverter $converter;

    public function __construct() 
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalToMedicalResponseConverter();
    }

    /**
     * @return MedicalResponse[]
     */
    public function searchResponses(MedicalFilterRequest $request): array
    {
        $entities = $this->medicalModel->search($request);

        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }

        return $responses;
    }
}
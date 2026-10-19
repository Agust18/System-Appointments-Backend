<?php 
namespace App\Services\Specialty;

use App\Converter\Specialty\SpecialtyToSpecialtyResponseConverter;
use App\Dto\Request\Specialty\SpecialtyFilterRequest;
use App\Dto\Response\Specialty\SpecialtyResponse;
use App\Models\Specialty\SpecialtyModel;

final class SpecialtiesSearcherService 
{
    private SpecialtyModel $specialtyModel;
    private SpecialtyToSpecialtyResponseConverter $converter;

    public function __construct() 
    {
        $this->specialtyModel = new SpecialtyModel();
        $this->converter = new SpecialtyToSpecialtyResponseConverter();
    }

    /**
     * @return SpecialtyResponse[]
     */
    
    public function searchResponses(SpecialtyFilterRequest $request): array
    {
        $entities = $this->specialtyModel->search($request);
        $total = $this->specialtyModel->count($request);

        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        return [
            'data' => $responses,
            'total' => $total,
        ];
    }
}


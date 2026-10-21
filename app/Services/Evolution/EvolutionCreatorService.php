<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\EvolutionToEvolutionResponseConverter;
use App\Dto\Request\Evolution\EvolutionRequest;
use App\Dto\Response\Evolution\EvolutionResponse;
use App\Entity\Evolution\Evolution;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\Evolution\EvolutionModel;
use App\Models\Medical\MedicalModel;
use App\Services\Evolution\Validations\EvolutionCreateValidatorService;

final class EvolutionCreatorService
{
    private EvolutionModel $evolutionModel;
    private MedicalModel $medicalModel;
    private EvolutionToEvolutionResponseConverter $converter;
    private EvolutionCreateValidatorService $validator;

    public function __construct()
    {
        $this->evolutionModel = new EvolutionModel();
        $this->medicalModel = new MedicalModel();
        $this->converter = new EvolutionToEvolutionResponseConverter();
        $this->validator = new EvolutionCreateValidatorService();
    }

    public function create(EvolutionRequest $request, int $patientId, int $roleId, int $userId): EvolutionResponse
    {
        $this->validator->validate($request, $patientId, $roleId, $userId);

        // el médico sale de la sesión, nunca del body
        $medical = $this->medicalModel->findByUserId($userId);
        if (!$medical) {
            throw new MedicalNotFoundExceptionByUserException($userId);
        }

        $evolution = $this->evolutionModel->insert(Evolution::fromRequest($request, $patientId, $medical->getId()));

        return $this->converter->convert($evolution);
    }
}

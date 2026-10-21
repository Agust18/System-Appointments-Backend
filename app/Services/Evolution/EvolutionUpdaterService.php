<?php
namespace App\Services\Evolution;

use App\Converter\Evolution\EvolutionToEvolutionResponseConverter;
use App\Dto\Request\Evolution\EvolutionUpdateRequest;
use App\Dto\Response\Evolution\EvolutionResponse;
use App\Models\Evolution\EvolutionModel;
use App\Services\Evolution\Validations\EvolutionDraftValidatorService;

/** Edita un borrador. Una evolución firmada no se edita: se corrige con una nueva. */
final class EvolutionUpdaterService
{
    private EvolutionModel $evolutionModel;
    private EvolutionFinderService $finder;
    private EvolutionToEvolutionResponseConverter $converter;
    private EvolutionDraftValidatorService $validator;

    public function __construct()
    {
        $this->evolutionModel = new EvolutionModel();
        $this->finder = new EvolutionFinderService();
        $this->converter = new EvolutionToEvolutionResponseConverter();
        $this->validator = new EvolutionDraftValidatorService();
    }

    public function update(EvolutionUpdateRequest $request, int $patientId, int $id, int $roleId, int $userId): EvolutionResponse
    {
        $evolution = $this->finder->find($id);
        $this->validator->validate($evolution, $patientId, $roleId, $userId);

        $evolution->modify($request);

        return $this->converter->convert($this->evolutionModel->updateDraft($evolution));
    }
}

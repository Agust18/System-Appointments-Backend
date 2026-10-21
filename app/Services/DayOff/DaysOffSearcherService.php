<?php
namespace App\Services\DayOff;

use App\Constants\Role;
use App\Converter\DayOff\DayOffToDayOffResponseConverter;
use App\Dto\Request\DayOff\DayOffFilterRequest;
use App\Exception\Medical\MedicalNotFoundExceptionByUserException;
use App\Models\DayOff\DayOffModel;
use App\Models\Medical\MedicalModel;

final class DaysOffSearcherService
{
    private DayOffModel $dayOffModel;
    private MedicalModel $medicalModel;
    private DayOffToDayOffResponseConverter $converter;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
        $this->medicalModel = new MedicalModel();
        $this->converter = new DayOffToDayOffResponseConverter();
    }

    /**
     * Un médico recibe solo los bloqueos que lo afectan (los suyos más los de la clínica),
     * sin importar el `medicalId` que mande.
     *
     * @return array{data: \App\Dto\Response\DayOff\DayOffResponse[], total: int}
     */
    public function search(DayOffFilterRequest $request, int $roleId, int $userId): array
    {
        if ($roleId === Role::MEDICAL) {
            $medical = $this->medicalModel->findByUserId($userId);
            if ($medical === null) {
                throw new MedicalNotFoundExceptionByUserException($userId);
            }
            $request = $request->withMedicalId($medical->getId());
        }

        $responses = [];
        foreach ($this->dayOffModel->search($request) as $dayOff) {
            $responses[] = $this->converter->convert($dayOff);
        }

        return [
            'data' => $responses,
            'total' => $this->dayOffModel->count($request),
        ];
    }
}

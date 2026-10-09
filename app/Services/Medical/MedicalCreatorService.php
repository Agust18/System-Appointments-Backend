<?php
namespace App\Services\Medical;

use App\Constants\Role;
use App\Converter\Medical\MedicalToMedicalResponseConverter;
use App\Dto\Request\Medical\MedicalRequest;
use App\Dto\Response\Medical\MedicalResponse;
use App\Entity\Medical\Medical;
use App\Exception\User\MissingRoleIdException;
use App\Exception\User\RoleMismatchException;
use App\Models\Medical\MedicalModel;
use App\Services\Medical\Validations\MedicalCreateValidatorService;
use App\Services\User\UserCreatorService;
use CodeIgniter\Database\BaseConnection;

final class MedicalCreatorService
{
    private MedicalModel $medicalModel;
    private MedicalToMedicalResponseConverter $converter;
    private MedicalCreateValidatorService $validator;
    private UserCreatorService $userCreatorService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalToMedicalResponseConverter();
        $this->validator = new MedicalCreateValidatorService();
        $this->userCreatorService = new UserCreatorService();
        $this->db = \Config\Database::connect();
    }

    public function create(MedicalRequest $request,bool $isVerified = false): MedicalResponse
    {
        $requestedRole = $request->getUserRequest()->getRoleId();

        if ($requestedRole === null) {
            throw new MissingRoleIdException();
        }
        if ($requestedRole !== Role::MEDICAL) {
            throw new RoleMismatchException();
        }

        $this->db->transBegin();

        try {
            $newUser = $this->userCreatorService->create($request->getUserRequest(),$isVerified);

            $this->validator->validate($newUser->id, $request->getMedicalDataRequest());

            $medical = Medical::convertFromRequest($newUser->id, $request->getMedicalDataRequest());

            $newMedical = $this->medicalModel->insert($medical);

            $this->db->transCommit();

            return $this->converter->convert($newMedical);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
}

<?php
namespace App\Services\Patient;

use App\Constants\Role;
use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Dto\Request\User\UserUpdaterRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Entity\User\User;
use App\Exception\User\MissingRoleIdException;
use App\Exception\User\RoleMismatchException;
use App\Models\Patient\PatientModel;
use App\Models\User\UserModel;
use App\Services\Auth\SendVerificationEmailService;
use App\Services\Patient\Validations\PatientCreateValidatorService;
use App\Services\Patient\Validations\PatientUpdateValidatorService;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use App\Services\User\UserCreatorService;
use App\Services\User\UserFinderService;
use App\Services\User\Validations\UserUpdateValidatorService;
use CodeIgniter\Database\BaseConnection;

final class PatientCreatorService
{
    private PatientModel $patientModel;
    private UserModel $userModel;
    private PatientToPatientResponseConverter $converter;
    private PatientCreateValidatorService $validator;
    private PatientUpdateValidatorService $updateValidator;
    private UserUpdateValidatorService $userUpdateValidator;
    private UserCreatorService $userCreatorService;
    private UserFinderService $userFinderService;
    private SendVerificationEmailService $emailVerificationService;
    private TenantEmailSettingsResolverService $settingsResolver;
    private BaseConnection $db;

    public function __construct()
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->patientModel = new PatientModel();
        $this->userModel = new UserModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->validator = new PatientCreateValidatorService();
        $this->updateValidator = new PatientUpdateValidatorService();
        $this->userUpdateValidator = new UserUpdateValidatorService();
        $this->userCreatorService = new UserCreatorService();
        $this->userFinderService = new UserFinderService();
        $this->emailVerificationService = new SendVerificationEmailService();
        $this->db = \Config\Database::connect();
    }

    public function create(PatientRequest $request, bool $isVerified = false): PatientResponse
    {
        $requestedRole = $request->getUserRequest()->getRoleId();

        if ($requestedRole === null) {
            throw new MissingRoleIdException();
        }
        if ($requestedRole !== Role::PATIENT) {
            throw new RoleMismatchException();
        }

        // Antes de escribir: sin frontend_url el email de verificación no se puede armar y
        // el usuario quedaría creado sin poder verificarse.
        if (!$isVerified) {
            $this->settingsResolver->assertFrontendUrlConfigured();
        }

        $this->db->transBegin();

        try {
            // Solo el alta de recepción (isVerified) reutiliza un usuario sin cuenta que
            // ya existía por una reserva pública. El registro público nunca lo hace acá:
            // se resuelve en PatientPublicRegisterService, que no acepta contraseñas.
            $accountless = $isVerified ? $this->findAccountlessPatientUser($request) : null;

            if ($accountless !== null) {
                $newPatient = $this->completeAccountlessPatient($accountless, $request);
            } else {
                $newUser = $this->userCreatorService->create($request->getUserRequest(), $isVerified);

                $this->validator->validate($newUser->id, $request->getPatientDataRequest());

                $patient = Patient::fromRequest($newUser->id, $request->getPatientDataRequest());

                $newPatient = $this->patientModel->insert($patient);
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        if (!$isVerified) {
            $this->emailVerificationService->send(
                $this->userFinderService->find($newPatient->getUserId())
            );
        }

        $patientResponse = $this->converter->convert($newPatient);



        return $patientResponse;
    }

    private function findAccountlessPatientUser(PatientRequest $request): ?User
    {
        $user = $this->userModel->findByDni($request->getUserRequest()->getDni());

        if ($user === null || $user->hasAccount() || $user->getRoleId() !== Role::PATIENT) {
            return null;
        }

        return $user;
    }

    /**
     * Completa los datos de un usuario sin cuenta (y de su paciente, si ya tenía)
     * en lugar de crear otros. Una cuenta real nunca llega acá: su DNI sigue
     * dando 409 (DniAlreadyRegisteredException) y se edita con PUT.
     */
    private function completeAccountlessPatient(User $user, PatientRequest $request): Patient
    {
        $userRequest = $request->getUserRequest();

        $userData = new UserUpdaterRequest(
            firstName: $userRequest->getFirstName(),
            lastName: $userRequest->getLastName(),
            email: $userRequest->getEmail(),
            phone: $userRequest->getPhone(),
            password: $userRequest->getPassword(),
        );

        $this->userUpdateValidator->validate($user, $userData);

        $user->completeAsAccountless($userData);
        $this->userModel->update($user);

        $patientData = $request->getPatientDataRequest();
        $patient = $this->patientModel->findByUserId($user->getId());

        if ($patient === null) {
            $this->validator->validate($user->getId(), $patientData);

            return $this->patientModel->insert(Patient::fromRequest($user->getId(), $patientData));
        }

        $patientUpdate = new PatientUpdateRequest($patientData->getBirthDate());

        $this->updateValidator->validate($patientUpdate, $patient);
        $patient->modify($patientUpdate);

        return $this->patientModel->update($patient);
    }
}

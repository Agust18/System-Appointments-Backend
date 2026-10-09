<?php
namespace App\Services\Patient;

use App\Constants\Role;
use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Entity\User\User;
use App\Models\Patient\PatientModel;
use App\Models\User\UserModel;
use App\Services\auth\SendVerificationEmailService;
use App\Services\Patient\Validations\PatientCreateValidatorService;
use App\Services\Tenant\TenantEmailSettingsResolverService;
use CodeIgniter\Database\BaseConnection;


final class PatientPublicRegisterService
{
    private UserModel $userModel;
    private PatientModel $patientModel;
    private PatientCreatorService $patientCreatorService;
    private PatientCreateValidatorService $patientValidator;
    private PatientToPatientResponseConverter $converter;
    private SendVerificationEmailService $verificationEmailService;
    private TenantEmailSettingsResolverService $settingsResolver;
    private BaseConnection $db;

    public function __construct()
    {
        $this->settingsResolver = new TenantEmailSettingsResolverService();
        $this->userModel = new UserModel();
        $this->patientModel = new PatientModel();
        $this->patientCreatorService = new PatientCreatorService();
        $this->patientValidator = new PatientCreateValidatorService();
        $this->converter = new PatientToPatientResponseConverter();
        $this->verificationEmailService = new SendVerificationEmailService();
        $this->db = \Config\Database::connect();
    }

    public function register(PatientRequest $request): PatientResponse
    {
        // Antes de escribir: ambas ramas terminan enviando el link de verificación.
        $this->settingsResolver->assertFrontendUrlConfigured();

        $accountless = $this->findActivatableAccountless($request);

        if ($accountless === null) {
            return $this->patientCreatorService->create($request, false);
        }

        $patient = $this->setPasswordAndEnsurePatient($accountless, $request);

        $this->verificationEmailService->send($accountless);

        return $this->converter->convert($patient);
    }

    private function setPasswordAndEnsurePatient(User $user, PatientRequest $request): Patient
    {
        $this->db->transBegin();

        try {
            $user->setPasswordFromRegistration($request->getUserRequest()->getPassword());
            $this->userModel->update($user);

            $patient = $this->patientModel->findByUserId($user->getId());

            if ($patient === null) {
                $patientData = $request->getPatientDataRequest();
                $this->patientValidator->validate($user->getId(), $patientData);
                $patient = $this->patientModel->insert(Patient::fromRequest($user->getId(), $patientData));
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        return $patient;
    }

    private function findActivatableAccountless(PatientRequest $request): ?User
    {
        $userRequest = $request->getUserRequest();
        $user = $this->userModel->findByDni($userRequest->getDni());

        if (
            $user === null
            || $user->hasAccount()
            || $user->getRoleId() !== Role::PATIENT
            || !$user->getIsActive()
            || $user->getEmail() !== $userRequest->getEmail()
        ) {
            return null;
        }

        return $user;
    }
}

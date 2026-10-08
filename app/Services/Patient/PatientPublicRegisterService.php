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

/**
 * Registro público de pacientes (POST /patients/register). Un solo paso de password.
 *
 * POST /patients/register
 * │
 * ├─ Request validado (PatientPublicRegisterRequest)
 * │
 * ├─ findActivatableAccountless(dni, email)
 * │  │
 * │  ├─ MATCH (DNI de un usuario sin cuenta, rol PATIENT, activo, mismo email guardado)
 * │  │   [transacción]
 * │  │    1. user->setPasswordFromRegistration(password)  (hashea, NO toca isVerified)
 * │  │    2. userModel->update()
 * │  │    3. asegurar patient: findByUserId(); si falta → validator + insert
 * │  │   [fuera de transacción]
 * │  │    4. SendVerificationEmailService::send(user)
 * │  │    → PatientResponse (201)
 * │  │
 * │  └─ NO MATCH
 * │      PatientCreatorService::create(request, false)
 * │        - crea User con password, is_verified=0, y Patient
 * │        - manda email de verificación
 * │      → PatientResponse (201)
 * │
 * └─ 409 si el email es distinto / rol no PATIENT / inactivo / cuenta real
 *
 * Post-registro (ambos caminos): el usuario hace clic en el link de verificación
 * → VerifyEmailService marca is_verified=1 → UserLoginService permite el login.
 *
 * Solo se fija la contraseña: nombre, apellido y teléfono del usuario existente no se
 * tocan (el teléfono es el segundo factor de la reserva pública). El usuario sigue sin
 * poder iniciar sesión hasta verificar su email.
 */
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

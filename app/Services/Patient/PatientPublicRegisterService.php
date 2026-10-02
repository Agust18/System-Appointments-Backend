<?php
namespace App\Services\Patient;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\User\User;
use App\Models\User\UserModel;
use App\Services\auth\SendPasswordResetEmailService;

/**
 * Registro público de pacientes (POST /patients/register).
 *
 * Si el DNI ya pertenece a un usuario sin cuenta (creado por una reserva pública)
 * no se crea nada nuevo ni se acepta la contraseña del body: se le envía un link
 * a su email guardado para que la fije ahí. Aceptarla directamente permitiría que
 * alguien que conozca DNI + email deje su propia contraseña antes que el dueño
 * (pre-hijacking de la cuenta).
 */
final class PatientPublicRegisterService
{
    private UserModel $userModel;
    private PatientCreatorService $patientCreatorService;
    private SendPasswordResetEmailService $activationEmailService;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->patientCreatorService = new PatientCreatorService();
        $this->activationEmailService = new SendPasswordResetEmailService();
    }

    /**
     * @return PatientResponse|null null cuando se envió el link de activación a un usuario sin cuenta.
     */
    public function register(PatientRequest $request): ?PatientResponse
    {
        $accountless = $this->findActivatableAccountless($request);

        if ($accountless !== null) {
            $this->activationEmailService->send($accountless);

            return null;
        }

        return $this->patientCreatorService->create($request, false);
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

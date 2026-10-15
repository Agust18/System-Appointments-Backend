<?php
namespace App\Services\Patient;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Dto\Request\Patient\GuestPatientRequest;
use App\Entity\Patient\Patient;
use App\Entity\User\User;
use App\Exception\Patient\PatientCannotBeCreatedException;
use App\Exception\Patient\PublicBookingIdentityException;
use App\Exception\Patient\PublicIdentityLockTimeoutException;
use App\Exception\User\UserCannotBeCreatedException;
use App\Models\Patient\PatientModel;
use App\Models\User\UserModel;
use App\Support\IdentityNormalizer;
use App\Tenancy\TenantContext;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use Config\Database;

final class PatientGuestResolverService
{
    private const LOCK_TIMEOUT_SECONDS = 10;
    private const MYSQL_DUPLICATE_ENTRY = 1062;

    private BaseConnection $db;
    private UserModel $userModel;
    private PatientModel $patientModel;
    private PublicIdentityAttemptLimiter $attemptLimiter;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->userModel = new UserModel();
        $this->patientModel = new PatientModel();
        $this->attemptLimiter = new PublicIdentityAttemptLimiter();
    }

    public function resolve(GuestPatientRequest $request): Patient
    {
        // DNI bloqueado por demasiados teléfonos incorrectos: mismo 409 neutral, sin tocar la base.
        if ($this->attemptLimiter->isBlocked($request->getDni())) {
            $this->reject('dni_blocked', $request);
        }

        $lockName = sprintf('public_identity_%d_%d', TenantContext::id(), $request->getDni());

        // Serializa las solicitudes concurrentes del mismo DNI (get-or-create).
        if (!$this->acquireLock($lockName)) {
            throw new PublicIdentityLockTimeoutException();
        }

        try {
            $this->db->transBegin();

            $patient = $this->findOrCreate($request);

            $this->db->transCommit();

            // Identidad resuelta: se reinicia la cuenta de teléfonos incorrectos de ese DNI.
            $this->attemptLimiter->clear($request->getDni());

            return $patient;
        } catch (\Throwable $e) {
            // Antes del rollback: después, error() ya no conserva el código del INSERT fallido.
            $isDuplicate = $this->isDuplicateKey($e);

            $this->db->transRollback();

            // Una solicitud concurrente con el mismo email (otro DNI) pasó el chequeo y
            // ganó el UNIQUE: para quien reserva es el caso C.
            throw $isDuplicate ? new PublicBookingIdentityException() : $e;
        } finally {
            $this->releaseLock($lockName);
        }
    }

    private function findOrCreate(GuestPatientRequest $request): Patient
    {
        $user = $this->userModel->findByDni($request->getDni());

        return $user === null
            ? $this->createNewIdentity($request)
            : $this->reuseExistingIdentity($user, $request);
    }

    private function createNewIdentity(GuestPatientRequest $request): Patient
    {
        if ($this->userModel->findByEmail($request->getEmail()) !== null) {
            $this->reject('email_belongs_to_other_user', $request);
        }

        $user = $this->userModel->insert(User::createWithoutAccount(
            $request->getFirstName(),
            $request->getLastName(),
            $request->getEmail(),
            $request->getPhone(),
            $request->getDni(),
            Role::PATIENT
        ));

        return $this->createPatient($user->getId());
    }

    private function reuseExistingIdentity(User $user, GuestPatientRequest $request): Patient
    {
        if ($user->getRoleId() !== Role::PATIENT) {
            $this->reject('dni_belongs_to_non_patient', $request);
        }

        if (!$user->getIsActive()) {
            $this->reject('user_inactive', $request);
        }

        if (!$this->phoneMatches($user, $request)) {
            // Solo estos rechazos cuentan para el bloqueo: son los que delatan a alguien probando
            // teléfonos para un DNI existente (incluye el usuario sin teléfono guardado). El email de
            // otro usuario, el rol o un usuario inactivo no se pueden "adivinar" y no suman.
            $this->attemptLimiter->recordFailure($request->getDni());
            $this->reject('phone_mismatch', $request);
        }

        $this->completeMissingEmail($user, $request);

        return $this->patientModel->findByUserId($user->getId()) ?? $this->createPatient($user->getId());
    }

    /**
     * Paciente cargado sin email por recepción: DNI y teléfono ya coincidieron, así que se guarda
     * el email que escribió. Si ese email es de otro usuario, el turno se reserva igual sin guardarlo.
     */
    private function completeMissingEmail(User $user, GuestPatientRequest $request): void
    {
        if ($user->hasEmail() || $this->userModel->findByEmail($request->getEmail()) !== null) {
            return;
        }

        $user->completeEmail($request->getEmail());
        $this->userModel->update($user);
    }

    /**
     * Un usuario sin teléfono guardado nunca coincide: completarlo desde la reserva
     * pública permitiría apropiarse de un DNI ajeno conociendo solo el DNI.
     */
    private function phoneMatches(User $user, GuestPatientRequest $request): bool
    {
        $storedPhone = IdentityNormalizer::phone($user->getPhone());

        return $storedPhone !== null && hash_equals($storedPhone, $request->getPhone());
    }

    private function createPatient(int $userId): Patient
    {
        return $this->patientModel->insert(Patient::fromRequest($userId, new PatientDataRequest(null)));
    }

    private function reject(string $reason, GuestPatientRequest $request): never
    {
        // DNI hasheado: el log no debe contener datos personales.
        log_message('notice', sprintf(
            'Reserva pública rechazada (%s) dni_hash=%s',
            $reason,
            substr(hash('sha256', (string) $request->getDni()), 0, 12)
        ));

        throw new PublicBookingIdentityException();
    }

    private function isDuplicateKey(\Throwable $e): bool
    {
        if ($e instanceof DatabaseException) {
            return str_contains($e->getMessage(), 'Duplicate entry');
        }

        // Dentro de una transacción el INSERT fallido no lanza: los Models detectan
        // insertID() = 0 y lanzan su excepción; el código real queda en error().
        if ($e instanceof UserCannotBeCreatedException || $e instanceof PatientCannotBeCreatedException) {
            return (int) ($this->db->error()['code'] ?? 0) === self::MYSQL_DUPLICATE_ENTRY;
        }

        return false;
    }

    private function acquireLock(string $lockName): bool
    {
        $result = $this->db->query('SELECT GET_LOCK(?, ?) AS is_locked', [$lockName, self::LOCK_TIMEOUT_SECONDS]);
        $row = $result->getRow();

        return $row && (int) $row->is_locked === 1;
    }

    private function releaseLock(string $lockName): void
    {
        $this->db->query('SELECT RELEASE_LOCK(?) AS is_released', [$lockName]);
    }
}

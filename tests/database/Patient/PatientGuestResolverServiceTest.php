<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PublicPatientRequest;
use App\Exception\Patient\PublicBookingIdentityException;
use App\Exception\Patient\PublicIdentityLockTimeoutException;
use App\Services\Patient\PatientGuestResolverService;
use App\Services\Patient\PublicIdentityAttemptLimiter;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use CodeIgniter\Test\Mock\MockCache;
use Config\Database;
use Config\Services;

/**
 * Casos A–E de la reserva pública (ver PatientGuestResolverService).
 *
 * @internal
 */
final class PatientGuestResolverServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private const DNI = 30123456;
    private const PHONE = '2325506499';

    private PatientGuestResolverService $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        // En tests el cache es 'dummy' (el bloqueo por DNI no cuenta nada). Estos tests usan un
        // cache en memoria, propio de cada test, para ejercitar el bloqueo sin estado compartido.
        $this->resetServices();
        Services::injectMock('cache', new MockCache());

        $this->resolver = new PatientGuestResolverService();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    protected function tearDown(): void
    {
        $this->resetServices();
        parent::tearDown();
    }

    /**
     * Intenta resolver el DNI con un teléfono que no coincide, $times veces, esperando el rechazo.
     */
    private function failPhoneAttempts(int $times, int $dni = self::DNI): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->assertRejected(
                fn () => $this->resolver->resolve($this->request($dni, "intento{$i}@test.com", '1144440000'))
            );
        }
    }

    private function request(
        int $dni = self::DNI,
        string $email = 'nuevo@test.com',
        string $phone = self::PHONE,
        string $firstName = 'Juana'
    ): PublicPatientRequest {
        // Nombre único: el down() de ReplaceUsernameWithFirstLastName reconstruye
        // users.username (UNIQUE) desde nombre + apellido al refrescar la base.
        return new PublicPatientRequest($firstName . '_' . uniqid(), 'Pérez', $email, $phone, $dni);
    }

    private function seedUser(
        int $dni = self::DNI,
        string $email = 'existente@test.com',
        ?string $phone = self::PHONE,
        ?int $roleId = Role::PATIENT,
        bool $active = true,
        ?string $password = null
    ): int {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name'  => 'existente_' . uniqid(),
            'last_name'   => 'Original',
            'email'       => $email,
            'phone'       => $phone,
            'password'    => $password,
            'dni'         => $dni,
            'is_active'   => $active ? 1 : 0,
            'is_verified' => 0,
            'role_id'     => $roleId,
        ]);

        return (int) $this->db->insertID();
    }

    private function seedPatientFor(int $userId): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => '1990-01-01']);

        return (int) $this->db->insertID();
    }

    private function assertIdentityLockReleased(int $dni = self::DNI): void
    {
        $row = $this->db->query('SELECT IS_FREE_LOCK(?) AS is_free', ['public_identity_' . $dni])->getRow();

        $this->assertSame(1, (int) $row->is_free, 'El lock por DNI debe quedar liberado.');
    }

    private function assertRejected(callable $resolve): PublicBookingIdentityException
    {
        try {
            $resolve();
        } catch (PublicBookingIdentityException $e) {
            $this->assertSame(409, $e->getCode());

            return $e;
        }

        $this->fail('Se esperaba PublicBookingIdentityException');
    }

    // --------------------------------------------------------------- Caso A

    public function testNewDniAndEmailCreateAnAccountlessUserAndItsPatient(): void
    {
        $request = $this->request();

        $patient = $this->resolver->resolve($request);

        $this->assertNotNull($patient->getId());
        $this->assertNull($patient->getBirthDate());
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('users', [
            'id'          => $patient->getUserId(),
            'first_name'  => $request->getFirstName(),
            'last_name'   => 'Pérez',
            'email'       => 'nuevo@test.com',
            'phone'       => self::PHONE,
            'dni'         => self::DNI,
            'password'    => null,
            'is_active'   => 1,
            'is_verified' => 0,
            'role_id'     => Role::PATIENT,
        ]);
        $this->assertIdentityLockReleased();
    }

    public function testResolvingTheSameRequestTwiceIsIdempotent(): void
    {
        $first = $this->resolver->resolve($this->request());
        $second = $this->resolver->resolve($this->request());

        $this->assertSame($first->getId(), $second->getId());
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
    }

    // ------------------------------------------------------------ Casos B/E

    public function testExistingUserWithMatchingPhoneReusesItsPatientAndChangesNothing(): void
    {
        $userId = $this->seedUser();
        $patientId = $this->seedPatientFor($userId);
        $before = $this->db->table('users')->where('id', $userId)->get()->getRowArray();

        $patient = $this->resolver->resolve($this->request(self::DNI, 'otro-email@test.com', self::PHONE, 'Distinto'));

        $this->assertSame($patientId, $patient->getId());
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
        $this->assertSame($before, $this->db->table('users')->where('id', $userId)->get()->getRowArray());
        $this->assertIdentityLockReleased();
    }

    public function testStoredPhoneWithSeparatorsStillMatches(): void
    {
        $userId = $this->seedUser(self::DNI, 'existente@test.com', '2325-506499');
        $patientId = $this->seedPatientFor($userId);

        $patient = $this->resolver->resolve($this->request());

        $this->assertSame($patientId, $patient->getId());
    }

    public function testAccountHolderWithMatchingPhoneIsReusedAndKeepsItsPassword(): void
    {
        $hash = password_hash('contrasenia123', PASSWORD_BCRYPT);
        $userId = $this->seedUser(self::DNI, 'titular@test.com', self::PHONE, Role::PATIENT, true, $hash);
        $patientId = $this->seedPatientFor($userId);

        $patient = $this->resolver->resolve($this->request());

        $this->assertSame($patientId, $patient->getId());
        $this->seeInDatabase('users', ['id' => $userId, 'password' => $hash]);
    }

    // --------------------------------------------------------------- Caso D

    public function testExistingUserWithMatchingPhoneAndNoPatientGetsOne(): void
    {
        $userId = $this->seedUser();

        $patient = $this->resolver->resolve($this->request());

        $this->assertSame($userId, $patient->getUserId());
        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('patients', ['id' => $patient->getId(), 'user_id' => $userId, 'birth_date' => null]);
    }

    // -------------------------------------------------------------- Caso B'

    public function testPhoneMismatchIsRejectedAndWritesNothing(): void
    {
        $userId = $this->seedUser();
        $this->seedPatientFor($userId);

        $this->assertRejected(fn () => $this->resolver->resolve($this->request(self::DNI, 'otro@test.com', '1144445555')));

        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('users', ['id' => $userId, 'email' => 'existente@test.com', 'phone' => self::PHONE]);
        $this->assertIdentityLockReleased();
    }

    public function testUserWithoutPhoneIsRejectedAndItsPhoneIsNeverCompleted(): void
    {
        $userId = $this->seedUser(self::DNI, 'existente@test.com', null);
        $this->seedPatientFor($userId);

        $this->assertRejected(fn () => $this->resolver->resolve($this->request()));

        $this->seeInDatabase('users', ['id' => $userId, 'phone' => null]);
    }

    public function testDniOfANonPatientIsRejected(): void
    {
        $this->db->table('roles')->insert(['id' => Role::MEDICAL, 'name' => 'medical']);
        $this->seedUser(self::DNI, 'medico@test.com', self::PHONE, Role::MEDICAL);

        $this->assertRejected(fn () => $this->resolver->resolve($this->request()));

        $this->seeNumRecords(0, 'patients', []);
    }

    public function testInactiveUserIsRejected(): void
    {
        $userId = $this->seedUser(self::DNI, 'inactivo@test.com', self::PHONE, Role::PATIENT, false);
        $this->seedPatientFor($userId);

        $this->assertRejected(fn () => $this->resolver->resolve($this->request()));
    }

    // --------------------------------------------------------------- Caso C

    public function testEmailOfAnotherUserIsRejectedAndWritesNothing(): void
    {
        $this->seedUser(30999999, 'ocupado@test.com');

        $this->assertRejected(fn () => $this->resolver->resolve($this->request(self::DNI, 'ocupado@test.com')));

        $this->seeNumRecords(1, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
        $this->assertIdentityLockReleased();
    }

    public function testCaseBPrimeAndCaseCAreIndistinguishableForTheCaller(): void
    {
        $this->seedUser(30999999, 'ocupado@test.com');
        $this->seedUser(self::DNI, 'existente@test.com', self::PHONE);

        $phoneMismatch = $this->assertRejected(
            fn () => $this->resolver->resolve($this->request(self::DNI, 'otro@test.com', '1144445555'))
        );
        $emailTaken = $this->assertRejected(
            fn () => $this->resolver->resolve($this->request(30111111, 'ocupado@test.com'))
        );

        $this->assertSame($phoneMismatch->getMessage(), $emailTaken->getMessage());
        $this->assertSame($phoneMismatch->getCode(), $emailTaken->getCode());
    }

    // ------------------------------------------------ transacción y concurrencia

    public function testUserInsertIsRolledBackWhenThePatientInsertFails(): void
    {
        // Para que falle el INSERT de patients (y comprobar que el usuario recién creado no
        // queda huérfano) se siembra un paciente con el user_id que el resolver va a generar:
        // el UNIQUE de patients.user_id lo rechaza. El id siguiente se averigua con una sonda
        // (AUTO_INCREMENT no retrocede al borrar) y la FK se apaga solo para sembrar ese huérfano.
        $probeId = $this->seedUser(30000001, 'sonda@test.com');
        $this->db->table('users')->delete(['id' => $probeId]);
        $nextUserId = $probeId + 1;

        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $nextUserId, 'birth_date' => '1990-01-01']);
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');

        try {
            $this->assertRejected(fn () => $this->resolver->resolve($this->request()));
        } finally {
            $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
            $this->db->table('patients')->delete(['user_id' => $nextUserId]);
            $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
        }

        $this->seeNumRecords(0, 'users', []);
        $this->assertIdentityLockReleased();
    }

    public function testLosingTheEmailRaceIsMappedToTheNeutralRejection(): void
    {
        // Una solicitud concurrente (otro DNI, mismo email) confirma su INSERT después de que
        // esta transacción tomó su snapshot: el chequeo por email no la ve y el UNIQUE decide.
        $this->db->transBegin();
        $this->db->query('SELECT COUNT(*) FROM users');

        $other = Database::connect('tests', false);
        $other->query(
            'INSERT INTO users (tenant_id, first_name, last_name, email, password, dni, role_id) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [1, 'ganador_' . uniqid(), 'Concurrente', 'carrera@test.com', null, 30999999, Role::PATIENT]
        );
        $other->close();

        try {
            $this->assertRejected(fn () => $this->resolver->resolve($this->request(self::DNI, 'carrera@test.com')));
        } finally {
            $this->db->transRollback();
        }

        $this->assertIdentityLockReleased();
    }

    public function testBusyIdentityLockTimesOutWithoutWritingAnything(): void
    {
        $holder = Database::connect('tests', false);
        $holder->query('SELECT GET_LOCK(?, 0)', ['public_identity_' . self::DNI]);

        try {
            $this->resolver->resolve($this->request());
            $this->fail('Se esperaba PublicIdentityLockTimeoutException');
        } catch (PublicIdentityLockTimeoutException $e) {
            $this->assertSame(423, $e->getCode());
        } finally {
            $holder->query('SELECT RELEASE_LOCK(?)', ['public_identity_' . self::DNI]);
            $holder->close();
        }

        $this->seeNumRecords(0, 'users', []);
    }

    // ------------------------------------------------------- bloqueo por DNI

    public function testFiveWrongPhonesBlockTheDniEvenForTheCorrectPhone(): void
    {
        $userId = $this->seedUser();
        $this->seedPatientFor($userId);

        $this->failPhoneAttempts(5);

        // Se borra el usuario: si el resolver consultara la base vería un DNI nuevo y crearía uno
        // (caso A). Rechaza igual, y con el mismo mensaje, porque el DNI está bloqueado.
        $this->db->table('patients')->delete(['user_id' => $userId]);
        $this->db->table('users')->delete(['id' => $userId]);

        $blocked = $this->assertRejected(fn () => $this->resolver->resolve($this->request()));

        $this->seeNumRecords(0, 'users', []);
        $this->seeNumRecords(0, 'patients', []);
        $this->assertSame(
            (new PublicBookingIdentityException())->getMessage(),
            $blocked->getMessage()
        );
    }

    public function testFourWrongPhonesDoNotBlockTheDni(): void
    {
        $userId = $this->seedUser();
        $patientId = $this->seedPatientFor($userId);

        $this->failPhoneAttempts(4);

        $patient = $this->resolver->resolve($this->request());

        $this->assertSame($patientId, $patient->getId());
    }

    public function testASuccessfulResolutionResetsTheCounter(): void
    {
        $userId = $this->seedUser();
        $patientId = $this->seedPatientFor($userId);

        $this->failPhoneAttempts(4);
        $this->assertSame($patientId, $this->resolver->resolve($this->request())->getId());

        // Sin el reinicio serían 8 fallos acumulados y el DNI estaría bloqueado.
        $this->failPhoneAttempts(4);
        $this->assertSame($patientId, $this->resolver->resolve($this->request())->getId());
    }

    public function testFailuresAreCountedPerDni(): void
    {
        $userId = $this->seedUser();
        $this->seedPatientFor($userId);
        $otherUserId = $this->seedUser(30999999, 'otro@test.com', '1155556666');
        $otherPatientId = $this->seedPatientFor($otherUserId);

        $this->failPhoneAttempts(5);

        $patient = $this->resolver->resolve($this->request(30999999, 'x@test.com', '1155556666'));

        $this->assertSame($otherPatientId, $patient->getId());
    }

    public function testAUserWithoutPhoneCountsTowardsTheBlock(): void
    {
        $userId = $this->seedUser(self::DNI, 'existente@test.com', null);
        $this->seedPatientFor($userId);

        $this->failPhoneAttempts(5);

        // Aunque recepción le cargue el teléfono después, el DNI sigue bloqueado.
        $this->db->table('users')->where('id', $userId)->update(['phone' => self::PHONE]);

        $this->assertRejected(fn () => $this->resolver->resolve($this->request()));
    }

    public function testRejectionsThatCannotBeGuessedDoNotCountTowardsTheBlock(): void
    {
        $limiter = new PublicIdentityAttemptLimiter();

        // Rol distinto a paciente.
        $this->db->table('roles')->insert(['id' => Role::MEDICAL, 'name' => 'medical']);
        $this->seedUser(30111111, 'medico@test.com', self::PHONE, Role::MEDICAL);
        // Usuario inactivo.
        $inactive = $this->seedUser(30222222, 'inactivo@test.com', self::PHONE, Role::PATIENT, false);
        $this->seedPatientFor($inactive);
        // Email que pertenece a otro usuario (caso C).
        $this->seedUser(30333333, 'ocupado@test.com');

        for ($i = 0; $i < 6; $i++) {
            $this->assertRejected(fn () => $this->resolver->resolve($this->request(30111111, 'a@test.com')));
            $this->assertRejected(fn () => $this->resolver->resolve($this->request(30222222, 'b@test.com')));
            $this->assertRejected(fn () => $this->resolver->resolve($this->request(30444444, 'ocupado@test.com')));
        }

        $this->assertFalse($limiter->isBlocked(30111111));
        $this->assertFalse($limiter->isBlocked(30222222));
        $this->assertFalse($limiter->isBlocked(30444444));
    }

    public function testNewIdentitiesAreNeverBlocked(): void
    {
        $patient = $this->resolver->resolve($this->request());

        $this->assertNotNull($patient->getId());
        $this->assertFalse((new PublicIdentityAttemptLimiter())->isBlocked(self::DNI));
    }

    public function testLockOfOneDniDoesNotBlockAnotherDni(): void
    {
        $holder = Database::connect('tests', false);
        $holder->query('SELECT GET_LOCK(?, 0)', ['public_identity_' . self::DNI]);

        try {
            $patient = $this->resolver->resolve($this->request(30555555, 'otro-dni@test.com'));
            $this->assertNotNull($patient->getId());
        } finally {
            $holder->query('SELECT RELEASE_LOCK(?)', ['public_identity_' . self::DNI]);
            $holder->close();
        }
    }
}

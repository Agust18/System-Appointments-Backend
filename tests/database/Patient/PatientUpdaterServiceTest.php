<?php

use App\Constants\Role;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Services\Patient\PatientUpdaterService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * PUT /patients/{id}: recepción puede corregir los datos de contacto del
 * paciente (que viven en users) además de su fecha de nacimiento.
 *
 * @internal
 */
final class PatientUpdaterServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientUpdaterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientUpdaterService();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    /**
     * @return array{0:int,1:int} [userId, patientId]
     */
    private function seedPatient(string $email, int $dni, ?string $phone = null, ?string $password = null): array
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name' => 'paciente_' . uniqid(),
            'last_name'  => 'Apellido',
            'email'      => $email,
            'phone'      => $phone,
            'password'   => $password,
            'dni'        => $dni,
            'role_id'    => Role::PATIENT,
        ]);
        $userId = (int) $this->db->insertID();

        $this->db->table('patients')->insert(['tenant_id' => 1, 'user_id' => $userId, 'birth_date' => '1990-01-01']);

        return [$userId, (int) $this->db->insertID()];
    }

    public function testCompletesTheMissingPhoneOfAPatient(): void
    {
        [$userId, $patientId] = $this->seedPatient('paciente@test.com', 30123456);

        $this->service->update(new PatientUpdateRequest(null, phone: '2325506499'), $patientId);

        $this->seeInDatabase('users', ['id' => $userId, 'phone' => '2325506499', 'email' => 'paciente@test.com']);
        $this->seeInDatabase('patients', ['id' => $patientId, 'birth_date' => '1990-01-01']);
    }

    public function testUpdatesUserAndPatientTogether(): void
    {
        [$userId, $patientId] = $this->seedPatient('paciente@test.com', 30123456);

        $response = $this->service->update(
            new PatientUpdateRequest('1985-03-10', firstName: 'Nuevo', phone: '1144445555', email: 'nuevo@test.com'),
            $patientId
        );

        $this->assertSame('1985-03-10', $response->birthDate);
        $this->seeInDatabase('users', [
            'id'         => $userId,
            'first_name' => 'Nuevo',
            'phone'      => '1144445555',
            'email'      => 'nuevo@test.com',
        ]);
    }

    public function testBirthDateOnlyUpdateDoesNotTouchTheUser(): void
    {
        [$userId, $patientId] = $this->seedPatient('paciente@test.com', 30123456, '2325506499');

        $this->service->update(new PatientUpdateRequest('1985-03-10'), $patientId);

        $this->seeInDatabase('users', ['id' => $userId, 'email' => 'paciente@test.com', 'phone' => '2325506499']);
    }

    public function testEmailOfAnotherUserIsRejectedAndNothingIsUpdated(): void
    {
        [$userId, $patientId] = $this->seedPatient('paciente@test.com', 30123456, '2325506499');
        $this->seedPatient('ocupado@test.com', 30999999);

        try {
            $this->service->update(
                new PatientUpdateRequest('1985-03-10', phone: '1144445555', email: 'ocupado@test.com'),
                $patientId
            );
            $this->fail('Se esperaba EmailAlreadyRegisteredException');
        } catch (Exception $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeInDatabase('users', ['id' => $userId, 'email' => 'paciente@test.com', 'phone' => '2325506499']);
        $this->seeInDatabase('patients', ['id' => $patientId, 'birth_date' => '1990-01-01']);
    }

    public function testUserUpdateIsRolledBackWhenTheBirthDateIsInvalid(): void
    {
        [$userId, $patientId] = $this->seedPatient('paciente@test.com', 30123456);
        $future = (new DateTime('+1 day'))->format('Y-m-d');

        try {
            $this->service->update(new PatientUpdateRequest($future, phone: '1144445555'), $patientId);
            $this->fail('Se esperaba InvalidBirthDateException');
        } catch (Exception $e) {
            // esperado
        }

        $this->seeInDatabase('users', ['id' => $userId, 'phone' => null]);
    }

    public function testDoesNotChangeThePasswordOfAnAccountHolder(): void
    {
        $hash = password_hash('contrasenia123', PASSWORD_BCRYPT);
        [$userId, $patientId] = $this->seedPatient('titular@test.com', 30123456, null, $hash);

        $this->service->update(new PatientUpdateRequest(null, phone: '2325506499'), $patientId);

        $this->seeInDatabase('users', ['id' => $userId, 'password' => $hash]);
    }
}

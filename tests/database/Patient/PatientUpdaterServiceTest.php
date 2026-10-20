<?php

use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Exception\Patient\InvalidBirthDateException;
use App\Services\Patient\PatientUpdaterService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * PUT /patients/{id}: recepción corrige los datos del paciente directamente en `patients`.
 * El DNI no se modifica.
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
    }

    private function seedPatient(?string $phone = null, ?string $email = 'paciente@test.com'): int
    {
        $this->db->table('patients')->insert([
            'tenant_id'  => 1,
            'first_name' => 'Ana',
            'last_name'  => 'Gómez',
            'email'      => $email,
            'phone'      => $phone,
            'dni'        => 30123456,
            'birth_date' => '1990-01-01',
        ]);

        return (int) $this->db->insertID();
    }

    public function testCompletesTheMissingPhoneAndKeepsTheRest(): void
    {
        $patientId = $this->seedPatient();

        $this->service->update(new PatientUpdateRequest(null, phone: '2325506499'), $patientId);

        $this->seeInDatabase('patients', [
            'id'         => $patientId,
            'phone'      => '2325506499',
            'email'      => 'paciente@test.com',
            'first_name' => 'Ana',
            'dni'        => 30123456,
            'birth_date' => '1990-01-01',
        ]);
    }

    public function testUpdatesAllTheEditableFieldsTogether(): void
    {
        $patientId = $this->seedPatient();

        $response = $this->service->update(
            new PatientUpdateRequest('1985-03-10', firstName: 'Nuevo', lastName: 'Apellido', phone: '1144445555', email: 'nuevo@test.com'),
            $patientId
        );

        $this->assertSame('1985-03-10', $response->birthDate);
        $this->seeInDatabase('patients', [
            'id'         => $patientId,
            'first_name' => 'Nuevo',
            'last_name'  => 'Apellido',
            'phone'      => '1144445555',
            'email'      => 'nuevo@test.com',
            'dni'        => 30123456,
        ]);
    }

    public function testAnInvalidBirthDateLeavesTheRecordUntouched(): void
    {
        $patientId = $this->seedPatient();
        $future = (new DateTime('+1 day'))->format('Y-m-d');

        $this->expectException(InvalidBirthDateException::class);

        try {
            $this->service->update(new PatientUpdateRequest($future, phone: '1144445555'), $patientId);
        } finally {
            $this->seeInDatabase('patients', ['id' => $patientId, 'phone' => null, 'birth_date' => '1990-01-01']);
        }
    }
}

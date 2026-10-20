<?php

use App\Dto\Request\Patient\PatientRequest;
use App\Exception\Patient\InvalidBirthDateException;
use App\Exception\Patient\PatientAlreadyExistsException;
use App\Services\Patient\PatientCreatorService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * El paciente es una ficha propia en `patients`: no crea ni toca `users`.
 *
 * @internal
 */
final class PatientCreatorServiceTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private PatientCreatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PatientCreatorService();
    }

    private function request(
        int $dni,
        ?string $email = 'paciente@test.com',
        string $firstName = 'Ana',
        ?string $phone = '1155550000',
        ?string $birthDate = null
    ): PatientRequest {
        return new PatientRequest($firstName, 'Gómez', $email, $phone, $dni, $birthDate);
    }

    public function testCreateStoresTheIdentityInPatientsAndCreatesNoUser(): void
    {
        $response = $this->service->create($this->request(30123456, 'ana@test.com', 'Ana', '1155550000', '1990-05-20'));

        $this->assertSame(30123456, $response->dni);
        $this->assertSame('1990-05-20', $response->birthDate);
        $this->seeInDatabase('patients', [
            'id'         => $response->id,
            'first_name' => 'Ana',
            'last_name'  => 'Gómez',
            'email'      => 'ana@test.com',
            'phone'      => '1155550000',
            'dni'        => 30123456,
        ]);
        $this->seeNumRecords(0, 'users', []);
    }

    public function testSeveralPatientsWithoutEmailAreAllowed(): void
    {
        $this->service->create($this->request(30123456, null));
        $this->service->create($this->request(30123457, null));

        $this->seeNumRecords(2, 'patients', ['email' => null]);
    }

    public function testCreateWithAnExistingDniReturns409AndChangesNothing(): void
    {
        $this->service->create($this->request(30123456, 'original@test.com', 'Original'));

        try {
            $this->service->create($this->request(30123456, 'otro@test.com', 'Otro'));
            $this->fail('Se esperaba PatientAlreadyExistsException');
        } catch (PatientAlreadyExistsException $e) {
            $this->assertSame(409, $e->getCode());
        }

        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('patients', ['dni' => 30123456, 'first_name' => 'Original', 'email' => 'original@test.com']);
    }

    public function testCreateRejectsAFutureBirthDateAndWritesNothing(): void
    {
        $future = (new DateTime('+1 day'))->format('Y-m-d');

        $this->expectException(InvalidBirthDateException::class);

        try {
            $this->service->create($this->request(30123456, 'ana@test.com', 'Ana', '1155550000', $future));
        } finally {
            $this->seeNumRecords(0, 'patients', []);
        }
    }

    public function testFindOrCreateByDniCreatesWhenTheDniIsNew(): void
    {
        $patient = $this->service->findOrCreateByDni($this->request(30123456, 'ana@test.com'));

        $this->assertNotNull($patient->getId());
        $this->seeNumRecords(1, 'patients', ['dni' => 30123456]);
    }

    public function testFindOrCreateByDniReusesTheExistingRecordWithoutModifyingIt(): void
    {
        $original = $this->service->create($this->request(30123456, 'original@test.com', 'Original', '1144445555'));

        $reused = $this->service->findOrCreateByDni($this->request(30123456, 'tipeado@test.com', 'Tipeado', '1100000000'));

        $this->assertSame($original->id, $reused->getId());
        $this->seeNumRecords(1, 'patients', []);
        $this->seeInDatabase('patients', [
            'id'         => $original->id,
            'first_name' => 'Original',
            'email'      => 'original@test.com',
            'phone'      => '1144445555',
        ]);
    }
}

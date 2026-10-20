<?php

use App\Constants\Role;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Exception\Validation\ValidationException;
use CodeIgniter\Config\Factories;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\TestResponse;

require_once __DIR__ . '/../Appointment/BookingServiceTestCase.php';

/**
 * Endpoints públicos /public/*: recorren el router y los filtros reales, sin
 * header Authorization. Las excepciones de dominio suben al test (el mapeo de su
 * código a HTTP vive en ApiExceptionHandler, que corta con exit), por eso se
 * verifica su clase y su código.
 *
 * El envío real de emails queda deshabilitado vaciando EMAIL_HOST.
 *
 * @internal
 */
final class PublicBookingEndpointsTest extends BookingServiceTestCase
{
    use FeatureTestTrait;

    private const ENV_KEYS = ['cors.allowedOrigins', 'cors.allowedOriginsPatterns', 'EMAIL_HOST'];

    /** @var array<string, mixed> */
    private array $previousEnv = [];

    /** Un lunes (day_of_week = 1) siempre futuro. */
    private string $monday;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (self::ENV_KEYS as $key) {
            $this->previousEnv[$key] = $_ENV[$key] ?? null;
            unset($_ENV[$key], $_SERVER[$key]);
        }
        $_ENV['EMAIL_HOST'] = '';
        Factories::reset('config');
        // service('response') es compartido: sin resetearlo, los headers de un request (p. ej.
        // Access-Control-Allow-Origin) quedan en la respuesta del siguiente.
        $this->resetServices();

        $this->monday = (new DateTime('next monday'))->modify('+7 days')->format('Y-m-d');
        session()->destroy();
    }

    protected function tearDown(): void
    {
        foreach (self::ENV_KEYS as $key) {
            if ($this->previousEnv[$key] === null) {
                unset($_ENV[$key]);
            } else {
                $_ENV[$key] = $this->previousEnv[$key];
            }
        }
        Factories::reset('config');

        parent::tearDown();
    }

    // ---------------------------------------------------------------- helpers

    private function configureCors(string $origins, string $patterns = ''): void
    {
        $_ENV['cors.allowedOrigins'] = $origins;
        $_ENV['cors.allowedOriginsPatterns'] = $patterns;
        Factories::reset('config');
    }

    private function decode(TestResponse $result): array
    {
        return json_decode($result->getJSON(), true);
    }

    private function seedSchedule(int $medicalId): void
    {
        $this->db->table('medical_schedules')->insert(['tenant_id' => 1,
            'medical_id'  => $medicalId,
            'day_of_week' => 1,
            'start_time'  => '09:00:00',
            'end_time'    => '12:00:00',
            'is_active'   => 1,
        ]);
    }

    /**
     * @return array{0:int,1:int} [medicalId, serviceId] con agenda de lunes 09:00-12:00
     */
    private function bookableScenario(): array
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30);
        $this->seedSchedule($medicalId);

        return [$medicalId, $serviceId];
    }

    private function bookingPayload(int $medicalId, int $serviceId, array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'Juana_' . uniqid(),
            'lastName'  => 'Pérez',
            'email'     => 'juana@test.com',
            'phone'     => '2325506499',
            'dni'       => '30123456',
            'medicalId' => $medicalId,
            'serviceId' => $serviceId,
            'fecha'     => $this->monday,
            'hora'      => '09:30',
            'nota'      => 'Primera consulta',
        ], $overrides);
    }

    private function book(array $payload): TestResponse
    {
        return $this->withBodyFormat('json')->post('public/appointments', $payload);
    }

    private function seedExistingPatient(int $dni, string $email, ?string $phone): int
    {
        $this->db->table('patients')->insert(['tenant_id' => 1,
            'first_name' => 'Original',
            'last_name'  => 'Registrado',
            'email'      => $email,
            'phone'      => $phone,
            'dni'        => $dni,
            'birth_date' => null,
        ]);

        return (int) $this->db->insertID();
    }

    // ------------------------------------------------------- catálogo público

    public function testSpecialtiesAreListedWithoutAuthentication(): void
    {
        $this->seedMedical();

        $result = $this->get('public/specialties');

        $result->assertStatus(200);
        $json = $this->decode($result);
        $this->assertSame(1, $json['total']);
        $this->assertSame(['id', 'name', 'description', 'createdAt', 'updatedAt'], array_keys($json['data'][0]));
    }

    public function testListsIgnoreTheClientPagination(): void
    {
        $this->seedMedical();

        foreach (['public/specialties', 'public/services', 'public/medicals/available'] as $path) {
            $result = $this->get($path, ['page' => '0', 'size' => '-5']);

            $result->assertStatus(200);
        }

        $result = $this->get('public/specialties', ['size' => '100000']);
        $this->assertCount(1, $this->decode($result)['data']);
    }

    public function testMedicalsListsOnlyActiveOnesWithMinimalFields(): void
    {
        $active = $this->seedMedical();
        $inactiveUser = $this->seedMedical();
        $userId = (int) $this->db->table('medicals')->where('id', $inactiveUser)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $userId)->update(['is_active' => 0]);

        $result = $this->get('public/medicals/available');

        $result->assertStatus(200);
        $json = $this->decode($result);
        $this->assertSame(1, $json['total']);
        $this->assertSame($active, $json['data'][0]['id']);
        $this->assertSame(['id', 'medicoNombre', 'especialidadNombre'], array_keys($json['data'][0]));
        $this->assertStringNotContainsString('@test.com', $result->getJSON());
        $this->assertStringNotContainsString('MAT-', $result->getJSON());
    }

    public function testMedicalsCanBeFilteredBySpecialty(): void
    {
        $first = $this->seedMedical();
        $this->seedMedical();
        $specialtyId = (int) $this->db->table('medicals')->where('id', $first)->get()->getRow()->specialty_id;

        $json = $this->decode($this->get('public/medicals/available', ['specialtyId' => (string) $specialtyId]));

        $this->assertSame(1, $json['total']);
        $this->assertSame($first, $json['data'][0]['id']);
    }

    public function testServicesListsOnlyActiveOnesWithMinimalFieldsAndIgnoresTheIsActiveFilter(): void
    {
        $active = $this->seedService(30, 15);
        $this->seedService(30, 0, false);

        $result = $this->get('public/services', ['isActive' => '0']);

        $result->assertStatus(200);
        $json = $this->decode($result);
        $this->assertSame(1, $json['total']);
        $this->assertSame($active, $json['data'][0]['id']);
        $this->assertSame(['id', 'name', 'description', 'durationMinutes', 'price'], array_keys($json['data'][0]));
    }

    public function testMedicalServicesReturnTheEffectiveDurationAndPrice(): void
    {
        [$medicalId, $serviceId] = $this->seedMedicalWithService(30, 15, 45);
        $this->db->table('medical_service')
            ->where(['medical_id' => $medicalId, 'service_id' => $serviceId])
            ->update(['custom_price' => 9000]);

        $result = $this->get("public/medicals/{$medicalId}/services");

        $result->assertStatus(200);
        $json = $this->decode($result);
        $this->assertCount(1, $json);
        $this->assertSame(['serviceId', 'name', 'description', 'durationMinutes', 'price'], array_keys($json[0]));
        $this->assertSame($serviceId, $json[0]['serviceId']);
        $this->assertSame(45, $json[0]['durationMinutes']);
        $this->assertEquals(9000, $json[0]['price']);
    }

    public function testMedicalServicesExcludeInactiveRelationsAndInactiveServices(): void
    {
        [$medicalId, $offered] = $this->seedMedicalWithService(30);
        $inactiveService = $this->seedService(30, 0, false);
        $this->offerService($medicalId, $inactiveService);
        $deactivated = $this->seedService(30, 0);
        $this->offerService($medicalId, $deactivated);
        $this->db->table('medical_service')
            ->where(['medical_id' => $medicalId, 'service_id' => $deactivated])
            ->update(['is_active' => 0]);

        $json = $this->decode($this->get("public/medicals/{$medicalId}/services"));

        $this->assertSame([$offered], array_column($json, 'serviceId'));
    }

    public function testMedicalServicesOfAnInactiveMedicalAreRejectedWith409(): void
    {
        [$medicalId] = $this->seedMedicalWithService(30);
        $userId = (int) $this->db->table('medicals')->where('id', $medicalId)->get()->getRow()->user_id;
        $this->db->table('users')->where('id', $userId)->update(['is_active' => 0]);

        try {
            $this->get("public/medicals/{$medicalId}/services");
            $this->fail('Se esperaba MedicalNotAvailableException');
        } catch (MedicalNotAvailableException $e) {
            $this->assertSame(409, $e->getCode());
        }
    }

    public function testMedicalServicesOfAnUnknownMedicalAre404(): void
    {
        try {
            $this->get('public/medicals/999999/services');
            $this->fail('Se esperaba una excepción 404');
        } catch (Exception $e) {
            $this->assertSame(404, $e->getCode());
        }
    }

    public function testAvailabilityIsPublicAndReturnsOnlySlots(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();

        $result = $this->get('public/appointments/availability', [
            'medicalId' => (string) $medicalId,
            'serviceId' => (string) $serviceId,
            'fecha'     => $this->monday,
        ]);

        $result->assertStatus(200);
        $slots = $this->decode($result);
        $this->assertNotEmpty($slots);
        $this->assertSame(['start_time', 'end_time', 'block_end_time'], array_keys($slots[0]));
    }

    public function testPublicAvailabilityIgnoresExcludeAppointmentId(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();
        $patientId = $this->seedPatient();
        // seedAppointment usa self::FECHA; se pisa la fecha al lunes del escenario
        $id = $this->seedAppointment($medicalId, $patientId, $serviceId, '09:30:00', '10:00:00');
        $this->db->table('appointments')->where('id', $id)->update(['fecha' => $this->monday]);

        $params = ['medicalId' => (string) $medicalId, 'serviceId' => (string) $serviceId, 'fecha' => $this->monday];
        $sin = array_column($this->decode($this->get('public/appointments/availability', $params)), 'start_time');
        $this->assertNotContains('09:30', $sin);

        // con el parámetro (válido o inválido) la ruta pública lo ignora: mismo resultado y sin 422
        $result = $this->get('public/appointments/availability', $params + ['excludeAppointmentId' => (string) $id]);
        $result->assertStatus(200);
        $this->assertSame($sin, array_column($this->decode($result), 'start_time'));

        $result = $this->get('public/appointments/availability', $params + ['excludeAppointmentId' => 'abc']);
        $result->assertStatus(200);
        $this->assertSame($sin, array_column($this->decode($result), 'start_time'));
    }

    // ------------------------------------------------------------ reserva (POST)

    public function testBookingReturns201WithAMinimalBodyAndPersistsEverything(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();

        $result = $this->book($this->bookingPayload($medicalId, $serviceId));

        $result->assertStatus(201);
        $json = $this->decode($result);
        $this->assertSame(['id', 'fecha', 'hora', 'medicoNombre', 'servicioNombre', 'status'], array_keys($json));
        $this->assertSame('confirmed', $json['status']);
        $this->assertSame($this->monday, $json['fecha']);
        $this->assertSame('09:30', $json['hora']);
        $this->seeInDatabase('appointments', ['id' => $json['id'], 'status' => 'confirmed', 'hora' => '09:30:00']);
        $this->seeInDatabase('patients', ['dni' => 30123456]);
        $this->assertStringNotContainsString('30123456', $result->getJSON());
        $this->assertStringNotContainsString('juana@test.com', $result->getJSON());
    }

    public function testStatusAndPatientIdInTheBodyAreIgnored(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();
        $otherPatient = $this->seedExistingPatient(30999999, 'otro@test.com', '1144445555');

        $result = $this->book($this->bookingPayload($medicalId, $serviceId, [
            'status'    => 'completed',
            'patientId' => $otherPatient,
        ]));

        $result->assertStatus(201);
        $id = $this->decode($result)['id'];
        $this->seeInDatabase('appointments', ['id' => $id, 'status' => 'confirmed']);
        $this->dontSeeInDatabase('appointments', ['id' => $id, 'patient_id' => $otherPatient]);
    }

    public function testIdentityIsNormalizedBeforeBeingStored(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();

        $result = $this->book($this->bookingPayload($medicalId, $serviceId, [
            'dni'   => '30.123.456',
            'phone' => '(2325) 50-6499',
            'email' => '  JUANA@Test.COM ',
        ]));

        $result->assertStatus(201);
        $this->seeInDatabase('patients', [
            'dni'   => 30123456,
            'phone' => '2325506499',
            'email' => 'juana@test.com',
        ]);
    }

    public function testInvalidBodyIsRejectedWith422AndCreatesNothing(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();
        $patientsBefore = (int) $this->db->table('patients')->countAllResults();

        try {
            $this->book($this->bookingPayload($medicalId, $serviceId, ['dni' => '123', 'hora' => '9:30']));
            $this->fail('Se esperaba ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(422, $e->getCode());
            $this->assertArrayHasKey('dni', $e->getErrors());
            $this->assertArrayHasKey('hora', $e->getErrors());
        }

        $this->assertSame($patientsBefore, (int) $this->db->table('patients')->countAllResults());
        $this->assertSame(0, $this->countAppointments());
    }

    public function testExistingDniIsReusedWithoutTouchingTheRecordWhateverTheTypedPhoneIs(): void
    {
        [$medicalId, $serviceId] = $this->bookableScenario();
        $patientId = $this->seedExistingPatient(30123456, 'existente@test.com', '1144445555');

        $result = $this->book($this->bookingPayload($medicalId, $serviceId));

        $result->assertStatus(201);
        $this->seeInDatabase('appointments', ['id' => $this->decode($result)['id'], 'patient_id' => $patientId]);
        $this->seeInDatabase('patients', [
            'id'         => $patientId,
            'first_name' => 'Original',
            'email'      => 'existente@test.com',
            'phone'      => '1144445555',
        ]);
        $this->seeNumRecords(1, 'patients', []);
    }

    // ------------------------------------------------------------------- CORS

    public function testAllowedOriginGetsCorsHeadersOnPublicRoutes(): void
    {
        $this->configureCors('https://clinica.example');

        $result = $this->withHeaders(['Origin' => 'https://clinica.example'])->get('public/specialties');

        $result->assertStatus(200);
        $this->assertSame('https://clinica.example', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
    }

    public function testSeveralOriginsAreMatchedAgainstTheRequestOrigin(): void
    {
        $this->configureCors('https://a.example,https://b.example');

        $result = $this->withHeaders(['Origin' => 'https://b.example'])->get('public/specialties');

        $this->assertSame('https://b.example', $result->response()->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Origin', $result->response()->getHeaderLine('Vary'));
    }

    public function testSubdomainPatternsAreAllowed(): void
    {
        $this->configureCors('', 'https://[a-z0-9-]+\.miclinica\.com');

        $allowed = $this->withHeaders(['Origin' => 'https://norte.miclinica.com'])->get('public/specialties');
        $this->assertSame('https://norte.miclinica.com', $allowed->response()->getHeaderLine('Access-Control-Allow-Origin'));

        $this->resetServices();
        $denied = $this->withHeaders(['Origin' => 'https://norte.miclinica.com.evil.com'])->get('public/specialties');
        $this->assertFalse($denied->response()->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testDisallowedOriginGetsNoCorsHeaders(): void
    {
        $this->configureCors('https://a.example,https://b.example');

        $result = $this->withHeaders(['Origin' => 'https://evil.example'])->get('public/specialties');

        $result->assertStatus(200);
        $this->assertFalse($result->response()->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testWithoutConfiguredOriginsNoCorsHeadersAreSent(): void
    {
        $result = $this->withHeaders(['Origin' => 'https://clinica.example'])->get('public/specialties');

        $result->assertStatus(200);
        $this->assertFalse($result->response()->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testPreflightIsAnsweredByTheFilterWithoutRunningTheController(): void
    {
        $this->configureCors('https://clinica.example');
        $this->bookableScenario();

        $result = $this->withHeaders([
            'Origin'                         => 'https://clinica.example',
            'Access-Control-Request-Method'  => 'POST',
            'Access-Control-Request-Headers' => 'content-type',
        ])->call('options', 'public/appointments');

        $response = $result->response();
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('https://clinica.example', $response->getHeaderLine('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('POST', $response->getHeaderLine('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Content-Type', $response->getHeaderLine('Access-Control-Allow-Headers'));
        $this->assertSame(0, $this->countAppointments());
    }

    public function testNonPublicRoutesDoNotGetCorsHeaders(): void
    {
        $this->configureCors('https://clinica.example');

        // Ruta autenticada: sin token el filter devuelve 401 y no pasa por el filtro 'cors'.
        $result = $this->withHeaders(['Origin' => 'https://clinica.example'])->get('medicals/available');

        $result->assertStatus(401);
        $this->assertFalse($result->response()->hasHeader('Access-Control-Allow-Origin'));
    }

    public function testAuthenticatedRoutesStillRequireAuthentication(): void
    {
        $this->get('appointments/availability')->assertStatus(401);
        $this->get('specialties')->assertStatus(401);
        $this->get('services')->assertStatus(401);
    }
}

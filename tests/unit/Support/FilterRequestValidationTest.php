<?php

use App\Dto\Request\Appointment\AppointmentFilterRequest;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Request\MedicalSchedule\MedicalScheduleFilterRequest;
use App\Dto\Request\PaginationRequest;
use App\Dto\Request\Service\ServiceFilterRequest;
use App\Dto\Request\Specialty\SpecialtyFilterRequest;
use App\Dto\Request\User\UserFilterRequest;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Exception\Validation\ValidationException;
use App\Validation\RequestValidator;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Valida el query string de los listados GET (paginación + filtros).
 *
 * @internal
 */
final class FilterRequestValidationTest extends CIUnitTestCase
{
    /**
     * @return array<string, array{0: class-string}>
     */
    public static function filterRequests(): array
    {
        return [
            'appointments'     => [AppointmentFilterRequest::class],
            'medicals'         => [MedicalFilterRequest::class],
            'users'            => [UserFilterRequest::class],
            'patients'         => [PatientFilterRequest::class],
            'services'         => [ServiceFilterRequest::class],
            'specialties'      => [SpecialtyFilterRequest::class],
            'medicalSchedules' => [MedicalScheduleFilterRequest::class],
        ];
    }

    private function validate(string $filterRequest, array $query): void
    {
        RequestValidator::validate($query, $filterRequest::rules(), $filterRequest::messages());
    }

    /**
     * @dataProvider filterRequests
     */
    public function testEmptyQueryPasses(string $filterRequest): void
    {
        $this->validate($filterRequest, []);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider filterRequests
     */
    public function testDefaultPaginationPasses(string $filterRequest): void
    {
        $this->validate($filterRequest, ['page' => '1', 'size' => '10']);
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider filterRequests
     */
    public function testInvalidPageAndSizeThrow422(string $filterRequest): void
    {
        foreach ([
            ['page' => '-5'],
            ['page' => '0'],
            ['page' => 'abc'],
            ['size' => '-1'],
            ['size' => '0'],
            ['size' => 'abc'],
            ['size' => '100000'],
        ] as $query) {
            try {
                $this->validate($filterRequest, $query);
                $this->fail('Se esperaba ValidationException para ' . json_encode($query));
            } catch (ValidationException $e) {
                $this->assertSame(422, $e->getCode());
                $this->assertArrayHasKey(array_key_first($query), $e->getErrors());
            }
        }
    }

    public function testSizeCapIsOneHundredByDefault(): void
    {
        $this->validate(PaginationRequest::class, ['size' => '100']);

        $this->expectException(ValidationException::class);
        $this->validate(PaginationRequest::class, ['size' => '101']);
    }

    public function testMedicalSchedulesAllowSizeFiveHundredForTheDashboard(): void
    {
        $this->validate(MedicalScheduleFilterRequest::class, ['size' => '500']);
        $this->addToAssertionCount(1);

        $this->expectException(ValidationException::class);
        $this->validate(MedicalScheduleFilterRequest::class, ['size' => '501']);
    }

    public function testAppointmentFiltersAreValidated(): void
    {
        $this->validate(AppointmentFilterRequest::class, [
            'medicalId' => '3', 'patientId' => '4', 'status' => 'pending', 'fecha' => '2026-10-03', 'search' => 'ana',
        ]);
        $this->addToAssertionCount(1);

        foreach ([['status' => 'foo'], ['fecha' => '2026-13-45'], ['medicalId' => 'abc'], ['search' => str_repeat('a', 101)]] as $query) {
            try {
                $this->validate(AppointmentFilterRequest::class, $query);
                $this->fail('Se esperaba ValidationException para ' . json_encode($query));
            } catch (ValidationException $e) {
                $this->assertArrayHasKey(array_key_first($query), $e->getErrors());
            }
        }
    }

    public function testIsActiveRejectsGarbage(): void
    {
        foreach (['true', 'false', '0', '1'] as $value) {
            $this->validate(ServiceFilterRequest::class, ['isActive' => $value]);
        }
        $this->addToAssertionCount(1);

        $this->expectException(ValidationException::class);
        $this->validate(ServiceFilterRequest::class, ['isActive' => 'abc']);
    }
}

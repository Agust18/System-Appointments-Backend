<?php

use App\Entity\MedicalSchedule\MedicalSchedule;
use App\Services\Appointment\SlotGeneratorService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class SlotGeneratorServiceTest extends CIUnitTestCase
{
    private SlotGeneratorService $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new SlotGeneratorService();
    }

    private function makeRange(string $start, string $end): MedicalSchedule
    {
        return new MedicalSchedule(1, 1, 1, $start, $end, true, null, null);
    }

    private function makeAppointment(string $hora, string $horaFin): stdClass
    {
        $appt = new stdClass();
        $appt->hora = $hora;
        $appt->hora_fin = $horaFin;

        return $appt;
    }

    public function testAllSlotsFreeWhenNoExistingAppointments(): void
    {
        $slots = $this->generator->generate([$this->makeRange('09:00:00', '10:00:00')], [], 30, 30, 30);

        $this->assertSame(['09:00', '09:30'], array_column($slots, 'start_time'));
    }

    public function testAppointmentBlocksExactSlot(): void
    {
        $slots = $this->generator->generate(
            [$this->makeRange('09:00:00', '10:00:00')],
            [$this->makeAppointment('09:30:00', '10:00:00')],
            30,
            30,
            30
        );

        $this->assertSame(['09:00'], array_column($slots, 'start_time'));
    }

    public function testAppointmentBlocksViaBuffer(): void
    {
        // duración 20, buffer 10 -> bloque 30. El turno 09:20-09:30 no solaparía
        // la duración visible del slot de 09:00 (termina a las 09:20), pero sí
        // solapa el bloque total con buffer (09:00-09:30) -> el buffer bloquea
        // ese slot igual. El de 09:30 queda libre.
        $slots = $this->generator->generate(
            [$this->makeRange('09:00:00', '10:00:00')],
            [$this->makeAppointment('09:20:00', '09:30:00')],
            20,
            30,
            30
        );

        $this->assertSame(['09:30'], array_column($slots, 'start_time'));
    }

    public function testMultipleRangesSameDay(): void
    {
        $slots = $this->generator->generate(
            [$this->makeRange('09:00:00', '09:30:00'), $this->makeRange('14:00:00', '14:30:00')],
            [],
            30,
            30,
            30
        );

        $this->assertSame(['09:00', '14:00'], array_column($slots, 'start_time'));
    }

    public function testCustomSlotStep(): void
    {
        $slots = $this->generator->generate([$this->makeRange('09:00:00', '09:30:00')], [], 10, 10, 5);

        $this->assertSame(['09:00', '09:05', '09:10', '09:15', '09:20'], array_column($slots, 'start_time'));
    }
}

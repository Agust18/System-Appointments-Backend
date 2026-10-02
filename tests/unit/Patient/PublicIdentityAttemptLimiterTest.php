<?php

use App\Services\Patient\PublicIdentityAttemptLimiter;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;

/**
 * @internal
 */
final class PublicIdentityAttemptLimiterTest extends CIUnitTestCase
{
    private const DNI = 30123456;

    private PublicIdentityAttemptLimiter $limiter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resetServices();
        Services::injectMock('cache', new MockCache());

        $this->limiter = new PublicIdentityAttemptLimiter();
    }

    protected function tearDown(): void
    {
        $this->resetServices();
        parent::tearDown();
    }

    private function recordFailures(int $dni, int $times): void
    {
        for ($i = 0; $i < $times; $i++) {
            $this->limiter->recordFailure($dni);
        }
    }

    public function testADniWithoutFailuresIsNotBlocked(): void
    {
        $this->assertFalse($this->limiter->isBlocked(self::DNI));
    }

    public function testFiveFailuresAreTheLimit(): void
    {
        $this->assertSame(5, PublicIdentityAttemptLimiter::MAX_FAILURES);

        $this->recordFailures(self::DNI, 4);
        $this->assertFalse($this->limiter->isBlocked(self::DNI));

        $this->recordFailures(self::DNI, 1);
        $this->assertTrue($this->limiter->isBlocked(self::DNI));
    }

    public function testTheBlockLastsOneHour(): void
    {
        $this->assertSame(3600, PublicIdentityAttemptLimiter::BLOCK_SECONDS);
    }

    public function testClearingResetsTheCounter(): void
    {
        $this->recordFailures(self::DNI, 5);
        $this->limiter->clear(self::DNI);

        $this->assertFalse($this->limiter->isBlocked(self::DNI));

        $this->recordFailures(self::DNI, 4);
        $this->assertFalse($this->limiter->isBlocked(self::DNI));
    }

    public function testEachDniHasItsOwnCounter(): void
    {
        $this->recordFailures(self::DNI, 5);

        $this->assertTrue($this->limiter->isBlocked(self::DNI));
        $this->assertFalse($this->limiter->isBlocked(30999999));
    }

    public function testClearingOneDniDoesNotAffectAnother(): void
    {
        $this->recordFailures(self::DNI, 5);
        $this->recordFailures(30999999, 5);

        $this->limiter->clear(self::DNI);

        $this->assertFalse($this->limiter->isBlocked(self::DNI));
        $this->assertTrue($this->limiter->isBlocked(30999999));
    }

    public function testItNeverBlocksWhenTheCacheDoesNotWork(): void
    {
        $this->resetServices();
        Services::injectMock('cache', new DummyHandler());

        $this->recordFailures(self::DNI, 10);

        $this->assertFalse($this->limiter->isBlocked(self::DNI));
    }
}

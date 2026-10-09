<?php

use App\Libraries\ApiExceptionHandler;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Las respuestas de error cortan la ejecución antes de que corra el filtro 'cors', así que
 * ApiExceptionHandler repite la decisión del origen. Debe coincidir con la del filtro.
 *
 * @internal
 */
final class ApiExceptionHandlerCorsTest extends CIUnitTestCase
{
    private function allowed(array $origins, array $patterns, ?string $origin): ?string
    {
        return ApiExceptionHandler::allowedOriginFor(
            ['allowedOrigins' => $origins, 'allowedOriginsPatterns' => $patterns],
            $origin
        );
    }

    public function testNoConfiguredOriginsAllowsNothing(): void
    {
        $this->assertNull($this->allowed([], [], 'https://clinica.com'));
        $this->assertNull($this->allowed([], [], null));
    }

    public function testSingleOriginIsAlwaysReturnedEvenWithoutAnOriginHeader(): void
    {
        $this->assertSame('https://clinica.com', $this->allowed(['https://clinica.com'], [], null));
        $this->assertSame('https://clinica.com', $this->allowed(['https://clinica.com'], [], 'https://otro.com'));
    }

    public function testSeveralOriginsOnlyAllowTheOnesThatMatch(): void
    {
        $origins = ['https://a.com', 'https://b.com'];

        $this->assertSame('https://b.com', $this->allowed($origins, [], 'https://b.com'));
        $this->assertNull($this->allowed($origins, [], 'https://c.com'));
        $this->assertNull($this->allowed($origins, [], null));
    }

    public function testPatternsAllowMatchingSubdomains(): void
    {
        $patterns = ['https://[a-z0-9-]+\.miclinica\.com'];

        $this->assertSame('https://norte.miclinica.com', $this->allowed([], $patterns, 'https://norte.miclinica.com'));
        $this->assertNull($this->allowed([], $patterns, 'https://miclinica.com'));
        $this->assertNull($this->allowed([], $patterns, 'https://norte.miclinica.com.evil.com'));
        $this->assertNull($this->allowed([], $patterns, 'http://norte.miclinica.com'));
    }

    public function testASingleOriginWithPatternsIsOnlyReturnedWhenTheRequestMatches(): void
    {
        $origins = ['https://a.com'];
        $patterns = ['https://[a-z]+\.miclinica\.com'];

        $this->assertSame('https://a.com', $this->allowed($origins, $patterns, 'https://a.com'));
        $this->assertSame('https://sur.miclinica.com', $this->allowed($origins, $patterns, 'https://sur.miclinica.com'));
        $this->assertNull($this->allowed($origins, $patterns, 'https://evil.com'));
        $this->assertNull($this->allowed($origins, $patterns, null));
    }

    public function testPatternsAreAnchoredSoTheyCannotMatchAPartOfTheOrigin(): void
    {
        $this->assertNull($this->allowed([], ['https://miclinica\.com'], 'https://evil.com/https://miclinica.com'));
    }
}

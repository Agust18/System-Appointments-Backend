<?php

use App\Support\IdentityNormalizer;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class IdentityNormalizerTest extends CIUnitTestCase
{
    /**
     * @dataProvider dniProvider
     */
    public function testDniNormalization(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, IdentityNormalizer::dni($input));
    }

    public static function dniProvider(): array
    {
        return [
            'con puntos'            => ['23.525.064', '23525064'],
            'con guiones'           => ['23-525-064', '23525064'],
            'sin formato'           => ['23525064', '23525064'],
            'con espacios'          => [' 23 525 064 ', '23525064'],
            'siete dígitos'         => ['7.123.456', '7123456'],
            'ceros a la izquierda'  => ['0023525064', '23525064'],
            'solo ceros'            => ['0000', '0'],
            'vacío'                 => ['', null],
            'solo separadores'      => ['..--', null],
            'null'                  => [null, null],
            'letras no se limpian'  => ['12abc345', '12abc345'],
        ];
    }

    /**
     * @dataProvider phoneProvider
     */
    public function testPhoneNormalization(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, IdentityNormalizer::phone($input));
    }

    public static function phoneProvider(): array
    {
        return [
            'con guion'                   => ['2325-506499', '2325506499'],
            'con espacio'                 => ['2325 506499', '2325506499'],
            'sin formato'                 => ['2325506499', '2325506499'],
            'paréntesis y guiones'        => ['(2325) 50-6499', '2325506499'],
            'cero de área'                => ['0 2325 506499', '2325506499'],
            '+54 móvil'                   => ['+54 9 2325 506499', '2325506499'],
            '+54 sin el 9'                => ['+54 2325 506499', '2325506499'],
            '0054 móvil'                  => ['00 54 9 11 4444 5555', '1144445555'],
            'prefijo 54 sin +'            => ['54 9 11 4444-5555', '1144445555'],
            'AMBA con cero'               => ['011 4444-5555', '1144445555'],
            'no recorta si queda corto'   => ['0123456789', '0123456789'],
            'vacío'                       => ['', null],
            'solo separadores'            => ['() -', null],
            'null'                        => [null, null],
            'letras no se limpian'        => ['2325-abc499', '2325abc499'],
        ];
    }

    public function testEmailNormalization(): void
    {
        $this->assertSame('juan.perez@mail.com', IdentityNormalizer::email('  Juan.Perez@Mail.COM '));
        $this->assertNull(IdentityNormalizer::email('   '));
        $this->assertNull(IdentityNormalizer::email(null));
    }

    public function testNormalizeInputNormalizesPresentFieldsOnly(): void
    {
        $result = IdentityNormalizer::normalizeInput([
            'firstName' => ' Juan ',
            'dni'       => '23.525.064',
            'phone'     => '2325-506499',
            'email'     => 'Juan@Mail.com',
        ]);

        $this->assertSame(' Juan ', $result['firstName']);
        $this->assertSame('23525064', $result['dni']);
        $this->assertSame('2325506499', $result['phone']);
        $this->assertSame('juan@mail.com', $result['email']);
    }

    public function testNormalizeInputDoesNotAddMissingFields(): void
    {
        $result = IdentityNormalizer::normalizeInput(['firstName' => 'Juan']);

        $this->assertSame(['firstName' => 'Juan'], $result);
    }

    public function testNormalizeInputAcceptsIntegerDni(): void
    {
        $result = IdentityNormalizer::normalizeInput(['dni' => 23525064]);

        $this->assertSame('23525064', $result['dni']);
    }

    public function testNormalizeInputLeavesNonScalarValuesUntouched(): void
    {
        $result = IdentityNormalizer::normalizeInput(['dni' => ['x'], 'phone' => null]);

        $this->assertSame(['x'], $result['dni']);
        $this->assertNull($result['phone']);
    }

    public function testNormalizeInputTurnsEmptyPhoneIntoNull(): void
    {
        $result = IdentityNormalizer::normalizeInput(['phone' => '  ']);

        $this->assertNull($result['phone']);
    }
}

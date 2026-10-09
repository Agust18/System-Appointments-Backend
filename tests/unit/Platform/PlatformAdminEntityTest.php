<?php

use App\Converter\Platform\PrimitiveToPlatformAdminConverter;
use App\Entity\Platform\PlatformAdmin;
use App\Entity\Platform\PlatformAdminToken;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class PlatformAdminEntityTest extends CIUnitTestCase
{
    public function testCreateHashesThePasswordAndStartsActive(): void
    {
        $admin = PlatformAdmin::create('root@plataforma.test', 'Root', 'Admin', 'Secreta-123');

        $this->assertNull($admin->getId());
        $this->assertTrue($admin->isActive());
        $this->assertNotSame('Secreta-123', $admin->getPassword());
        $this->assertTrue($admin->verifyPassword('Secreta-123'));
        $this->assertFalse($admin->verifyPassword('otra'));
    }

    public function testPrimitiveConverterMapsTheRow(): void
    {
        $row = (object) [
            'id' => '7',
            'email' => 'root@plataforma.test',
            'first_name' => 'Root',
            'last_name' => 'Admin',
            'password' => password_hash('x', PASSWORD_BCRYPT),
            'is_active' => '0',
            'created_at' => '2026-10-07 10:00:00',
            'updated_at' => '2026-10-07 11:00:00',
        ];

        $admin = (new PrimitiveToPlatformAdminConverter())->convert($row);

        $this->assertSame(7, $admin->getId());
        $this->assertSame('root@plataforma.test', $admin->getEmail());
        $this->assertFalse($admin->isActive());
        $this->assertSame('2026-10-07 11:00:00', $admin->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function testIssuedTokensAreRandomAndExpireInTheFuture(): void
    {
        $a = PlatformAdminToken::issueFor(1);
        $b = PlatformAdminToken::issueFor(1);

        $this->assertSame(64, strlen($a->getToken()));
        $this->assertNotSame($a->getToken(), $b->getToken());
        $this->assertSame(1, $a->getPlatformAdminId());
        $this->assertGreaterThan(new DateTime('+23 hours'), $a->getExpiresAt());
    }
}

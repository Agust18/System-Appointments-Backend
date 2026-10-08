<?php

use App\Models\Platform\PlatformAdminModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\StreamFilterTrait;
use Tests\Support\Database\FastDatabaseTestTrait;

/**
 * El comando es un cascarón sobre PlatformAdminCreatorService: acá se verifica lo que imprime,
 * que valide y que la contraseña no se filtre más de lo esperado.
 *
 * @internal
 */
final class CreatePlatformAdminCommandTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;
    use StreamFilterTrait;

    protected $refresh = true;
    protected $namespace = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpStreamFilterTrait();
    }

    protected function tearDown(): void
    {
        $this->tearDownStreamFilterTrait();
        parent::tearDown();
    }

    private function adminCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) AS c FROM platform_admins')->getRow()->c;
    }

    public function testCreatesAnAdminWithAGeneratedPasswordShownOnce(): void
    {
        command('platform:create-admin --email Root@Plataforma.test --first-name Root --last-name Admin --generate-password');

        $output = $this->getStreamFilterBuffer();
        $this->assertStringContainsString('Administrador de plataforma creado: root@plataforma.test', $output);
        $this->assertSame(1, $this->adminCount());

        preg_match('/no se vuelve a mostrar\): (\w+)/', $output, $matches);
        $this->assertSame(32, strlen($matches[1] ?? ''));

        $admin = (new PlatformAdminModel())->findByEmail('root@plataforma.test');
        $this->assertTrue($admin->verifyPassword($matches[1]));
        $this->assertStringNotContainsString($admin->getPassword(), $output);
    }

    public function testRejectsAnExistingEmail(): void
    {
        command('platform:create-admin --email root@plataforma.test --first-name Root --last-name Admin --generate-password');
        $this->resetStreamFilterBuffer();

        command('platform:create-admin --email root@plataforma.test --first-name Otro --last-name Admin --generate-password');

        $this->assertStringContainsString('Ya existe un administrador de plataforma con ese email', $this->getStreamFilterBuffer());
        $this->assertSame(1, $this->adminCount());
    }

    public function testRejectsAnInvalidEmailWithoutWritingAnything(): void
    {
        command('platform:create-admin --email no-es-un-email --first-name Root --last-name Admin --generate-password');

        $this->assertStringContainsString('Ese email no es válido.', $this->getStreamFilterBuffer());
        $this->assertSame(0, $this->adminCount());
    }

    public function testRejectsShortNames(): void
    {
        command('platform:create-admin --email root@plataforma.test --first-name R --last-name A --generate-password');

        $output = $this->getStreamFilterBuffer();
        $this->assertStringContainsString('El nombre es muy corto.', $output);
        $this->assertStringContainsString('El apellido es muy corto.', $output);
        $this->assertSame(0, $this->adminCount());
    }

    public function testGeneratedPasswordIsPrintedExactlyOnce(): void
    {
        command('platform:create-admin --email root@plataforma.test --first-name Root --last-name Admin --generate-password');

        $this->assertSame(1, substr_count($this->getStreamFilterBuffer(), 'Contraseña generada'));
    }
}

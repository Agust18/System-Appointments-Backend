<?php

use App\Entity\Tenant\Tenant;
use App\Filters\Auth;
use App\Tenancy\TenantContext;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\FastDatabaseTestTrait;
use Config\Services;

/**
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use FastDatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private Auth $filter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filter = new Auth();
        session()->destroy();
        unset($_SERVER['HTTP_AUTHORIZATION']);
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        parent::tearDown();
    }

    private function seedUserWithToken(string $token, ?string $tokenExpiration): int
    {
        $this->db->table('users')->insert(['tenant_id' => 1,
            'first_name'             => 'user_' . uniqid(),
            'last_name'              => 'Apellido',
            'email'                  => uniqid() . '@test.com',
            'password'               => 'hash',
            'dni'                    => random_int(1000000, 9999999),
            'token'                  => $token,
            'token_expiration_date'  => $tokenExpiration,
        ]);

        return $this->db->insertID();
    }

    public function testMissingTokenReturns401WithoutThrowing(): void
    {
        $request = Services::request(null, false);

        $result = $this->filter->before($request);

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(401, $result->getStatusCode());
        $this->assertNull(session()->get('userId'));
    }

    public function testInvalidTokenReturns401(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'token-inexistente';
        $request = Services::request(null, false);

        $result = $this->filter->before($request);

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(401, $result->getStatusCode());
    }

    public function testExpiredTokenReturns401(): void
    {
        $token = 'expired-' . uniqid();
        $this->seedUserWithToken($token, date('Y-m-d H:i:s', strtotime('-1 day')));

        $_SERVER['HTTP_AUTHORIZATION'] = $token;
        $request = Services::request(null, false);

        $result = $this->filter->before($request);

        $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame(401, $result->getStatusCode());
    }

    public function testValidTokenSetsSessionAndLetsRequestThrough(): void
    {
        $token = 'valid-' . uniqid();
        $userId = $this->seedUserWithToken($token, date('Y-m-d H:i:s', strtotime('+1 day')));

        $_SERVER['HTTP_AUTHORIZATION'] = $token;
        $request = Services::request(null, false);

        $result = $this->filter->before($request);

        $this->assertNotInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
        $this->assertSame($userId, session()->get('userId'));
    }

    public function testValidTokenOfAnotherTenantReturns401(): void
    {
        $token = 'valid-' . uniqid();
        $this->seedUserWithToken($token, date('Y-m-d H:i:s', strtotime('+1 day')));

        $this->db->table('tenants')->insert(['slug' => 'tenant-b', 'name' => 'Tenant B']);
        $tenantB = $this->db->insertID();

        try {
            TenantContext::set(new Tenant($tenantB, 'tenant-b', 'Tenant B', 'active', null, null));

            $_SERVER['HTTP_AUTHORIZATION'] = $token;
            $result = $this->filter->before(Services::request(null, false));

            $this->assertInstanceOf(\CodeIgniter\HTTP\ResponseInterface::class, $result);
            $this->assertSame(401, $result->getStatusCode());
            $this->assertNull(session()->get('userId'));
        } finally {
            TenantContext::set(new Tenant(1, 'demo', 'Demo', 'active', null, null));
            $this->db->query('DELETE FROM users');
            $this->db->query('DELETE FROM tenants WHERE id <> 1');
        }
    }
}

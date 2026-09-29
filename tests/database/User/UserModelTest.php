<?php

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;
use App\Entity\User\User;
use App\Exception\User\UserCannotBeCreatedException;
use App\Models\User\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class UserModelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $refresh = true;
    protected $namespace = null;

    private UserModel $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new UserModel();
        $this->db->table('roles')->insert(['id' => Role::PATIENT, 'name' => 'patient']);
    }

    private function user(string $username, string $email, int $dni): User
    {
        $request = new UserRequest($username, $email, 'password123', $dni, true, Role::PATIENT);

        return User::convertFromRequest($request);
    }

    public function testInsertReturnsHydratedEntityWithRealTimestamps(): void
    {
        $newUser = $this->model->insert($this->user('usuario_hidratado', 'hidratado@test.com', 77777777));

        $this->assertNotNull($newUser->getId());
        $this->assertNotNull($newUser->getCreatedAt());
    }

    /**
     * Reproduce el bug real: un INSERT que falla (username duplicado) dentro
     * de una transacción administrada no lanza DatabaseException (CI4 no
     * tira excepción por defecto dentro de una transacción), por lo que
     * insertID() devuelve 0. UserModel::insert() debe detectar ese caso y
     * lanzar UserCannotBeCreatedException en vez de devolver un User con id=0.
     */
    public function testInsertFailureInsideTransactionThrowsUserCannotBeCreatedException(): void
    {
        $this->model->insert($this->user('usuario_duplicado', 'primero@test.com', 88888888));

        $this->db->transStart();

        try {
            $this->model->insert($this->user('usuario_duplicado', 'segundo@test.com', 99999999));
            $this->fail('Se esperaba UserCannotBeCreatedException');
        } catch (UserCannotBeCreatedException $e) {
            $this->assertSame(500, $e->getCode());
        } finally {
            // transStrict (true por defecto) hace que transStatus quede en
            // false tras el fallo aunque hagamos rollback; sin resetearlo acá
            // "contamina" la conexión compartida y rompe el próximo test que
            // use transStart()/transComplete() (ver PatientCreatorServiceTest).
            $this->db->transRollback();
            $this->db->resetTransStatus();
        }

        $this->seeNumRecords(1, 'users', []);
    }
}

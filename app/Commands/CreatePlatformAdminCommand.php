<?php

namespace App\Commands;

use App\Dto\Request\Platform\PlatformAdminRequest;
use App\Exception\Platform\PlatformAdminEmailAlreadyExistsException;
use App\Exception\Validation\ValidationException;
use App\Services\Platform\PlatformAdminCreatorService;
use App\Support\IdentityNormalizer;
use App\Validation\RequestValidator;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;


class CreatePlatformAdminCommand extends BaseCommand
{
    protected $group       = 'Platform';
    protected $name        = 'platform:create-admin';
    protected $description = 'Crea un administrador de plataforma (SuperAdmin). Pide por prompt lo que no se pase por opción.';
    protected $usage       = 'platform:create-admin [--email <email>] [--first-name <nombre>] [--last-name <apellido>] [--generate-password]';
    protected $options     = [
        '--email'             => 'Email del administrador.',
        '--first-name'        => 'Nombre.',
        '--last-name'         => 'Apellido.',
        '--generate-password' => 'Genera una contraseña aleatoria y la muestra una sola vez (en vez de pedirla).',
    ];

    public function run(array $params): int
    {
        $email     = IdentityNormalizer::email($this->option($params, 'email') ?? CLI::prompt('Email'));
        $firstName = trim((string) ($this->option($params, 'first-name') ?? CLI::prompt('Nombre')));
        $lastName  = trim((string) ($this->option($params, 'last-name') ?? CLI::prompt('Apellido')));

        $generated = array_key_exists('generate-password', $params) || CLI::getOption('generate-password') !== null;
        $password  = $generated ? bin2hex(random_bytes(16)) : (string) CLI::prompt('Contraseña (mínimo 12 caracteres)');

        $parameters = [
            'email'     => $email,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'password'  => $password,
        ];

        try {
            RequestValidator::validate($parameters, PlatformAdminRequest::rules(), PlatformAdminRequest::messages());

            $admin = (new PlatformAdminCreatorService())->create(
                new PlatformAdminRequest($email, $firstName, $lastName, $password)
            );
        } catch (ValidationException $e) {
            foreach ($e->getErrors() as $field => $message) {
                CLI::error("{$field}: {$message}");
            }

            return EXIT_ERROR;
        } catch (PlatformAdminEmailAlreadyExistsException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write("Administrador de plataforma creado: {$admin->email} (id {$admin->id}).", 'green');

        if ($generated) {
            CLI::write("Contraseña generada (guardala ahora, no se vuelve a mostrar): {$password}", 'yellow');
        }

        return EXIT_SUCCESS;
    }

    
    private function option(array $params, string $name): ?string
    {
        $value = $params[$name] ?? CLI::getOption($name);

        return is_string($value) ? $value : null;
    }
}

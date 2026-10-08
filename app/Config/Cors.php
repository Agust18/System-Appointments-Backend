<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) Configuration
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * The default CORS configuration.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        /**
         * Origins for the `Access-Control-Allow-Origin` header.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         *
         * E.g.:
         *   - ['http://localhost:8080']
         *   - ['https://www.example.com']
         */
        'allowedOrigins' => [],

        /**
         * Origin regex patterns for the `Access-Control-Allow-Origin` header.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Origin
         *
         * NOTE: A pattern specified here is part of a regular expression. It will
         *       be actually `#\A<pattern>\z#`.
         *
         * E.g.:
         *   - ['https://\w+\.example\.com']
         */
        'allowedOriginsPatterns' => [],

        /**
         * Weather to send the `Access-Control-Allow-Credentials` header.
         *
         * The Access-Control-Allow-Credentials response header tells browsers whether
         * the server allows cross-origin HTTP requests to include credentials.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Credentials
         */
        'supportsCredentials' => false,

        /**
         * Set headers to allow.
         *
         * The Access-Control-Allow-Headers response header is used in response to
         * a preflight request which includes the Access-Control-Request-Headers to
         * indicate which HTTP headers can be used during the actual request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Headers
         */
        'allowedHeaders' => ['Content-Type', 'Accept', 'X-Tenant'],

        /**
         * Set headers to expose.
         *
         * The Access-Control-Expose-Headers response header allows a server to
         * indicate which response headers should be made available to scripts running
         * in the browser, in response to a cross-origin request.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Expose-Headers
         */
        'exposedHeaders' => [],

        /**
         * Set methods to allow.
         *
         * The Access-Control-Allow-Methods response header specifies one or more
         * methods allowed when accessing a resource in response to a preflight
         * request.
         *
         * E.g.:
         *   - ['GET', 'POST', 'PUT', 'DELETE']
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Allow-Methods
         */
        'allowedMethods' => ['GET', 'POST', 'OPTIONS'],

        /**
         * Set how many seconds the results of a preflight request can be cached.
         *
         * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Access-Control-Max-Age
         */
        'maxAge' => 7200,
    ];

    /**
     * Perfil del panel de plataforma (/platform/*): se usa con el filtro `cors:platform`.
     * Mismos orígenes que /public/* (se copian en el constructor). A diferencia de `default`,
     * admite PUT (suspender/reactivar tenants) y el header Authorization; no lleva X-Tenant
     * porque /platform/* no tiene Tenant. Sin orígenes configurados no se envía ningún header.
     *
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $platform = [
        'allowedOrigins' => [],
        'allowedOriginsPatterns' => [],
        'supportsCredentials' => false,
        'allowedHeaders' => ['Content-Type', 'Accept', 'Authorization'],
        'exposedHeaders' => [],
        'allowedMethods' => ['GET', 'POST', 'PUT', 'OPTIONS'],
        'maxAge' => 7200,
    ];

    /**
     * Los orígenes se configuran por entorno, separados por coma (el filtro 'cors' solo se
     * aplica a /public/*, ver Routes.php). Sin ninguno configurado no se envía ningún header
     * CORS. BaseConfig no puede sobrescribir arrays vacíos desde el .env, por eso se parsea acá.
     *
     *   cors.allowedOrigins = https://clinica-a.com,https://clinica-b.com
     *   cors.allowedOriginsPatterns = https://[a-z0-9-]+\.miclinica\.com
     *
     * Los patrones son regex parciales (el filtro los envuelve en #\A…\z#) y no pueden
     * contener comas.
     */
    public function __construct()
    {
        parent::__construct();

        $this->default['allowedOrigins'] = $this->listFromEnv('cors.allowedOrigins');
        $this->default['allowedOriginsPatterns'] = $this->listFromEnv('cors.allowedOriginsPatterns');

        $this->platform['allowedOrigins'] = $this->default['allowedOrigins'];
        $this->platform['allowedOriginsPatterns'] = $this->default['allowedOriginsPatterns'];
    }

    /**
     * @return list<string>
     */
    private function listFromEnv(string $key): array
    {
        $items = array_map('trim', explode(',', (string) env($key, '')));

        return array_values(array_filter($items, static fn (string $item): bool => $item !== ''));
    }
}

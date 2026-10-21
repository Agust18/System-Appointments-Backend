<?php

namespace App\Libraries;

use App\Exception\HasErrorCode;
use App\Exception\Validation\ValidationException;
use CodeIgniter\Debug\BaseExceptionHandler;
use CodeIgniter\Debug\ExceptionHandlerInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Cors as CorsConfig;
use Throwable;

final class ApiExceptionHandler extends BaseExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(
        Throwable $exception,
        RequestInterface $request,
        ResponseInterface $response,
        int $statusCode,
        int $exitCode,
    ): void {
        $code = $exception->getCode();

        $httpCode = ($code >= 400 && $code < 600) ? $code : 500;
        $message  = $httpCode < 500 ? $exception->getMessage() : 'Internal server error';

        if (! headers_sent()) {
            header(
                sprintf('HTTP/%s %s %s', $request->getProtocolVersion(), $httpCode, $response->getReasonPhrase()),
                true,
                $httpCode
            );
            header('Content-Type: application/json; charset=UTF-8');
            $this->addCorsHeaders($request);
        }

        echo json_encode(self::bodyFor($exception, $message));

        exit($exitCode);
    }

    /**
     * @return array<string, mixed>
     */
    public static function bodyFor(Throwable $exception, string $message): array
    {
        if ($exception instanceof ValidationException) {
            return ['error' => $message, 'errors' => $exception->getErrors()];
        }

        $body = ['error' => $message];

        if ($exception instanceof HasErrorCode) {
            $body['code'] = $exception->errorCode();
        }

        return $body;
    }

    /**
     * Replica lo que hace el filtro 'cors' (CodeIgniter\Filters\Cors) en su fase 'after'.
     * Hace falta repetirlo aca porque este handler corta la ejecucion con exit() antes
     * de que ese filtro llegue a correr, asi que las respuestas de error quedaban sin
     * el header Access-Control-Allow-Origin y el navegador las trataba como error de red.
     */
    private function addCorsHeaders(RequestInterface $request): void
    {
        $config = config(CorsConfig::class)->default;

        $origin = method_exists($request, 'hasHeader') && $request->hasHeader('Origin')
            ? $request->getHeaderLine('Origin')
            : null;

        $allowedOrigin = self::allowedOriginFor($config, $origin);

        if ($allowedOrigin !== null) {
            header('Access-Control-Allow-Origin: ' . $allowedOrigin);

            // La respuesta depende del Origin del request salvo con un único origen fijo.
            if (! $this->hasSingleFixedOrigin($config)) {
                header('Vary: Origin');
            }
        }

        if (! empty($config['supportsCredentials'])) {
            header('Access-Control-Allow-Credentials: true');
        }
    }

    /**
     * Origen que corresponde devolver en Access-Control-Allow-Origin, o null si ninguno.
     * Misma regla que CodeIgniter\HTTP\Cors: un único origen sin patrones se devuelve
     * siempre; con varios o con patrones, solo si el Origin del request coincide.
     *
     * @param array{allowedOrigins?: list<string>, allowedOriginsPatterns?: list<string>} $config
     */
    public static function allowedOriginFor(array $config, ?string $origin): ?string
    {
        $allowedOrigins = $config['allowedOrigins'] ?? [];
        $patterns       = $config['allowedOriginsPatterns'] ?? [];

        if (count($allowedOrigins) === 1 && $patterns === []) {
            return $allowedOrigins[0];
        }

        if ($origin === null) {
            return null;
        }

        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        foreach ($patterns as $pattern) {
            if (preg_match('#\A' . $pattern . '\z#', $origin) === 1) {
                return $origin;
            }
        }

        return null;
    }

    private function hasSingleFixedOrigin(array $config): bool
    {
        return count($config['allowedOrigins'] ?? []) === 1 && ($config['allowedOriginsPatterns'] ?? []) === [];
    }
}

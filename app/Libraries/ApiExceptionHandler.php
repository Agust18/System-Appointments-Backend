<?php

namespace App\Libraries;

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

        if ($exception instanceof ValidationException) {
            echo json_encode(['error' => $message, 'errors' => $exception->getErrors()]);

            exit($exitCode);
        }

        echo json_encode(['error' => $message]);

        exit($exitCode);
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

        $allowedOrigins = $config['allowedOrigins'] ?? [];
        $originCount     = count($allowedOrigins);

        if ($originCount === 1 && empty($config['allowedOriginsPatterns'])) {
            header('Access-Control-Allow-Origin: ' . $allowedOrigins[0]);
        } elseif ($originCount > 1 && method_exists($request, 'hasHeader') && $request->hasHeader('Origin')) {
            $origin = $request->getHeaderLine('Origin');

            if (in_array($origin, $allowedOrigins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }
        }

        if (! empty($config['supportsCredentials'])) {
            header('Access-Control-Allow-Credentials: true');
        }
    }
}

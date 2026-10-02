<?php

namespace App\Filters;

use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use InvalidArgumentException;

/**
 * Limita la cantidad de requests por IP con el Throttler de CodeIgniter (token bucket
 * sobre el cache). Se usa con dos argumentos: `throttle:{capacidad},{segundos}`, por ejemplo
 * `throttle:5,60` permite 5 requests por minuto.
 *
 * Cada combinación IP + método + límite tiene su propio bucket, así que los GET del catálogo
 * no gastan el cupo del POST de reserva.
 *
 * Al excederse corta con un 429 (devuelve el Response, no lanza excepción, como los demás
 * Filters). La respuesta se arma desde service('response') —el mismo objeto al que el filtro
 * 'cors' ya agregó sus headers— para que el navegador pueda leer el 429 y su Retry-After:
 * por eso 'cors' debe ir antes que 'throttle' en la lista de filtros de la ruta.
 */
final class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        [$capacity, $seconds] = $this->parseArguments($arguments);

        $this->warnIfThrottlingCannotWork();

        $throttler = service('throttler');
        $key = 'public_throttle_' . sha1(implode('|', [
            $request->getIPAddress(),
            strtoupper($request->getMethod()),
            $capacity,
            $seconds,
        ]));

        if ($throttler->check($key, $capacity, $seconds)) {
            return null;
        }

        return service('response')
            ->setStatusCode(429)
            ->setHeader('Retry-After', (string) $throttler->getTokenTime())
            ->setJSON(['error' => 'Demasiadas solicitudes. Por favor, esperá unos segundos e intentá nuevamente.']);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    /**
     * @param list<string>|null $arguments
     *
     * @return array{0: int, 1: int}
     */
    private function parseArguments(?array $arguments): array
    {
        $capacity = $arguments[0] ?? null;
        $seconds  = $arguments[1] ?? null;

        if (!ctype_digit((string) $capacity) || !ctype_digit((string) $seconds) || (int) $capacity < 1 || (int) $seconds < 1) {
            throw new InvalidArgumentException(
                "El filtro 'throttle' requiere dos enteros positivos: throttle:{capacidad},{segundos}."
            );
        }

        return [(int) $capacity, (int) $seconds];
    }

    /**
     * Si el cache de archivos no se puede usar, CodeIgniter cae al handler 'dummy' (backupHandler)
     * y el throttle deja de limitar sin ningún error. Se deja pasar el request (falla abierto) pero
     * con un aviso crítico en cada request para que el problema no pase desapercibido.
     */
    private function warnIfThrottlingCannotWork(): void
    {
        if (ENVIRONMENT !== 'testing' && service('cache') instanceof DummyHandler) {
            log_message(
                'critical',
                'Throttle desactivado: el cache activo es DummyHandler (¿writable/cache sin permisos de escritura?). '
                . 'Los endpoints públicos no tienen límite de requests.'
            );
        }
    }
}

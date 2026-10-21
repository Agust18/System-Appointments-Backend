<?php
namespace App\Services\Evolution\Projection;

use App\Constants\Role;
use App\Converter\Evolution\Projection\EvolutionAccessLogProjectionToEvolutionAccessLogProjectionResponseConverter;
use App\Dto\Request\Evolution\EvolutionAccessLogFilterRequest;
use App\Exception\Evolution\EvolutionAccessDeniedException;
use App\Models\Evolution\EvolutionAccessLogModel;

/**
 * Registro de quién leyó evoluciones: solo el ADMIN. Consultarlo no deja rastro propio.
 */
final class EvolutionAccessLogsProjectionSearcherService
{
    private EvolutionAccessLogModel $logModel;
    private EvolutionAccessLogProjectionToEvolutionAccessLogProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->logModel = new EvolutionAccessLogModel();
        $this->converter = new EvolutionAccessLogProjectionToEvolutionAccessLogProjectionResponseConverter();
    }

    /**
     * @return array{data: \App\Dto\Response\Evolution\Projection\EvolutionAccessLogProjectionResponse[], total: int}
     */
    public function search(EvolutionAccessLogFilterRequest $request, int $roleId): array
    {
        if ($roleId !== Role::ADMIN) {
            throw new EvolutionAccessDeniedException('Solo un administrador puede ver el registro de accesos.');
        }

        $result = $this->logModel->search($request);

        return [
            'data'  => array_map(fn ($log) => $this->converter->convert($log), $result['data']),
            'total' => $result['total'],
        ];
    }
}

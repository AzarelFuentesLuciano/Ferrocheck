<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use App\Auth\AuthenticatedUser;
use App\Repositories\Rail\ConsistRepository;
use DomainException;

final class ConsistWorkflowService
{
    public function __construct(
        private ConsistDocumentBuilder $builder,
        private RouteCodeCatalogLoader $catalogLoader,
        private ConsistRepository $repository,
        private ?ConsistGoldenMasterComparator $comparator = null,
        private ?ConsistWorkbookExporter $exporter = null,
        private ?ConsistWorkbookValidator $exportValidator = null,
        private ?string $exportDirectory = null,
    ) {
    }

    public function create(
        string $token,
        array $analysis,
        string $startDate,
        string $endDate,
        AuthenticatedUser $user,
    ): array
    {
        $this->require($user, 'rail.consist.generar');
        $period = ConsistOperationalPeriod::fromInput($startDate, $endDate);
        $draft = $this->builder->build($token, $analysis, $this->catalogLoader->load(), $user->id, $period);
        return $this->repository->createDraft($draft->toArray());
    }

    public function export(int $id, AuthenticatedUser $user): array
    {
        $this->require($user, 'rail.consist.exportar');
        if ($this->exporter === null || $this->exportValidator === null || $this->exportDirectory === null) {
            throw new DomainException('La exportación de Consist no está disponible.');
        }
        $consist = $this->repository->find($id);
        if ($consist === null) {
            throw new DomainException('El Consist solicitado no existe.');
        }
        $result = $this->exporter->export($consist, $this->exportDirectory);
        try {
            $result['validation'] = $this->exportValidator->validate($result['path']);
            $this->repository->recordExport(
                $id,
                $user->id,
                (string) $result['sha256'],
                (string) $result['filename'],
                (array) ($result['summary_warnings'] ?? []),
            );
        } catch (\Throwable $exception) {
            if (is_file((string) $result['path'])) {
                unlink((string) $result['path']);
            }
            throw $exception;
        }
        return $result;
    }

    public function find(int $id, AuthenticatedUser $user): ?array
    {
        $this->require($user, 'rail.consist.ver');
        return $this->repository->find($id);
    }

    public function detail(int $id, string $vin, AuthenticatedUser $user): ?array
    {
        $this->require($user, 'rail.consist.ver_detalle');
        $consist = $this->repository->find($id);
        if ($consist === null) {
            return null;
        }
        foreach ($consist['units'] as $unit) {
            if ($unit['vin'] === mb_strtoupper(trim($vin), 'UTF-8')) {
                return ['consist' => $consist, 'unit' => $unit];
            }
        }
        throw new DomainException('El VIN no pertenece al Consist solicitado.');
    }

    public function history(array $filters, int $page, AuthenticatedUser $user): array
    {
        $this->require($user, 'rail.consist.ver_historial');
        return $this->repository->history($filters, $page);
    }

    public function compare(array $consist, AuthenticatedUser $user): array
    {
        $this->require($user, 'rail.consist.ver');
        if ($this->comparator === null) {
            throw new DomainException('La comparación de referencia no está habilitada.');
        }
        $units = array_map(static fn (array $unit): array => [
            'global_position' => (int) $unit['global_position'],
            'vin' => (string) $unit['vin'],
            'final_columns' => (array) $unit['final_data_json'],
        ], $consist['units'] ?? []);
        return $this->comparator->compare(['units' => $units]);
    }

    private function require(AuthenticatedUser $user, string $permission): void
    {
        if (!$user->can($permission) && !$user->isSuperAdministrator()) {
            throw new DomainException('No cuenta con permiso para realizar esta acción.');
        }
    }
}

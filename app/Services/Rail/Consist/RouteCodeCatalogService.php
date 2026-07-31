<?php

declare(strict_types=1);

namespace App\Services\Rail\Consist;

use App\Auth\AuthenticatedUser;
use App\Repositories\Rail\RouteCodeCatalogRepository;
use DomainException;
use RuntimeException;

final class RouteCodeCatalogService
{
    public function __construct(
        private RouteCodeCatalogRepository $repository,
        private int $maxFileSize = 10485760,
    ) {
    }

    public function status(): array
    {
        $catalog = $this->repository->activeCatalog();
        return [
            'active' => $catalog !== null,
            'version' => $catalog['version'] ?? null,
        ];
    }

    public function import(array $file, AuthenticatedUser $user): array
    {
        if (!$user->can('rail.catalogos.importar') && !$user->isSuperAdministrator()) {
            throw new DomainException('No cuenta con permiso para importar catálogos de Rail.');
        }
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        $temporary = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $original = basename(str_replace('\\', '/', (string) ($file['name'] ?? '')));
        if ($error !== UPLOAD_ERR_OK || $temporary === '' || !is_file($temporary)) {
            throw new RuntimeException('Seleccione un archivo XLSX válido para el catálogo maestro.');
        }
        if ($original === ''
            || mb_strlen($original) > 255
            || $size < 1
            || $size > $this->maxFileSize
            || strtolower(pathinfo($original, PATHINFO_EXTENSION)) !== 'xlsx'
        ) {
            throw new RuntimeException('El catálogo debe ser un XLSX de hasta 10 MB.');
        }
        $catalog = (new RouteCodeCatalogLoader($temporary, null))->load();
        $catalog['version']['source_filename'] = $original;
        return $this->repository->import($catalog, $user->id);
    }
}

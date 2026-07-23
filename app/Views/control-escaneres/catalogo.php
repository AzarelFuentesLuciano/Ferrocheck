<?php
declare(strict_types=1);

use App\Domain\ControlEscaneres\ScannerStatus;
use App\ViewModels\ControlEscaneres\ScannerCatalogViewModel;
use App\Support\SpanishDateFormatter;

$vistaActual = 'catalogo';
$catalogViewModel = $catalogViewModel ?? new ScannerCatalogViewModel([], 0, 1, 25, 1, []);
$h = static fn(mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$baseParams = ['modulo' => 'control-escaneres', 'seccion' => 'catalogo'];
$filterParams = array_filter([
    'q' => $catalogViewModel->filters['search'] ?? null,
    'marca' => $catalogViewModel->filters['brand'] ?? null,
    'modelo' => $catalogViewModel->filters['model'] ?? null,
    'area' => $catalogViewModel->filters['area'] ?? null,
    'area_propietaria' => $catalogViewModel->filters['organizationalArea'] ?? null,
    'estado' => $catalogViewModel->filters['status'] ?? null,
    'activo' => isset($catalogViewModel->filters['active']) ? (int) $catalogViewModel->filters['active'] : null,
    'incidencia' => isset($catalogViewModel->filters['withIncident']) ? (int) $catalogViewModel->filters['withIncident'] : null,
    'orden' => $catalogViewModel->filters['orderBy'] ?? null,
    'direccion' => $catalogViewModel->filters['direction'] ?? null,
    'por_pagina' => $catalogViewModel->perPage,
], static fn(mixed $value): bool => $value !== null && $value !== '');
$buildUrl = static fn(array $params): string => BASE_URL . '/index.php?' . http_build_query($params);
$operationUrl = static fn(string $section, int $id): string => BASE_URL . '/index.php?modulo=control-escaneres&amp;seccion=' . $h($section) . '&amp;scanner_id=' . $id;
$actionLabels = [
    'expediente' => 'Expediente',
    'editar' => 'Editar',
    'entrega' => 'Entregar',
    'recepcion' => 'Recibir',
    'incidencias' => 'Incidencia',
    'mantenimiento' => 'Mantenimiento',
    'baja' => 'Baja lógica',
    'reactivar' => 'Reactivar',
];

ob_start();
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/control-escaneres/catalogo.css">
<div class="vascor-ui ce-catalog">
    <?php
    $pageTitle = 'Escáneres registrados';
    $pageDescription = 'Consulta el estado real de los equipos y abre únicamente las acciones disponibles.';
    $breadcrumbs = ['Control de Escáneres', 'Catálogo'];
    require dirname(__DIR__) . '/components/page-header.php';
    ?>

    <?php foreach ($catalogViewModel->flashMessages as $message): ?>
        <?php
        $alertType = in_array($message['type'] ?? '', ['success', 'warning', 'error'], true) ? $message['type'] : 'info';
        $alertMessage = $message['message'] ?? '';
        require dirname(__DIR__) . '/components/alert.php';
        ?>
    <?php endforeach; ?>

    <?php $finderMode='automatico';$finderButtonLabel='Escanear QR';$finderTitle='Localizar escáner';require __DIR__.'/partials/equipment-finder.php'; ?>

    <details class="vo-filter" open>
        <summary>Filtrar catálogo</summary>
        <form method="get" aria-label="Filtros del catálogo">
            <input type="hidden" name="modulo" value="control-escaneres">
            <input type="hidden" name="seccion" value="catalogo">
            <div class="vo-filter__grid">
                <div class="vo-field">
                    <label for="catalog-search">Buscar equipo</label>
                    <input class="vo-input" id="catalog-search" name="q" maxlength="100" value="<?= $h($catalogViewModel->filters['search'] ?? '') ?>" placeholder="Ejemplo: SC-5537">
                    <small class="vo-field__help">Código, TAG, IMEI, serie, teléfono, ICCID, marca o modelo.</small>
                </div>
                <div class="vo-field">
                    <label for="catalog-brand">Marca <span>(opcional)</span></label>
                    <input class="vo-input" id="catalog-brand" name="marca" maxlength="100" value="<?= $h($catalogViewModel->filters['brand'] ?? '') ?>">
                </div>
                <div class="vo-field">
                    <label for="catalog-model">Modelo <span>(opcional)</span></label>
                    <input class="vo-input" id="catalog-model" name="modelo" maxlength="100" value="<?= $h($catalogViewModel->filters['model'] ?? '') ?>">
                </div>
                <div class="vo-field">
                    <label for="catalog-area">Área operativa habitual <span>(opcional)</span></label>
                    <input class="vo-input" id="catalog-area" name="area" maxlength="100" value="<?= $h($catalogViewModel->filters['area'] ?? '') ?>">
                </div>
                <div class="vo-field"><label for="catalog-owner-area">Área propietaria</label><select class="vo-select" id="catalog-owner-area" name="area_propietaria"><option value="">Todas</option><option value="unassigned" <?= ($catalogViewModel->filters['organizationalArea']??null)==='unassigned'?'selected':'' ?>>Sin asignar</option><?php foreach(($catalogOrganizationalAreas??[])as$area):?><option value="<?= (int)$area['id'] ?>" <?= (string)($catalogViewModel->filters['organizationalArea']??'')===(string)$area['id']?'selected':'' ?>><?= $h($area['nombre']) ?></option><?php endforeach?></select></div>
                <div class="vo-field">
                    <label for="catalog-status">Estado</label>
                    <select class="vo-select" id="catalog-status" name="estado">
                        <option value="">Todos los estados</option>
                        <?php foreach (ScannerStatus::VALUES as $status): ?>
                            <option value="<?= $h($status) ?>" <?= ($catalogViewModel->filters['status'] ?? null) === $status ? 'selected' : '' ?>><?= $h(ucwords(str_replace('_', ' ', $status))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="vo-field">
                    <label for="catalog-active">Actividad</label>
                    <select class="vo-select" id="catalog-active" name="activo">
                        <option value="">Activos e inactivos</option>
                        <option value="1" <?= ($catalogViewModel->filters['active'] ?? null) === true ? 'selected' : '' ?>>Sólo activos</option>
                        <option value="0" <?= ($catalogViewModel->filters['active'] ?? null) === false ? 'selected' : '' ?>>Sólo inactivos</option>
                    </select>
                </div>
                <div class="vo-field">
                    <label for="catalog-incident">Incidencias</label>
                    <select class="vo-select" id="catalog-incident" name="incidencia">
                        <option value="">Con y sin incidencias</option>
                        <option value="1" <?= ($catalogViewModel->filters['withIncident'] ?? null) === true ? 'selected' : '' ?>>Con incidencia abierta</option>
                        <option value="0" <?= ($catalogViewModel->filters['withIncident'] ?? null) === false ? 'selected' : '' ?>>Sin incidencia abierta</option>
                    </select>
                </div>
            </div>
            <div class="vo-actions">
                <button class="vo-btn vo-btn--primary">Aplicar filtros</button>
                <a class="vo-btn vo-btn--subtle" href="<?= $h($buildUrl($baseParams)) ?>">Limpiar filtros</a>
            </div>
        </form>
    </details>

    <section aria-labelledby="catalog-results">
        <div class="ce-catalog__heading">
            <h3 id="catalog-results"><?= $catalogViewModel->total ?> <?= $catalogViewModel->total === 1 ? 'equipo encontrado' : 'equipos encontrados' ?></h3>
            <div class="vo-actions">
                <a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=importar-inventario">Importar inventario</a>
                <a class="vo-btn vo-btn--primary" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=registrar">+ Registrar escáner</a>
            </div>
        </div>

        <?php if ($catalogViewModel->items === []): ?>
            <div class="vo-card">
                <?php
                $emptyTitle = 'No encontramos escáneres con estos filtros';
                $emptyDescription = 'Ajusta la búsqueda o limpia los filtros para volver a ver el catálogo.';
                $emptyActionUrl = $buildUrl($baseParams);
                $emptyActionLabel = 'Limpiar filtros';
                require dirname(__DIR__) . '/components/empty-state.php';
                ?>
            </div>
        <?php else: ?>
            <div class="vo-table-wrap" tabindex="0" aria-label="Tabla de escáneres; puede desplazarse horizontalmente">
                <table class="vo-table">
                    <thead><tr><th scope="col">QR</th><th scope="col">Equipo</th><th scope="col">TAG</th><th scope="col">Marca y modelo</th><th scope="col">Serie</th><th scope="col">Áreas</th><th scope="col">Contacto protegido</th><th scope="col">Estado</th><th scope="col">Conservación</th><th scope="col">Responsable actual</th><th scope="col">Última entrega</th><th scope="col">Incidencia</th><th scope="col">Acciones</th></tr></thead>
                    <tbody>
                    <?php foreach ($catalogViewModel->items as $item): ?>
                        <tr>
                            <td><a href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $item->id ?>&amp;size=700" target="_blank" rel="noopener"><img src="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $item->id ?>&amp;size=160" width="64" height="64" loading="lazy" alt="QR de <?= $h($item->code) ?>"></a></td>
                            <td><strong><?= $h($item->code) ?></strong><small>#<?= $item->id ?> · <?= $item->active ? 'Activo' : 'Inactivo' ?></small><a href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $item->id ?>&amp;size=700&amp;download=1">Descargar QR</a></td>
                            <td><?= $h($item->tag ?? '—') ?></td>
                            <td><?= $h($item->brand) ?><small><?= $h($item->model) ?></small></td>
                            <td><?= $h($item->serial) ?></td>
                            <td><small>Propietaria</small><strong><?= $h($item->organizationalArea??'Sin asignar') ?></strong><small>Operativa habitual: <?= $h($item->area??'Sin definir') ?></small></td>
                            <td><span aria-label="IMEI protegido"><?= $h($item->maskedImei) ?></span><small aria-label="Teléfono protegido"><?= $h($item->maskedPhone) ?></small><small aria-label="ICCID protegido"><?= $h($item->maskedIccid) ?></small></td>
                            <td><?php $badgeStatus = $item->active ? $item->status : 'inactivo'; $badgeLabel = $item->active ? ucwords(str_replace('_', ' ', $item->status)) : 'Inactivo'; require dirname(__DIR__) . '/components/badge.php'; ?></td>
                            <td><?= $item->conservation === null ? '—' : (int) $item->conservation . '/100' ?></td>
                            <td><?= $h($item->currentResponsible ?? '—') ?></td>
                            <td><?= $h(SpanishDateFormatter::format($item->lastDelivery,'Sin entregas')) ?></td>
                            <td><?php if ($item->hasOpenIncident): $badgeStatus = 'critica'; $badgeLabel = 'Incidencia abierta'; require dirname(__DIR__) . '/components/badge.php'; else: ?>Sin incidencias<?php endif; ?></td>
                            <td><div class="vo-actions"><?php foreach ($item->actions as $index => $action): ?><a class="vo-btn <?= $index === 0 ? '' : 'vo-btn--subtle' ?>" href="<?= $operationUrl($action, $item->id) ?>"><?= $h($actionLabels[$action] ?? $action) ?></a><?php endforeach; ?><a class="vo-btn vo-btn--subtle" href="<?= BASE_URL ?>/index.php?modulo=control-escaneres&amp;seccion=qr&amp;scanner_id=<?= $item->id ?>&amp;size=700" target="_blank" rel="noopener">Imprimir QR</a></div></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $paginationPage = $catalogViewModel->page;
            $paginationTotal = $catalogViewModel->totalPages;
            if ($paginationPage > 1) $paginationPrevious = $buildUrl($baseParams + $filterParams + ['pagina' => $paginationPage - 1]);
            if ($paginationPage < $paginationTotal) $paginationNext = $buildUrl($baseParams + $filterParams + ['pagina' => $paginationPage + 1]);
            $paginationPages = [];
            for ($page = max(1, $paginationPage - 2); $page <= min($paginationTotal, $paginationPage + 2); $page++) {
                $paginationPages[$page] = $buildUrl($baseParams + $filterParams + ['pagina' => $page]);
            }
            require dirname(__DIR__) . '/components/pagination.php';
            unset($paginationPrevious, $paginationNext, $paginationPages);
            ?>
        <?php endif; ?>
    </section>
</div>
<?php $contenidoModulo = ob_get_clean(); require __DIR__ . '/plantilla.php'; ?>

<?php

declare(strict_types=1);

namespace App\Controllers\Rail;

use App\Auth\AuthenticatedUser;
use App\Auth\Csrf;
use App\Core\Rendering\RenderAdapter;
use App\Core\Rendering\RenderContext;
use App\Services\ModuleNavigationBuilder;
use App\Services\Rail\Consist\ConsistAnalysisResultStore;
use App\Services\Rail\Consist\ConsistOperationalSummary;
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistSpreadsheetIdentityValidator;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;
use App\Services\Rail\Consist\ConsistUploadValidationException;
use App\Services\Rail\Consist\ConsistUploadValidator;
use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;
use App\Services\Rail\Consist\ConsistVinExtractor;
use App\Services\Rail\Consist\ConsistWorkflowService;
use App\Services\Rail\Consist\RouteCodeCatalogService;
use App\Support\AuthenticatedHeaderBuilder;
use App\Support\Rail\RailFlashStore;
use App\ViewModels\Rail\ConsistUploadViewModel;
use App\ViewModels\Rail\ConsistAnalysisViewModel;
use DomainException;
use RuntimeException;
use Throwable;

final class RailController
{
    private string $baseUrl;

    public function __construct(
        private AuthenticatedUser $authenticatedUser,
        private string $logoutCsrf,
        private ModuleNavigationBuilder $moduleNavigationBuilder,
        private ?RenderAdapter $renderAdapter = null,
        ?string $baseUrl = null,
        private ?Csrf $csrf = null,
        private ?RailFlashStore $flashStore = null,
        private ?ConsistTemporaryUploadStore $temporaryStore = null,
        private ?ConsistUploadValidator $uploadValidator = null,
        private ?ConsistSpreadsheetPreviewer $spreadsheetPreviewer = null,
        private ?ConsistVinExtractor $vinExtractor = null,
        private ?ConsistVinCrossAnalyzer $vinCrossAnalyzer = null,
        private ?ConsistAnalysisResultStore $analysisResultStore = null,
        private ?ConsistSpreadsheetIdentityValidator $identityValidator = null,
        private ?ConsistWorkflowService $workflowService = null,
        private ?RouteCodeCatalogService $catalogService = null,
        private ?ConsistOperationalSummary $operationalSummary = null,
    ) {
        $this->baseUrl = $baseUrl !== null
            ? rtrim($baseUrl, '/')
            : (defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '');
    }

    public function dispatch(string $method, array $query = [], array $post = [], array $files = []): ?string
    {
        if (strtoupper($method) !== 'POST') {
            if (($query['accion'] ?? '') === 'exportar_consist') {
                return $this->downloadConsist((int) ($query['id'] ?? 0));
            }
            return $this->render($query, $this->uploadViewModel($query));
        }

        [$section, $subsection] = $this->resolveLocation($query, $this->navigationConfiguration());
        $action = (string) ($post['action'] ?? '');
        if ($section === 'configuracion' && $subsection === 'catalogos' && $action === 'import_route_catalog') {
            return $this->handleCatalogImport($post, $files);
        }
        $workflowActions = ['create_consist_draft'];
        if ($section !== 'consist-rail'
            || ($subsection !== 'registrar' && !in_array($action, $workflowActions, true))
        ) {
            return $this->render($query, $this->uploadViewModel($query));
        }
        $this->requireUploadDependencies();
        $redirect = $this->consistUploadUrl();
        $requestedAnalysisToken = trim((string) ($post['batch_token'] ?? ''));
        if (in_array((string) ($post['action'] ?? ''), ['analyze_vin_cross', 'create_consist_draft'], true)
            && preg_match('/^[a-f0-9]{64}$/', $requestedAnalysisToken)
        ) {
            $redirect .= '&preview=' . rawurlencode($requestedAnalysisToken);
        }
        $stagedToken = null;

        try {
            if (!$this->csrf->validate((string) ($post['_csrf'] ?? ''))) {
                throw new RuntimeException('La sesión del formulario expiró. Intenta nuevamente.');
            }
            if (($post['action'] ?? '') === 'analyze_vin_cross') {
                return $this->analyzeVinCross($post, $redirect);
            }
            if (in_array($action, $workflowActions, true)) {
                return $this->handleWorkflowAction($post);
            }
            $validated = $this->uploadValidator->validateBatch($files);
            $staged = $this->temporaryStore->stageBatch($validated);
            $stagedToken = (string) $staged['token'];
            $identities = $this->identityValidator()->validateBatch($staged['files']);
            $preview = $this->spreadsheetPreviewer->previewBatch($staged['files'], $identities);
            $this->temporaryStore->savePreview($stagedToken, $preview);
            $this->csrf->rotate();
            $this->flashStore->add(
                $preview['valid'] ? 'success' : 'warning',
                $preview['valid']
                    ? 'Los tres archivos fueron cargados y validados.'
                    : 'La carga fue procesada, pero contiene errores que impiden continuar.',
            );
            $redirect .= '&preview=' . rawurlencode($stagedToken);
        } catch (ConsistUploadValidationException $exception) {
            if ($stagedToken !== null) {
                $this->temporaryStore->discard($stagedToken);
            }
            $this->flashStore->add(
                'error',
                'No se pudo validar el lote. ' . $exception->getMessage(),
                $exception->field,
            );
        } catch (Throwable $exception) {
            if ($stagedToken !== null) {
                $this->temporaryStore->discard($stagedToken);
            }
            $this->flashStore->add(
                'error',
                $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Ocurrió un error inesperado al procesar el lote.',
            );
        }

        header('Location: ' . $redirect, true, 303);
        return null;
    }

    public function render(array $query = [], ?ConsistUploadViewModel $consistUpload = null): string
    {
        $navigation = $this->navigationConfiguration();
        [$section, $subsection] = $this->resolveLocation($query, $navigation);
        $catalogPage = ($this->catalogService?->status() ?? ['active' => false, 'version' => null]) + [
            'can_import' => $this->authenticatedUser->can('rail.catalogos.importar')
                || $this->authenticatedUser->isSuperAdministrator(),
        ];
        $consistPage = null;
        if ($section === 'consist-rail' && $this->workflowService !== null) {
            if (!$this->authenticatedUser->can('rail.consist.ver') && !$this->authenticatedUser->isSuperAdministrator()) {
                $consistPage = ['forbidden' => true];
            } else {
                if ($subsection === 'consultar' && (int) ($query['id'] ?? 0) > 0) {
                    $consistPage = isset($query['vin_detalle'])
                        ? $this->workflowService->detail(
                            (int) $query['id'],
                            (string) $query['vin_detalle'],
                            $this->authenticatedUser,
                        )
                        : $this->workflowService->find((int) $query['id'], $this->authenticatedUser);
                    if (is_array($consistPage)
                        && defined('APP_ENV')
                        && APP_ENV !== 'production'
                        && ($query['comparar_golden'] ?? '') === '1'
                        && isset($consistPage['units'])
                    ) {
                        $consistPage['golden_comparison'] = $this->workflowService->compare(
                            $consistPage,
                            $this->authenticatedUser,
                        );
                    }
                    if (is_array($consistPage) && isset($consistPage['units'])) {
                        $consistPage['can_export'] = $this->authenticatedUser->can('rail.consist.exportar')
                            || $this->authenticatedUser->isSuperAdministrator();
                        $consistPage = $this->paginateConsistPage($consistPage, $query);
                    }
                } elseif ($subsection === 'historial') {
                    $consistPage = $this->workflowService->history([
                        'folio' => trim((string) ($query['folio'] ?? '')),
                        'vin' => trim((string) ($query['vin'] ?? '')),
                        'estado' => trim((string) ($query['estado'] ?? '')),
                        'desde' => trim((string) ($query['desde'] ?? '')),
                        'hasta' => trim((string) ($query['hasta'] ?? '')),
                        'usuario' => trim((string) ($query['usuario'] ?? '')),
                    ], max(1, (int) ($query['pagina'] ?? 1)), $this->authenticatedUser);
                }
            }
        }
        [$moduleNavigation, $content] = $this->renderRailViews(
            $section,
            $subsection,
            $navigation,
            $consistUpload,
            $consistPage,
            $catalogPage,
        );
        $additionalScripts = $section === 'consist-rail' && $subsection === 'registrar'
            ? [$this->baseUrl . '/assets/js/rail/consist-upload-progress.js']
            : [];
        $header = AuthenticatedHeaderBuilder::build(
            $this->authenticatedUser,
            $this->baseUrl . '/index.php?modulo=auth&accion=logout',
            $this->logoutCsrf,
            [
                'systemName' => 'VASCOR OPS',
                'systemSubtitle' => 'Plataforma Operativa',
                'versionLabel' => 'Versión v1.0',
                'menuLabel' => 'Abrir navegación',
            ],
        );

        $context = new RenderContext(
            pageTitle: 'Rail | VASCOR OPS',
            documentLanguage: 'es',
            assetBaseUrl: $this->baseUrl,
            activeModule: 'rail',
            activeSection: $section,
            modules: $this->moduleNavigationBuilder->build($this->baseUrl),
            moduleNavigation: $moduleNavigation,
            content: $content,
            additionalStyles: [$this->baseUrl . '/assets/css/rail/rail.css'],
            additionalScripts: $additionalScripts,
            header: $header,
            footer: [
                'title' => 'VASCOR OPS v1.0',
                'subtitle' => 'Plataforma Operativa',
                'creditLabel' => 'Desarrollado por',
                'developer' => 'Ing. Azarel Fuentes Luciano',
                'year' => '2026',
            ],
            sidebarLabel: 'Módulos principales',
        );

        return ($this->renderAdapter ?? new RenderAdapter())->render($context);
    }

    private function navigationConfiguration(): array
    {
        $path = dirname(__DIR__, 3) . '/config/rail-navigation.php';
        $navigation = require $path;
        if (!is_array($navigation) || !isset($navigation['ferrocheck'])) {
            throw new RuntimeException('La navegación de Rail no está disponible.');
        }

        return $navigation;
    }

    private function resolveLocation(array $query, array $navigation): array
    {
        $section = isset($query['seccion']) && is_string($query['seccion'])
            ? trim($query['seccion'])
            : 'ferrocheck';
        if (!isset($navigation[$section]) || !is_array($navigation[$section])) {
            $section = 'ferrocheck';
        }

        $sectionConfiguration = $navigation[$section];
        $subsections = isset($sectionConfiguration['subsections']) && is_array($sectionConfiguration['subsections'])
            ? $sectionConfiguration['subsections']
            : [];
        $defaultSubsection = isset($sectionConfiguration['default_subsection']) && is_string($sectionConfiguration['default_subsection'])
            ? $sectionConfiguration['default_subsection']
            : '';
        $subsection = isset($query['subseccion']) && is_string($query['subseccion'])
            ? trim($query['subseccion'])
            : $defaultSubsection;
        if (!isset($subsections[$subsection]) || !is_array($subsections[$subsection])) {
            $subsection = isset($subsections[$defaultSubsection])
                ? $defaultSubsection
                : (string) array_key_first($subsections);
        }

        return [$section, $subsection];
    }

    private function renderRailViews(
        string $section,
        string $subsection,
        array $navigation,
        ?ConsistUploadViewModel $consistUpload,
        ?array $consistPage,
        array $catalogPage,
    ): array
    {
        $railSection = $section;
        $railSubsection = $subsection;
        $railNavigation = $navigation;
        $railBaseUrl = $this->baseUrl;
        $railConsistPage = $consistPage;
        $railCatalogPage = $catalogPage;
        $railMessages = $consistUpload?->messages ?? [];
        $railCsrfToken = $consistUpload?->csrfToken ?? '';
        $initialLevel = ob_get_level();

        try {
            ob_start();
            require dirname(__DIR__, 2) . '/Views/rail/index.php';
            $content = ob_get_clean();
            if (!is_string($content) || trim($content) === '' || !isset($railModuleNavigation) || !is_string($railModuleNavigation)) {
                throw new RuntimeException('Rail no pudo renderizar su contenido.');
            }

            return [$railModuleNavigation, $content];
        } catch (Throwable $exception) {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }
            throw $exception;
        }
    }

    private function uploadViewModel(array $query): ?ConsistUploadViewModel
    {
        if ($this->csrf === null || $this->flashStore === null) {
            return null;
        }

        $preview = null;
        $analysis = null;
        $canAnalyze = false;
        $token = isset($query['preview']) && is_string($query['preview']) ? trim($query['preview']) : '';
        if ($token !== '' && $this->temporaryStore !== null) {
            try {
                $preview = $this->temporaryStore->preview($token);
                $canAnalyze = is_array($preview)
                    && ($preview['valid'] ?? false) === true
                    && count($preview['files'] ?? []) === 3;
                if ($canAnalyze && $this->analysisResultStore !== null) {
                    $storedAnalysis = $this->analysisResultStore->load($token);
                    $analysis = is_array($storedAnalysis) ? new ConsistAnalysisViewModel($storedAnalysis, $query) : null;
                }
            } catch (Throwable $exception) {
                $this->flashStore->add('error', $exception->getMessage());
            }
        }

        return new ConsistUploadViewModel(
            $this->csrf->token(),
            $this->flashStore->consume(),
            $preview,
            $this->importConfiguration(),
            $token,
            $canAnalyze,
            $analysis,
        );
    }

    private function requireUploadDependencies(): void
    {
        if ($this->csrf === null
            || $this->flashStore === null
            || $this->temporaryStore === null
            || $this->uploadValidator === null
            || $this->spreadsheetPreviewer === null
        ) {
            throw new RuntimeException('El flujo de carga de Consist Rail no está disponible.');
        }
    }

    private function consistUploadUrl(): string
    {
        return $this->baseUrl . '/index.php?modulo=rail&seccion=consist-rail&subseccion=registrar';
    }

    private function identityValidator(): ConsistSpreadsheetIdentityValidator
    {
        return $this->identityValidator ??= new ConsistSpreadsheetIdentityValidator(
            $this->importConfiguration(),
        );
    }

    private function importConfiguration(): array
    {
        $configuration = require dirname(__DIR__, 3) . '/config/consist-rail-import.php';
        return is_array($configuration) ? $configuration : [];
    }

    private function analyzeVinCross(array $post, string $redirect): ?string
    {
        if ($this->vinExtractor === null || $this->vinCrossAnalyzer === null || $this->analysisResultStore === null) {
            throw new RuntimeException('El análisis de VIN no está disponible.');
        }
        $token = trim((string) ($post['batch_token'] ?? ''));
        if ($token === '') {
            throw new RuntimeException('El token del lote es obligatorio.');
        }

        $entry = $this->temporaryStore->resolve($token);
        $preview = $entry['preview'] ?? null;
        if (!is_array($preview) || ($preview['valid'] ?? false) !== true || count($entry['files'] ?? []) !== 3) {
            throw new RuntimeException('Los tres archivos deben estar validados antes de analizar el cruce.');
        }

        $configuration = $this->importConfiguration();
        $extracted = [];
        foreach ($configuration['files'] as $field => $definition) {
            if (!isset($entry['files'][$field])) {
                throw new RuntimeException(sprintf('Falta el archivo %s en el lote.', $definition['label']));
            }
            $validatedHeader = $preview['files'][$field]['validated_header'] ?? null;
            $extracted[$field] = $this->vinExtractor->extract(
                $entry['files'][$field],
                $definition,
                is_array($validatedHeader) ? $validatedHeader : null,
            );
        }
        $result = $this->vinCrossAnalyzer->analyze(
            $extracted['vehicle_load_report'],
            $extracted['shippers'],
            $extracted['cnacs'],
        );
        $result['operational_summary'] = ($this->operationalSummary ?? new ConsistOperationalSummary())
            ->fromAnalysis($result);
        $this->analysisResultStore->save($token, $result);
        $this->csrf->rotate();
        $this->flashStore->add('success', 'Cruce de VIN completado.');
        header('Location: ' . $redirect, true, 303);
        return null;
    }

    private function handleWorkflowAction(array $post): ?string
    {
        if ($this->workflowService === null) {
            throw new RuntimeException('El flujo persistente de Consist Rail no está disponible.');
        }
        $action = (string) ($post['action'] ?? '');
        $id = max(0, (int) ($post['consist_id'] ?? 0));
        if ($action === 'create_consist_draft') {
            $token = trim((string) ($post['batch_token'] ?? ''));
            $analysis = $this->analysisResultStore?->load($token);
            if (!is_array($analysis)) {
                throw new RuntimeException('El análisis requerido no está disponible.');
            }
            $draft = $this->workflowService->create(
                $token,
                $analysis,
                trim((string) ($post['fecha_inicio'] ?? '')),
                trim((string) ($post['fecha_fin'] ?? '')),
                $this->authenticatedUser,
            );
            $this->csrf?->rotate();
            $this->flashStore?->add('success', 'Borrador de Consist guardado correctamente.');
            header('Location: ' . $this->consistPageUrl((int) $draft['id']), true, 303);
            return null;
        }
        throw new RuntimeException('La acción solicitada no es válida.');
    }

    private function handleCatalogImport(array $post, array $files): ?string
    {
        $redirect = $this->baseUrl
            . '/index.php?modulo=rail&seccion=configuracion&subseccion=catalogos';
        try {
            if ($this->csrf === null || !$this->csrf->validate((string) ($post['_csrf'] ?? ''))) {
                throw new RuntimeException('La sesión del formulario expiró. Intenta nuevamente.');
            }
            if ($this->catalogService === null) {
                throw new RuntimeException('La importación del catálogo maestro no está disponible.');
            }
            $catalog = $this->catalogService->import(
                (array) ($files['route_catalog'] ?? []),
                $this->authenticatedUser,
            );
            $this->csrf->rotate();
            $this->flashStore?->add(
                'success',
                sprintf(
                    'Catálogo maestro activado correctamente con %d rutas.',
                    (int) ($catalog['version']['record_count'] ?? 0),
                ),
            );
        } catch (Throwable $exception) {
            $this->flashStore?->add(
                'error',
                $exception instanceof RuntimeException || $exception instanceof DomainException
                    ? $exception->getMessage()
                    : 'No fue posible importar el catálogo maestro.',
            );
        }
        header('Location: ' . $redirect, true, 303);
        return null;
    }

    private function downloadConsist(int $id): ?string
    {
        if ($id < 1) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            return 'El Consist solicitado no existe.';
        }
        if ($this->workflowService === null) {
            throw new RuntimeException('La descarga de Consist no está disponible.');
        }
        try {
            $export = $this->workflowService->export($id, $this->authenticatedUser);
        } catch (DomainException $exception) {
            $forbidden = str_contains($exception->getMessage(), 'permiso');
            http_response_code($forbidden ? 403 : 404);
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            return $forbidden
                ? 'No cuenta con permiso para descargar este Consist.'
                : 'El Consist solicitado no existe.';
        }
        $path = (string) $export['path'];
        $filename = (string) $export['filename'];
        try {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . addcslashes($filename, '"\\') . '"');
            header('Content-Length: ' . filesize($path));
            header('X-Content-Type-Options: nosniff');
            readfile($path);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
        return null;
    }

    private function consistPageUrl(int $id): string
    {
        return $this->baseUrl . '/index.php?modulo=rail&seccion=consist-rail&subseccion=consultar&id=' . $id;
    }

    private function paginateConsistPage(array $page, array $query): array
    {
        $vin = mb_strtoupper(trim((string) ($query['vin_buscar'] ?? '')), 'UTF-8');
        $platform = trim((string) ($query['plataforma'] ?? ''));
        $incident = trim((string) ($query['incidencia'] ?? ''));
        $units = array_values(array_filter($page['units'], static function (array $unit) use ($vin, $platform, $incident): bool {
            if ($vin !== '' && !str_contains((string) $unit['vin'], $vin)) {
                return false;
            }
            if ($platform !== '' && (string) ($unit['platform_number'] ?? '') !== $platform) {
                return false;
            }
            if ($incident === 'si' && ($unit['issues_json'] ?? []) === []) {
                return false;
            }
            if ($incident === 'no' && ($unit['issues_json'] ?? []) !== []) {
                return false;
            }
            return true;
        }));
        $perPage = 25;
        $current = max(1, (int) ($query['pagina'] ?? 1));
        $pages = max(1, (int) ceil(count($units) / $perPage));
        $current = min($current, $pages);
        $page['all_platforms'] = array_values(array_unique(array_filter(array_map(
            static fn (array $unit): string => trim((string) ($unit['platform_number'] ?? '')),
            $page['units'],
        ), static fn (string $value): bool => $value !== '')));
        $page['units'] = array_slice($units, ($current - 1) * $perPage, $perPage);
        $page['pagination'] = ['page' => $current, 'pages' => $pages, 'total' => count($units), 'per_page' => $perPage];
        $page['filters'] = ['vin_buscar' => $vin, 'plataforma' => $platform, 'incidencia' => $incident];
        return $page;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers\Rail;

use App\Auth\AuthenticatedUser;
use App\Auth\Csrf;
use App\Core\Rendering\RenderAdapter;
use App\Core\Rendering\RenderContext;
use App\Services\ModuleNavigationBuilder;
use App\Services\Rail\Consist\ConsistAnalysisResultStore;
use App\Services\Rail\Consist\ConsistSpreadsheetPreviewer;
use App\Services\Rail\Consist\ConsistTemporaryUploadStore;
use App\Services\Rail\Consist\ConsistUploadValidator;
use App\Services\Rail\Consist\ConsistVinCrossAnalyzer;
use App\Services\Rail\Consist\ConsistVinExtractor;
use App\Support\AuthenticatedHeaderBuilder;
use App\Support\Rail\RailFlashStore;
use App\ViewModels\Rail\ConsistUploadViewModel;
use App\ViewModels\Rail\ConsistAnalysisViewModel;
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
    ) {
        $this->baseUrl = $baseUrl !== null
            ? rtrim($baseUrl, '/')
            : (defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '');
    }

    public function dispatch(string $method, array $query = [], array $post = [], array $files = []): ?string
    {
        if (strtoupper($method) !== 'POST') {
            return $this->render($query, $this->uploadViewModel($query));
        }

        [$section, $subsection] = $this->resolveLocation($query, $this->navigationConfiguration());
        if ($section !== 'consist-rail' || $subsection !== 'registrar') {
            return $this->render($query, $this->uploadViewModel($query));
        }
        $this->requireUploadDependencies();
        $redirect = $this->consistUploadUrl();
        $requestedAnalysisToken = trim((string) ($post['batch_token'] ?? ''));
        if (($post['action'] ?? '') === 'analyze_vin_cross' && preg_match('/^[a-f0-9]{64}$/', $requestedAnalysisToken)) {
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
            $validated = $this->uploadValidator->validateBatch($files);
            $staged = $this->temporaryStore->stageBatch($validated);
            $stagedToken = (string) $staged['token'];
            $preview = $this->spreadsheetPreviewer->previewBatch($staged['files']);
            $this->temporaryStore->savePreview($stagedToken, $preview);
            $this->csrf->rotate();
            $this->flashStore->add(
                $preview['valid'] ? 'success' : 'warning',
                $preview['valid']
                    ? 'Los tres archivos fueron cargados y validados.'
                    : 'La carga fue procesada, pero contiene errores que impiden continuar.',
            );
            $redirect .= '&preview=' . rawurlencode($stagedToken);
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
        [$moduleNavigation, $content] = $this->renderRailViews($section, $subsection, $navigation, $consistUpload);
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
    ): array
    {
        $railSection = $section;
        $railSubsection = $subsection;
        $railNavigation = $navigation;
        $railBaseUrl = $this->baseUrl;
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
                    $analysis = is_array($storedAnalysis) ? new ConsistAnalysisViewModel($storedAnalysis) : null;
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
            $extracted[$field] = $this->vinExtractor->extract($entry['files'][$field], $definition);
        }
        $result = $this->vinCrossAnalyzer->analyze(
            $extracted['vehicle_load_report'],
            $extracted['shippers'],
            $extracted['cnacs'],
        );
        $this->analysisResultStore->save($token, $result);
        $this->csrf->rotate();
        $this->flashStore->add('success', 'Cruce de VIN completado.');
        header('Location: ' . $redirect, true, 303);
        return null;
    }
}

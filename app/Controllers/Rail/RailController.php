<?php

declare(strict_types=1);

namespace App\Controllers\Rail;

use App\Auth\AuthenticatedUser;
use App\Core\Rendering\RenderAdapter;
use App\Core\Rendering\RenderContext;
use App\Services\ModuleNavigationBuilder;
use App\Support\AuthenticatedHeaderBuilder;
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
    ) {
        $this->baseUrl = $baseUrl !== null
            ? rtrim($baseUrl, '/')
            : (defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '');
    }

    public function render(array $query = []): string
    {
        $navigation = $this->navigationConfiguration();
        [$section, $subsection] = $this->resolveLocation($query, $navigation);
        [$moduleNavigation, $content] = $this->renderRailViews($section, $subsection, $navigation);
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
            additionalScripts: [],
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
        if (!is_array($navigation) || !isset($navigation['dashboard'])) {
            throw new RuntimeException('La navegación de Rail no está disponible.');
        }

        return $navigation;
    }

    private function resolveLocation(array $query, array $navigation): array
    {
        $section = isset($query['seccion']) && is_string($query['seccion'])
            ? trim($query['seccion'])
            : 'dashboard';
        if (!isset($navigation[$section]) || !is_array($navigation[$section])) {
            $section = 'dashboard';
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

    private function renderRailViews(string $section, string $subsection, array $navigation): array
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
}

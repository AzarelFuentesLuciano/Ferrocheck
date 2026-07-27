<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/DashboardService.php';

use App\Auth\AuthenticatedUser;
use App\Core\Rendering\Exceptions\RenderException;
use App\Core\Rendering\LegacyRenderBridge;
use App\Core\Rendering\RenderAdapter;
use App\Services\DashboardService;
use App\Services\ModuleNavigationBuilder;
use App\Support\AuthenticatedHeaderBuilder;
use Throwable;

class DashboardController
{
    /** Modo local y reversible; producción continúa en legacy por defecto. */
    private const RENDER_MODE = 'app_shell';
    private const ALLOWED_RENDER_MODES = ['legacy', 'app_shell'];
    private const FERROCHECK_APP_SHELL_ENABLED = true;
    private const FERROCHECK_MODULE = 'ferrocheck';
    private const FERROCHECK_SECTIONS = [
        'dashboard',
        'consulta-vin',
        'importar-excel',
        'busqueda-multiple',
        'configuracion',
    ];
    private ?AuthenticatedUser $authenticatedUser = null;
    private string $logoutCsrf = '';
    private ?ModuleNavigationBuilder $moduleNavigationBuilder = null;

    public function setAuthenticatedUser(
        AuthenticatedUser $user,
        string $logoutCsrf,
        ?ModuleNavigationBuilder $moduleNavigationBuilder = null
    ): void
    {
        $this->authenticatedUser = $user;
        $this->logoutCsrf = $logoutCsrf;
        $this->moduleNavigationBuilder = $moduleNavigationBuilder;
    }

    public function index(): void
    {
        $modulo = trim((string) ($_GET['modulo'] ?? 'dashboard'));
        $modulo = $modulo !== '' ? $modulo : 'dashboard';
        $seccion = trim((string) ($_GET['seccion'] ?? 'consulta-vin'));

        if (!$this->shouldRenderFerroCheckWithAppShell($modulo, $seccion)) {
            $this->renderLegacy();
            return;
        }

        try {
            $html = $this->renderAppShell($seccion);
        } catch (RenderException) {
            $this->renderLegacy();
            return;
        }

        echo $html;
        return;
    }

    public function resumenTarjetas(): void
    {
        header('Content-Type: application/json');

        try {
            $service = new DashboardService();
            $data = $service->obtenerResumenTarjetas();

            echo json_encode([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function resolveRenderMode(): string
    {
        return in_array(self::RENDER_MODE, self::ALLOWED_RENDER_MODES, true)
            ? self::RENDER_MODE
            : 'legacy';
    }

    private function isFerroCheckRequest(string $modulo, string $seccion): bool
    {
        return $modulo === self::FERROCHECK_MODULE
            && in_array($seccion, self::FERROCHECK_SECTIONS, true);
    }

    private function shouldRenderFerroCheckWithAppShell(string $modulo, string $seccion): bool
    {
        return self::FERROCHECK_APP_SHELL_ENABLED
            && $this->resolveRenderMode() === 'app_shell'
            && $this->isFerroCheckRequest($modulo, $seccion);
    }

    private function renderLegacy(): void
    {
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $requestedModule = trim((string) ($_GET['modulo'] ?? 'dashboard'));
        $modules = $this->moduleNavigationBuilder?->build($baseUrl) ?? [];
        $activeModule = $requestedModule === 'ferrocheck'
            ? 'rail'
            : str_replace('_', '-', $requestedModule);
        $activeSection = $requestedModule === 'ferrocheck' ? 'ferrocheck' : '';
        $headerMetadata = [
            'systemName' => 'VASCOR OPS',
            'systemSubtitle' => 'Plataforma Operativa',
            'versionLabel' => 'Versión v1.0',
            'menuLabel' => 'Abrir menú',
            'legacyHooks' => true,
        ];
        $header = $this->authenticatedUser instanceof AuthenticatedUser
            ? AuthenticatedHeaderBuilder::build(
                $this->authenticatedUser,
                $baseUrl . '/index.php?modulo=auth&accion=logout',
                $this->logoutCsrf,
                $headerMetadata,
            )
            : $headerMetadata;

        require __DIR__ . '/../Views/inventario/importar.php';
    }

    private function renderAppShell(string $ferroSeccion): string
    {
        $contenidoModulo = $this->renderFerroCheckContent($ferroSeccion);
        $railModuleNavigation = $this->renderRailNavigationForFerroCheck();
        $legacy = $this->buildLegacyRenderData($contenidoModulo, $ferroSeccion, $railModuleNavigation);

        $context = (new LegacyRenderBridge())->createContext($legacy);

        return (new RenderAdapter())->render($context);
    }

    private function renderRailNavigationForFerroCheck(): string
    {
        $railNavigation = require dirname(__DIR__, 2) . '/config/rail-navigation.php';
        $railSection = 'ferrocheck';
        $railSubsection = '';
        $railSubsections = [];
        $railSectionConfig = is_array($railNavigation[$railSection] ?? null)
            ? $railNavigation[$railSection]
            : [];
        $railBaseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';
        $railEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

        ob_start();
        require __DIR__ . '/../Views/rail/partials/navigation.php';

        return (string) ob_get_clean();
    }

    private function renderFerroCheckContent(string $ferroSeccion): string
    {
        $initialLevel = ob_get_level();

        try {
            ob_start();
            require __DIR__ . '/../Views/inventario/partials/ferrocheck-content.php';
            $contenidoModulo = ob_get_clean();

            if (!is_string($contenidoModulo) || trim($contenidoModulo) === '') {
                throw new RenderException('FerroCheck content could not be rendered.');
            }

            return $contenidoModulo;
        } catch (Throwable $exception) {
            while (ob_get_level() > $initialLevel) {
                ob_end_clean();
            }

            if ($exception instanceof RenderException) {
                throw $exception;
            }

            throw new RenderException('FerroCheck content could not be rendered.', 0, $exception);
        }
    }

    private function buildLegacyRenderData(
        string $contenidoModulo,
        string $ferroSeccion,
        string $railModuleNavigation = ''
    ): array
    {
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';

        $headerMetadata = [
            'systemName' => 'VASCOR OPS',
            'systemSubtitle' => 'Plataforma Operativa',
            'versionLabel' => 'Versión v1.0',
            'menuLabel' => 'Abrir navegación',
        ];
        $header = $this->authenticatedUser instanceof AuthenticatedUser
            ? AuthenticatedHeaderBuilder::build(
                $this->authenticatedUser,
                $baseUrl . '/index.php?modulo=auth&accion=logout',
                $this->logoutCsrf,
                $headerMetadata,
            )
            : $headerMetadata;

        return [
            'pageTitle' => 'VASCOR OPS | FerroCheck',
            'documentLanguage' => 'es',
            'baseUrl' => $baseUrl,
            'modulo' => 'rail',
            'seccion' => 'ferrocheck',
            'modules' => $this->moduleNavigationBuilder?->build($baseUrl) ?? [],
            'moduleNavigation' => $railModuleNavigation,
            'contenidoModulo' => $contenidoModulo,
            'additionalStyles' => [
                $baseUrl . '/assets/css/rail/rail.css',
                $baseUrl . '/assets/css/importador.css',
            ],
            'additionalScripts' => [
                $baseUrl . '/assets/js/importador.js',
            ],
            'header' => $header,
            'footer' => [
                'title' => 'VASCOR OPS v1.0',
                'subtitle' => 'Plataforma Operativa',
                'creditLabel' => 'Desarrollado por',
                'developer' => 'Ing. Azarel Fuentes Luciano',
                'year' => '2026',
            ],
            'sidebarLabel' => 'Módulos principales',
        ];
    }
}

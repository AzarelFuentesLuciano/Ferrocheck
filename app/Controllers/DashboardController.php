<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/DashboardService.php';

use App\Core\Rendering\Exceptions\RenderException;
use App\Core\Rendering\LegacyRenderBridge;
use App\Core\Rendering\RenderAdapter;
use App\Services\DashboardService;
use Throwable;

class DashboardController
{
    /** Modo local y reversible; producción continúa en legacy por defecto. */
    private const RENDER_MODE = 'legacy';
    private const ALLOWED_RENDER_MODES = ['legacy', 'app_shell'];
    private const FERROCHECK_APP_SHELL_ENABLED = false;
    private const FERROCHECK_MODULE = 'ferrocheck';
    private const FERROCHECK_SECTIONS = [
        'dashboard',
        'consulta-vin',
        'importar-excel',
        'busqueda-multiple',
        'configuracion',
    ];

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
        require __DIR__ . '/../Views/inventario/importar.php';
    }

    private function renderAppShell(string $ferroSeccion): string
    {
        $contenidoModulo = $this->renderFerroCheckContent($ferroSeccion);
        $legacy = $this->buildLegacyRenderData($contenidoModulo, $ferroSeccion);

        $context = (new LegacyRenderBridge())->createContext($legacy);

        return (new RenderAdapter())->render($context);
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

    private function buildLegacyRenderData(string $contenidoModulo, string $ferroSeccion): array
    {
        $baseUrl = defined('BASE_URL') ? rtrim((string) BASE_URL, '/') : '';

        return [
            'pageTitle' => 'VASCOR OPS | FerroCheck',
            'documentLanguage' => 'es',
            'baseUrl' => $baseUrl,
            'modulo' => 'ferrocheck',
            'seccion' => $ferroSeccion,
            'modules' => [
                [
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'url' => $baseUrl . '/index.php?modulo=dashboard',
                    'icon' => '🏠',
                ],
                [
                    'id' => 'ferrocheck',
                    'label' => 'FerroCheck',
                    'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=dashboard',
                    'icon' => '🚂',
                    'sections' => [
                        ['id' => 'dashboard', 'label' => 'Dashboard', 'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=dashboard'],
                        ['id' => 'consulta-vin', 'label' => 'Consulta VIN', 'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=consulta-vin'],
                        ['id' => 'importar-excel', 'label' => 'Importar Excel', 'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=importar-excel'],
                        ['id' => 'busqueda-multiple', 'label' => 'Búsqueda múltiple', 'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=busqueda-multiple'],
                        ['id' => 'configuracion', 'label' => 'Configuración', 'url' => $baseUrl . '/index.php?modulo=ferrocheck&seccion=configuracion'],
                    ],
                ],
                [
                    'id' => 'inventario-material',
                    'label' => 'Inventario de Material',
                    'url' => $baseUrl . '/index.php?modulo=inventario-material',
                    'icon' => '📦',
                ],
                [
                    'id' => 'operaciones-patio',
                    'label' => 'Inventario de Patio',
                    'url' => $baseUrl . '/index.php?modulo=operaciones-patio',
                    'icon' => '🚛',
                ],
                [
                    'id' => 'control-escaneres',
                    'label' => 'Control de Escáneres',
                    'url' => $baseUrl . '/index.php?modulo=control-escaneres',
                    'icon' => '📡',
                ],
                [
                    'id' => 'reportes',
                    'label' => 'Reportes',
                    'url' => $baseUrl . '/index.php?modulo=reportes',
                    'icon' => '📊',
                ],
                [
                    'id' => 'administracion',
                    'label' => 'Administración',
                    'url' => $baseUrl . '/index.php?modulo=administracion',
                    'icon' => '👤',
                ],
                [
                    'id' => 'configuracion-general',
                    'label' => 'Configuración General',
                    'url' => $baseUrl . '/index.php?modulo=configuracion-general',
                    'icon' => '⚙',
                ],
            ],
            'moduleNavigation' => '',
            'contenidoModulo' => $contenidoModulo,
            'additionalStyles' => [
                $baseUrl . '/assets/css/importador.css',
            ],
            'additionalScripts' => [
                $baseUrl . '/assets/js/importador.js',
            ],
            'header' => [
                'systemName' => 'VASCOR OPS',
                'systemSubtitle' => 'Plataforma Operativa',
                'versionLabel' => 'Versión v1.0',
                'menuLabel' => 'Abrir navegación',
            ],
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

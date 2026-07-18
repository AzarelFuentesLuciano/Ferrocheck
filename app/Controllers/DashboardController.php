<?php

namespace App\Controllers;

require_once __DIR__ . '/../Services/DashboardService.php';

use App\Core\Rendering\Exceptions\RenderException;
use App\Core\Rendering\LegacyRenderBridge;
use App\Core\Rendering\RenderAdapter;
use App\Services\DashboardService;

class DashboardController
{
    /** Modo local y reversible; producción continúa en legacy por defecto. */
    private const RENDER_MODE = 'legacy';
    private const ALLOWED_RENDER_MODES = ['legacy', 'app_shell'];

    public function index(): void
    {
        $renderMode = $this->resolveRenderMode();
        if ($renderMode === 'legacy') {
            $this->renderLegacy();
            return;
        }

        try {
            $html = $this->renderAppShell();
        } catch (RenderException) {
            $this->renderLegacy();
            return;
        }

        echo $html;
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

    private function renderLegacy(): void
    {
        require __DIR__ . '/../Views/inventario/importar.php';
    }

    private function renderAppShell(): string
    {
        $legacy = $this->buildLegacyRenderData();

        // Bloqueo seguro: todavía no existe una vista reutilizable sin shell.
        if ($legacy['contenidoModulo'] === '') {
            throw new RenderException('App Shell content is not available.');
        }

        $context = (new LegacyRenderBridge())->createContext($legacy);

        return (new RenderAdapter())->render($context);
    }

    private function buildLegacyRenderData(): array
    {
        return [
            'pageTitle' => 'VASCOR OPS',
            'documentLanguage' => 'es',
            'baseUrl' => defined('BASE_URL') ? BASE_URL : '',
            'modulo' => 'dashboard',
            'seccion' => '',
            'modules' => [],
            'moduleNavigation' => '',
            'contenidoModulo' => '',
            'additionalStyles' => [],
            'additionalScripts' => [],
            'header' => [],
            'footer' => [],
            'sidebarLabel' => 'Módulos principales',
        ];
    }
}

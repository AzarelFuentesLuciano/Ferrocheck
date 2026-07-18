<?php

declare(strict_types=1);

namespace App\Core\Rendering;

use App\Core\Rendering\Exceptions\RenderException;
use Throwable;

final class RenderAdapter
{
    private readonly string $layoutPath;
    private readonly string $layoutDirectory;

    public function __construct(?string $layoutPath = null)
    {
        $layoutDirectory = realpath(__DIR__ . '/../../Views/layouts');
        if ($layoutDirectory === false || !is_dir($layoutDirectory)) {
            throw new RenderException('The application layout directory is unavailable.');
        }

        $this->layoutDirectory = $layoutDirectory;
        $this->layoutPath = $layoutPath ?? $layoutDirectory . DIRECTORY_SEPARATOR . 'app.php';
    }

    public function render(RenderContext $context): string
    {
        $layoutPath = $this->resolveLayoutPath();
        $variables = $context->toArray();
        $initialBufferLevel = ob_get_level();

        try {
            $pageTitle = $variables['pageTitle'];
            $documentLanguage = $variables['documentLanguage'];
            $assetBaseUrl = $variables['assetBaseUrl'];
            $activeModule = $variables['activeModule'];
            $activeSection = $variables['activeSection'];
            $modules = $variables['modules'];
            $moduleNavigation = $variables['moduleNavigation'];
            $content = $variables['content'];
            $additionalStyles = $variables['additionalStyles'];
            $additionalScripts = $variables['additionalScripts'];
            $header = $variables['header'];
            $footer = $variables['footer'];
            $sidebarLabel = $variables['sidebarLabel'];

            ob_start();
            require $layoutPath;
            $html = ob_get_clean();

            if (!is_string($html)) {
                throw new RenderException('The application layout could not be rendered.');
            }

            return $html;
        } catch (Throwable $exception) {
            while (ob_get_level() > $initialBufferLevel) {
                ob_end_clean();
            }

            if ($exception instanceof RenderException) {
                throw $exception;
            }

            throw new RenderException('The application layout could not be rendered.', 0, $exception);
        }
    }

    private function resolveLayoutPath(): string
    {
        $candidateDirectory = realpath(dirname($this->layoutPath));
        if ($candidateDirectory === false || $candidateDirectory !== $this->layoutDirectory) {
            throw new RenderException('The application layout is unavailable.');
        }

        $resolvedPath = realpath($this->layoutPath);
        if ($resolvedPath === false || !is_file($resolvedPath) || dirname($resolvedPath) !== $this->layoutDirectory) {
            throw new RenderException('The application layout is unavailable.');
        }

        return $resolvedPath;
    }
}

<?php

declare(strict_types=1);

namespace App\Core\Rendering;

use App\Core\Rendering\Exceptions\RenderException;
use InvalidArgumentException;

final class LegacyRenderBridge
{
    public function createContext(array $legacy): RenderContext
    {
        $this->rejectSuperglobals($legacy);

        try {
            return new RenderContext(
                pageTitle: $this->stringValue($legacy, ['pageTitle', 'tituloPagina'], 'VASCOR OPS'),
                documentLanguage: $this->stringValue($legacy, ['documentLanguage'], 'es'),
                assetBaseUrl: $this->stringValue($legacy, ['baseUrl', 'BASE_URL'], ''),
                activeModule: $this->stringValue($legacy, ['modulo'], 'dashboard'),
                activeSection: $this->stringValue($legacy, ['seccion'], ''),
                modules: $this->arrayValue($legacy, 'modules'),
                moduleNavigation: $this->stringValue($legacy, ['moduleNavigation'], ''),
                content: $this->stringValue($legacy, ['contenidoModulo'], ''),
                additionalStyles: $this->arrayValue($legacy, 'additionalStyles'),
                additionalScripts: $this->arrayValue($legacy, 'additionalScripts'),
                header: $this->arrayValue($legacy, 'header'),
                footer: $this->arrayValue($legacy, 'footer'),
                sidebarLabel: $this->stringValue($legacy, ['sidebarLabel'], 'Módulos principales')
            );
        } catch (InvalidArgumentException $exception) {
            throw new RenderException(
                'The legacy render context is invalid: ' . $exception->getMessage(),
                0,
                $exception
            );
        }
    }

    private function stringValue(array $legacy, array $keys, string $default): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $legacy)) {
                continue;
            }

            if (!is_string($legacy[$key])) {
                throw new RenderException('Legacy field ' . $key . ' must be a string.');
            }

            return trim($legacy[$key]);
        }

        return $default;
    }

    private function arrayValue(array $legacy, string $key): array
    {
        if (!array_key_exists($key, $legacy)) {
            return [];
        }

        if (!is_array($legacy[$key])) {
            throw new RenderException('Legacy field ' . $key . ' must be an array.');
        }

        return $legacy[$key];
    }

    private function rejectSuperglobals(array $legacy): void
    {
        foreach (['_GET', '_POST', '_SESSION', '_FILES', '_COOKIE', '_SERVER', 'GLOBALS'] as $key) {
            if (array_key_exists($key, $legacy)) {
                throw new RenderException('Legacy superglobal input is not accepted.');
            }
        }
    }
}

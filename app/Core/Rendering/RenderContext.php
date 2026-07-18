<?php

declare(strict_types=1);

namespace App\Core\Rendering;

use InvalidArgumentException;

final class RenderContext
{
    private readonly string $pageTitle;
    private readonly string $documentLanguage;
    private readonly string $assetBaseUrl;
    private readonly string $activeModule;
    private readonly string $activeSection;
    private readonly array $modules;
    private readonly string $moduleNavigation;
    private readonly string $content;
    private readonly array $additionalStyles;
    private readonly array $additionalScripts;
    private readonly array $header;
    private readonly array $footer;
    private readonly string $sidebarLabel;

    public function __construct(
        string $pageTitle = 'VASCOR OPS',
        string $documentLanguage = 'es',
        string $assetBaseUrl = '',
        string $activeModule = 'dashboard',
        string $activeSection = '',
        array $modules = [],
        string $moduleNavigation = '',
        string $content = '',
        array $additionalStyles = [],
        array $additionalScripts = [],
        array $header = [],
        array $footer = [],
        string $sidebarLabel = 'Módulos principales'
    ) {
        $this->pageTitle = self::nonEmptyText($pageTitle, 'pageTitle');
        $this->documentLanguage = self::language($documentLanguage);
        $this->assetBaseUrl = self::assetBaseUrl($assetBaseUrl);
        $this->activeModule = self::identifier($activeModule, 'activeModule', false);
        $this->activeSection = self::identifier($activeSection, 'activeSection', true);
        $this->modules = array_values($modules);
        $this->moduleNavigation = $moduleNavigation;
        $this->content = $content;
        $this->additionalStyles = self::assetList($additionalStyles, 'additionalStyles');
        $this->additionalScripts = self::assetList($additionalScripts, 'additionalScripts');
        $this->header = $header;
        $this->footer = $footer;
        $this->sidebarLabel = self::nonEmptyText($sidebarLabel, 'sidebarLabel');
    }

    public function toArray(): array
    {
        return [
            'pageTitle' => $this->pageTitle,
            'documentLanguage' => $this->documentLanguage,
            'assetBaseUrl' => $this->assetBaseUrl,
            'activeModule' => $this->activeModule,
            'activeSection' => $this->activeSection,
            'modules' => $this->modules,
            'moduleNavigation' => $this->moduleNavigation,
            'content' => $this->content,
            'additionalStyles' => $this->additionalStyles,
            'additionalScripts' => $this->additionalScripts,
            'header' => $this->header,
            'footer' => $this->footer,
            'sidebarLabel' => $this->sidebarLabel,
        ];
    }

    private static function nonEmptyText(string $value, string $field): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException($field . ' cannot be empty.');
        }

        return $value;
    }

    private static function language(string $value): string
    {
        $value = trim($value);
        if (preg_match('/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/D', $value) !== 1) {
            throw new InvalidArgumentException('documentLanguage has an invalid format.');
        }

        return $value;
    }

    private static function identifier(string $value, string $field, bool $allowEmpty): string
    {
        $value = strtolower(trim($value));
        if ($allowEmpty && $value === '') {
            return '';
        }

        if (preg_match('/^[a-z0-9][a-z0-9_-]*$/D', $value) !== 1) {
            throw new InvalidArgumentException($field . ' has an invalid format.');
        }

        return $value;
    }

    private static function assetBaseUrl(string $value): string
    {
        $value = rtrim(trim($value), '/');
        if ($value === '') {
            return '';
        }

        self::assertSafeUrl($value, 'assetBaseUrl');
        if (str_contains($value, '?') || str_contains($value, '#')) {
            throw new InvalidArgumentException('assetBaseUrl cannot contain a query or fragment.');
        }

        return $value;
    }

    private static function assetList(array $assets, string $field): array
    {
        $result = [];
        $seen = [];

        foreach ($assets as $asset) {
            if (!is_string($asset) || trim($asset) === '') {
                throw new InvalidArgumentException($field . ' must contain non-empty strings.');
            }

            $asset = trim($asset);
            self::assertSafeUrl($asset, $field);
            if (!isset($seen[$asset])) {
                $seen[$asset] = true;
                $result[] = $asset;
            }
        }

        return $result;
    }

    private static function assertSafeUrl(string $value, string $field): void
    {
        if (preg_match('/[\x00-\x20\x7F]/', $value) === 1 || str_contains($value, '\\')) {
            throw new InvalidArgumentException($field . ' contains an invalid URL.');
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);
        if (is_string($scheme) && !in_array(strtolower($scheme), ['http', 'https'], true)) {
            throw new InvalidArgumentException($field . ' contains a disallowed URL scheme.');
        }

        $path = (string) (parse_url($value, PHP_URL_PATH) ?? '');
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '..') {
                throw new InvalidArgumentException($field . ' cannot traverse directories.');
            }
        }
    }
}

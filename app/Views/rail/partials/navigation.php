<?php

declare(strict_types=1);

$railNavigationItems = is_array($railNavigation ?? null) ? $railNavigation : [];
$railContextItems = is_array($railSubsections ?? null) ? $railSubsections : [];
$railUrl = static function (string $section, string $subsection) use ($railBaseUrl): string {
    $query = http_build_query([
        'modulo' => 'rail',
        'seccion' => $section,
        'subseccion' => $subsection,
    ], '', '&', PHP_QUERY_RFC3986);

    return $railBaseUrl . '/index.php?' . $query;
};
$railConfiguredUrl = static function (array $item, string $section, string $subsection) use ($railBaseUrl, $railUrl): string {
    $explicitUrl = trim((string) ($item['url'] ?? ''));
    if ($explicitUrl !== '') {
        return $railBaseUrl . '/' . ltrim($explicitUrl, '/');
    }

    return $railUrl($section, $subsection);
};
?>
<header class="rail-section-header rail-module-header">
    <span class="rail-section-header__eyebrow">Módulo</span>
    <h1>Rail</h1>
    <p>Operaciones y servicios ferroviarios de VASCOR OPS.</p>
</header>
<div class="rail-navigation">
    <nav class="rail-primary-nav" aria-label="Secciones principales de Rail">
        <?php foreach ($railNavigationItems as $railNavigationKey => $railNavigationItem): ?>
            <?php
            if (!is_string($railNavigationKey) || !is_array($railNavigationItem)) {
                continue;
            }
            $railNavigationLabel = (string) ($railNavigationItem['label'] ?? $railNavigationKey);
            $railNavigationIcon = (string) ($railNavigationItem['icon'] ?? '');
            $railNavigationDefault = (string) ($railNavigationItem['default_subsection'] ?? '');
            $railNavigationActive = $railNavigationKey === $railSection;
            ?>
            <a class="rail-primary-nav__link<?php echo $railNavigationActive ? ' rail-primary-nav__link--current' : ''; ?>"
               href="<?php echo $railEscape($railConfiguredUrl($railNavigationItem, $railNavigationKey, $railNavigationDefault)); ?>"
               <?php echo $railNavigationActive ? 'aria-current="page"' : ''; ?>>
                <span class="rail-primary-nav__icon" aria-hidden="true"><?php echo $railEscape($railNavigationIcon); ?></span>
                <span><?php echo $railEscape($railNavigationLabel); ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <?php if ($railContextItems !== []): ?>
    <nav class="rail-context-nav" aria-label="Opciones de la sección <?php echo $railEscape((string) ($railSectionConfig['label'] ?? $railSection)); ?>">
        <?php foreach ($railContextItems as $railContextKey => $railContextItem): ?>
            <?php
            if (!is_string($railContextKey) || !is_array($railContextItem)) {
                continue;
            }
            $railContextLabel = (string) ($railContextItem['label'] ?? $railContextKey);
            $railContextActive = $railContextKey === $railSubsection;
            ?>
            <a class="rail-context-nav__link<?php echo $railContextActive ? ' rail-context-nav__link--current' : ''; ?>"
               href="<?php echo $railEscape($railUrl($railSection, $railContextKey)); ?>"
               <?php echo $railContextActive ? 'aria-current="page"' : ''; ?>>
                <?php echo $railEscape($railContextLabel); ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <?php endif; ?>
</div>

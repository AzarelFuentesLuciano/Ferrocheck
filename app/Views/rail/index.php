<?php

declare(strict_types=1);

$railEscape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$railViewAllowlist = [
    'ferrocheck' => __DIR__ . '/ferro.php',
    'consist-rail' => __DIR__ . '/consist-rail.php',
    'facturacion' => __DIR__ . '/facturacion.php',
    'inventario' => __DIR__ . '/inventario.php',
    'evidencias' => __DIR__ . '/evidencias.php',
    'configuracion' => __DIR__ . '/configuracion.php',
];

$railNavigation = isset($railNavigation) && is_array($railNavigation) ? $railNavigation : [];
$railSection = isset($railSection) && is_string($railSection) ? trim($railSection) : '';
$railSubsection = isset($railSubsection) && is_string($railSubsection) ? trim($railSubsection) : '';
$railBaseUrl = isset($railBaseUrl) && is_string($railBaseUrl) ? rtrim(trim($railBaseUrl), '/') : '';

if (!isset($railViewAllowlist[$railSection], $railNavigation[$railSection]) || !is_array($railNavigation[$railSection])) {
    $railSection = 'ferrocheck';
}

$railSectionConfig = isset($railNavigation[$railSection]) && is_array($railNavigation[$railSection])
    ? $railNavigation[$railSection]
    : [];
$railSubsections = isset($railSectionConfig['subsections']) && is_array($railSectionConfig['subsections'])
    ? $railSectionConfig['subsections']
    : [];
$railDefaultSubsection = isset($railSectionConfig['default_subsection']) && is_string($railSectionConfig['default_subsection'])
    ? $railSectionConfig['default_subsection']
    : '';

if (!isset($railSubsections[$railSubsection]) || !is_array($railSubsections[$railSubsection])) {
    $railSubsection = isset($railSubsections[$railDefaultSubsection]) ? $railDefaultSubsection : (string) array_key_first($railSubsections);
}

$railActiveSubsection = isset($railSubsections[$railSubsection]) && is_array($railSubsections[$railSubsection])
    ? $railSubsections[$railSubsection]
    : [];
$railActiveSubsectionLabel = (string) ($railActiveSubsection['label'] ?? $railSubsection);

ob_start();
require __DIR__ . '/partials/navigation.php';
$railModuleNavigation = (string) ob_get_clean();
?>
<section class="rail-module" aria-labelledby="railSectionTitle">
    <div class="rail-content">
        <?php require $railViewAllowlist[$railSection]; ?>
    </div>
</section>

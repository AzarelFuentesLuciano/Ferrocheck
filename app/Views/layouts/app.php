<?php

declare(strict_types=1);

/**
 * App Shell neutral de VASCOR OPS.
 *
 * Todo el contexto debe ser preparado por el caller. $content es HTML ya
 * renderizado y confiable; los valores visibles restantes se escapan aquí.
 */
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$pageTitle = isset($pageTitle) ? (string) $pageTitle : 'VASCOR OPS';
$documentLanguage = isset($documentLanguage) ? (string) $documentLanguage : 'es';
$assetBaseUrl = isset($assetBaseUrl) ? rtrim((string) $assetBaseUrl, '/') : '';
$activeModule = isset($activeModule) ? (string) $activeModule : '';
$activeSection = isset($activeSection) ? (string) $activeSection : '';
$modules = isset($modules) && is_array($modules) ? $modules : [];
$moduleNavigation = isset($moduleNavigation) ? (string) $moduleNavigation : '';
$content = isset($content) ? (string) $content : '';
$additionalStyles = isset($additionalStyles) && is_array($additionalStyles) ? $additionalStyles : [];
$additionalScripts = isset($additionalScripts) && is_array($additionalScripts) ? $additionalScripts : [];
$header = isset($header) && is_array($header) ? $header : [];
$footer = isset($footer) && is_array($footer) ? $footer : [];
?>
<!DOCTYPE html>
<html lang="<?php echo $escape($documentLanguage); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $escape($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $escape($assetBaseUrl . '/assets/css/vascor-design-system.css'); ?>">
    <link rel="stylesheet" href="<?php echo $escape($assetBaseUrl . '/assets/css/app-shell.css'); ?>">
    <?php foreach ($additionalStyles as $styleUrl): ?>
        <link rel="stylesheet" href="<?php echo $escape($styleUrl); ?>">
    <?php endforeach; ?>
</head>
<body class="app-shell-page">
    <div class="app-shell" data-app-shell>
        <div class="app-shell-backdrop" data-app-shell-backdrop aria-hidden="true"></div>

        <?php require __DIR__ . '/../partials/header.php'; ?>

        <div class="app-shell-body">
            <?php require __DIR__ . '/../partials/sidebar.php'; ?>

            <main class="app-main" id="appShellMain" tabindex="-1">
                <?php echo $moduleNavigation; ?>
                <?php echo $content; ?>
            </main>
        </div>

        <?php require __DIR__ . '/../partials/footer.php'; ?>
    </div>

    <script src="<?php echo $escape($assetBaseUrl . '/assets/js/app-shell.js'); ?>" defer></script>
    <?php foreach ($additionalScripts as $scriptUrl): ?>
        <script src="<?php echo $escape($scriptUrl); ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>

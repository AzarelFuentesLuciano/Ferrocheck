<?php

declare(strict_types=1);

$escape = isset($escape) && is_callable($escape)
    ? $escape
    : static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$modules = isset($modules) && is_array($modules) ? $modules : [];
$activeModule = (string) ($activeModule ?? '');
$activeSection = (string) ($activeSection ?? '');
?>
<aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegación lateral">
    <div class="sidebar__section">
        <?php foreach ($modules as $module): ?>
            <?php
            if (!is_array($module)) {
                continue;
            }
            $moduleId = (string) ($module['id'] ?? '');
            $moduleLabel = (string) ($module['label'] ?? $moduleId);
            $moduleUrl = (string) ($module['url'] ?? '#');
            $moduleIcon = (string) ($module['icon'] ?? '•');
            $moduleActive = $moduleId !== '' && $moduleId === $activeModule;
            ?>
            <a href="<?php echo $escape($moduleUrl); ?>"
               class="sidebar__item<?php echo $moduleActive ? ' active' : ''; ?>"
               data-label="<?php echo $escape($moduleLabel); ?>"
               <?php echo $moduleActive ? 'aria-current="page"' : ''; ?>>
                <span class="sidebar__icon"><?php echo $escape($moduleIcon); ?></span>
                <span class="sidebar__text"><?php echo $escape($moduleLabel); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</aside>

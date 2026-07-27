<?php

declare(strict_types=1);

$sidebarLabel = (string) ($sidebarLabel ?? 'Navegación principal');
?>
<aside class="app-sidebar" id="appShellSidebar" aria-label="<?php echo $escape($sidebarLabel); ?>" data-app-shell-sidebar>
    <nav class="app-sidebar-nav">
        <?php foreach ($modules as $module): ?>
            <?php
            if (!is_array($module)) {
                continue;
            }

            $moduleId = (string) ($module['id'] ?? '');
            $moduleLabel = (string) ($module['label'] ?? $moduleId);
            $moduleUrl = (string) ($module['url'] ?? '#');
            $moduleIcon = (string) ($module['icon'] ?? '•');
            $moduleActive = array_key_exists('active', $module)
                ? (bool) $module['active']
                : $moduleId !== '' && $moduleId === $activeModule;
            ?>
            <div class="app-sidebar-module<?php echo $moduleActive ? ' is-active' : ''; ?>">
                <div class="app-sidebar-module__row">
                    <a
                        class="app-sidebar-link<?php echo $moduleActive ? ' is-active' : ''; ?>"
                        href="<?php echo $escape($moduleUrl); ?>"
                        <?php echo $moduleActive ? 'aria-current="page"' : ''; ?>
                    >
                        <span class="app-sidebar-link__icon" aria-hidden="true"><?php echo $escape($moduleIcon); ?></span>
                        <span class="app-sidebar-link__label"><?php echo $escape($moduleLabel); ?></span>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

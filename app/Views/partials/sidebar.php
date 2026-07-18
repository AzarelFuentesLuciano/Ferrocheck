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
            $sections = isset($module['sections']) && is_array($module['sections']) ? $module['sections'] : [];
            $submenuId = 'appShellSubmenu-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $moduleId);
            $submenuOpen = $moduleActive && $sections !== [];
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

                    <?php if ($sections !== []): ?>
                        <button
                            class="app-sidebar-expand"
                            type="button"
                            aria-label="Mostrar secciones de <?php echo $escape($moduleLabel); ?>"
                            aria-expanded="<?php echo $submenuOpen ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo $escape($submenuId); ?>"
                            data-app-shell-submenu-toggle
                        >
                            <span aria-hidden="true">⌄</span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if ($sections !== []): ?>
                    <div
                        class="app-sidebar-submenu"
                        id="<?php echo $escape($submenuId); ?>"
                        data-app-shell-submenu
                        <?php echo $submenuOpen ? '' : 'hidden'; ?>
                    >
                        <?php foreach ($sections as $section): ?>
                            <?php
                            if (!is_array($section)) {
                                continue;
                            }
                            $sectionId = (string) ($section['id'] ?? '');
                            $sectionLabel = (string) ($section['label'] ?? $sectionId);
                            $sectionUrl = (string) ($section['url'] ?? '#');
                            $sectionActive = array_key_exists('active', $section)
                                ? (bool) $section['active']
                                : $moduleActive && $sectionId !== '' && $sectionId === $activeSection;
                            ?>
                            <a
                                class="app-sidebar-submenu__link<?php echo $sectionActive ? ' is-active' : ''; ?>"
                                href="<?php echo $escape($sectionUrl); ?>"
                                <?php echo $sectionActive ? 'aria-current="page"' : ''; ?>
                            ><?php echo $escape($sectionLabel); ?></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </nav>
</aside>

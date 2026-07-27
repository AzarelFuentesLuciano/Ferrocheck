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
            $sections = isset($module['sections']) && is_array($module['sections']) ? $module['sections'] : [];
            $submenuId = 'legacySubmenu-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $moduleId);
            ?>
            <?php if ($sections === []): ?>
                <a href="<?php echo $escape($moduleUrl); ?>"
                   class="sidebar__item<?php echo $moduleActive ? ' active' : ''; ?>"
                   data-label="<?php echo $escape($moduleLabel); ?>"
                   <?php echo $moduleActive ? 'aria-current="page"' : ''; ?>>
                    <span class="sidebar__icon"><?php echo $escape($moduleIcon); ?></span>
                    <span class="sidebar__text"><?php echo $escape($moduleLabel); ?></span>
                </a>
            <?php else: ?>
                <details class="sidebar-group<?php echo $moduleActive ? ' is-open' : ''; ?>" <?php echo $moduleActive ? 'open' : ''; ?>>
                    <summary class="sidebar__item sidebar__item--summary<?php echo $moduleActive ? ' active' : ''; ?>"
                             data-label="<?php echo $escape($moduleLabel); ?>"
                             aria-controls="<?php echo $escape($submenuId); ?>"
                             aria-expanded="<?php echo $moduleActive ? 'true' : 'false'; ?>">
                        <span class="sidebar__icon"><?php echo $escape($moduleIcon); ?></span>
                        <span class="sidebar__text"><?php echo $escape($moduleLabel); ?></span>
                    </summary>
                    <div class="sidebar-submenu" id="<?php echo $escape($submenuId); ?>">
                        <a href="<?php echo $escape($moduleUrl); ?>"
                           class="sidebar-submenu__item<?php echo $moduleActive && $activeSection === '' ? ' active' : ''; ?>">
                            <?php echo $escape($moduleLabel); ?>
                        </a>
                        <?php foreach ($sections as $section): ?>
                            <?php
                            if (!is_array($section)) {
                                continue;
                            }
                            $sectionId = (string) ($section['id'] ?? '');
                            $sectionLabel = (string) ($section['label'] ?? $sectionId);
                            $sectionUrl = (string) ($section['url'] ?? '#');
                            $sectionActive = $moduleActive && $sectionId !== '' && $sectionId === $activeSection;
                            ?>
                            <a href="<?php echo $escape($sectionUrl); ?>"
                               class="sidebar-submenu__item<?php echo $sectionActive ? ' active' : ''; ?>"
                               <?php echo $sectionActive ? 'aria-current="page"' : ''; ?>>
                                <?php echo $escape($sectionLabel); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </details>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</aside>

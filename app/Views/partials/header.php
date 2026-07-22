<?php

declare(strict_types=1);

$systemName = (string) ($header['systemName'] ?? 'VASCOR OPS');
$systemSubtitle = (string) ($header['systemSubtitle'] ?? 'Plataforma Operativa');
$versionLabel = (string) ($header['versionLabel'] ?? 'Versión v1.0');
$menuLabel = (string) ($header['menuLabel'] ?? 'Abrir navegación');
$currentUser = trim((string) ($header['currentUser'] ?? ''));
$currentRole = trim((string) ($header['currentRole'] ?? 'Usuario'));
$initials = $currentUser === '' ? '' : implode('',array_map(static fn(string$part):string=>mb_strtoupper(mb_substr($part,0,1)),array_slice(array_values(array_filter(preg_split('/\s+/u',$currentUser)?:[])),0,2)));
$logoutUrl = (string) ($header['logoutUrl'] ?? '');
$logoutCsrf = (string) ($header['logoutCsrf'] ?? '');
?>
<header class="app-header">
    <button
        class="app-header-menu"
        type="button"
        aria-label="<?php echo $escape($menuLabel); ?>"
        aria-expanded="false"
        aria-controls="appShellSidebar"
        data-app-shell-toggle
    >
        <span class="app-header-menu__icon" aria-hidden="true" data-app-shell-toggle-icon>☰</span>
    </button>

    <div class="app-header-brand">
        <div class="app-header-brand__mark" aria-hidden="true">
            <span class="app-header-brand__rail"></span>
            <span class="app-header-brand__rail app-header-brand__rail--secondary"></span>
            <span class="app-header-brand__core">VO</span>
        </div>
        <div class="app-header-brand__copy">
            <strong><?php echo $escape($systemName); ?></strong>
            <span><?php echo $escape($systemSubtitle); ?></span>
        </div>
    </div>

    <div class="app-header-meta" aria-live="polite">
        <span class="app-header-meta__version"><?php echo $escape($versionLabel); ?></span>
        <span class="app-header-meta__item"><small>Fecha</small><span data-app-shell-date>--</span></span>
        <span class="app-header-meta__item"><small>Hora</small><span data-app-shell-time>--:--:--</span></span>
        <?php if ($currentUser !== ''): ?>
            <span class="app-header-user"><span class="app-header-user__avatar" aria-hidden="true"><?php echo $escape($initials); ?></span><span class="app-header-user__identity"><strong><?php echo $escape($currentUser); ?></strong><small><?php echo $escape($currentRole); ?></small></span></span>
        <?php endif; ?>
        <?php if ($logoutUrl !== '' && $logoutCsrf !== ''): ?>
            <form method="post" action="<?php echo $escape($logoutUrl); ?>" class="app-header-logout">
                <input type="hidden" name="_csrf" value="<?php echo $escape($logoutCsrf); ?>">
                <button type="submit"><span aria-hidden="true">↪</span><span class="app-header-logout__label">Cerrar sesión</span></button>
            </form>
        <?php endif; ?>
    </div>
</header>

<?php declare(strict_types=1);$e=static fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Iniciar sesión | VASCOR OPS</title><link rel="stylesheet" href="<?= $e(BASE_URL) ?>/assets/css/vascor-design-system.css"><link rel="stylesheet" href="<?= $e(BASE_URL) ?>/assets/css/auth.css"><script src="<?= $e(BASE_URL) ?>/assets/js/auth.js" defer></script></head>
<body class="auth-page"><main>
 <section class="auth-viewport" aria-labelledby="login-title">
  <div class="auth-layout">
   <div class="auth-intro"><p class="auth-welcome" data-auth-welcome data-text="Bienvenido a VASCOR OPS">Bienvenido a VASCOR OPS</p><p class="auth-subtitle">Acceso seguro a la Plataforma Operativa.</p></div>
   <section class="auth-card"><div class="auth-brand"><strong>VASCOR OPS</strong><span>Plataforma Operativa</span></div><h1 id="login-title">Iniciar sesión</h1><?php if($message):?><p class="auth-error" role="alert"><?= $e($message) ?></p><?php endif?><form method="post" action="<?= $e(BASE_URL) ?>/index.php?modulo=auth"><input type="hidden" name="_csrf" value="<?= $e($csrfToken) ?>"><input type="hidden" name="return" value="<?= $e($returnUrl) ?>"><label>Usuario<input name="usuario" autocomplete="username" maxlength="80" required autofocus></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button type="submit">Iniciar sesión</button></form></section>
  </div>
  <a class="auth-scroll-cue" href="#auth-footer"><span aria-hidden="true">↓</span> Desplázate hacia abajo</a>
 </section>
 <section class="auth-footer-section" id="auth-footer" aria-label="Información institucional"><footer class="auth-footer"><strong>VASCOR OPS V1.0</strong><span>Plataforma Operativa</span><span class="auth-footer__label">DESARROLLADO POR</span><a href="https://azarelfuentesluciano.github.io/Azarel_Fuentes_Luciano/" target="_blank" rel="noopener noreferrer">Ing. Azarel Fuentes Luciano <span aria-hidden="true">↗</span></a><span>© 2026 VASCOR OPS. Todos los derechos reservados.</span></footer></section>
</main></body></html>

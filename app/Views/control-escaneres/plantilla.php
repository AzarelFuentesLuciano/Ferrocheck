<?php
if (!isset($contenidoModulo)) {
    $seccion = trim((string) ($_GET['seccion'] ?? 'dashboard'));
    $vistas = [
        'dashboard' => 'dashboard.php', 'catalogo' => 'catalogo.php', 'expediente' => 'expediente.php',
        'entrega' => 'entrega.php', 'recepcion' => 'recepcion.php', 'incidencias' => 'incidencias.php',
        'mantenimiento' => 'mantenimiento.php', 'historial' => 'historial.php',
        'reportes' => 'reporte.php', 'reporte' => 'reporte.php', 'areas' => 'areas.php', 'importar-inventario' => 'importacion.php', 'registrar' => 'registrar.php', 'editar' => 'editar.php', 'baja' => 'estado-activo.php', 'reactivar' => 'estado-activo.php',
    ];
    require __DIR__ . '/' . ($vistas[$seccion] ?? $vistas['dashboard']);
    return;
}

$vistaActual = $vistaActual ?? 'dashboard';
$baseModulo = BASE_URL . '/index.php?modulo=control-escaneres&amp;seccion=';
$navegacion = [
    'dashboard' => ['Dashboard', '▦'], 'catalogo' => ['Catálogo', '▤'],
    'expediente' => ['Expediente', '▣'], 'entrega' => ['Entrega', '→'],
    'recepcion' => ['Recepción', '←'], 'historial' => ['Historial', '◷'],
    'reporte' => ['Reportes', '▥'],
];
$navegacion['incidencias'] = ['Incidencias', '!'];
$navegacion['mantenimiento'] = ['Mantenimiento', 'M'];
$navegacion['areas'] = ['Áreas', 'A'];
?>
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vascor-design-tokens.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/vascor-components.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/operations.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/history.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/dashboard.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/control-escaneres.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/media-fields.css">
<section class="ce-module" aria-label="Control de Escáneres">
    <header class="ce-hero">
        <div>
            <span class="ce-kicker">Operación logística</span>
            <h1>Control de Escáneres</h1>
            <p>Seguimiento operativo del equipo, desde su registro hasta su recepción y conservación.</p>
        </div>
        <div class="ce-hero__status"><span></span> Interfaz preparada</div>
    </header>
    <nav class="ce-nav" aria-label="Secciones de Control de Escáneres">
        <?php foreach ($navegacion as $clave => [$texto, $icono]): ?>
            <a class="ce-nav__item<?php echo $vistaActual === $clave ? ' is-active' : ''; ?>" href="<?php echo $baseModulo . $clave; ?>">
                <span aria-hidden="true"><?php echo $icono; ?></span><?php echo $texto; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="ce-content"><?php echo $contenidoModulo; ?></div>
</section>
<script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/operations-ui.js" defer></script>
<script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/equipment-finder.js" defer></script>
<script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/qr-scanner.js" defer></script>
<script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/signatures.js" defer></script>
<?php if ($vistaActual === 'dashboard'): ?><script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/dashboard.js" defer></script><?php endif; ?>

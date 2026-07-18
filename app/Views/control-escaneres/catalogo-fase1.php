<?php
require_once __DIR__ . '/../../../config/config.php';

$registros = [
    ['qr' => 'QR-ESC-001', 'id' => 'ESC-001', 'marca' => 'Zebra', 'modelo' => 'TC26', 'serie' => 'ZB-TC26-9A11', 'imei' => '351756051234561', 'telefono' => '8121001101', 'area' => 'Patio Norte', 'estado' => 'Disponible', 'actualizacion' => '2026-07-15 08:10'],
    ['qr' => 'QR-ESC-002', 'id' => 'ESC-002', 'marca' => 'Honeywell', 'modelo' => 'EDA52', 'serie' => 'HW-EDA52-1B90', 'imei' => '356938031234562', 'telefono' => '8121001102', 'area' => 'Patio Sur', 'estado' => 'Entregado', 'actualizacion' => '2026-07-15 08:25'],
    ['qr' => 'QR-ESC-003', 'id' => 'ESC-003', 'marca' => 'Urovo', 'modelo' => 'DT50', 'serie' => 'UR-DT50-7D20', 'imei' => '865421041234563', 'telefono' => '8121001103', 'area' => 'Almacen', 'estado' => 'Mantenimiento', 'actualizacion' => '2026-07-14 17:45'],
    ['qr' => 'QR-ESC-004', 'id' => 'ESC-004', 'marca' => 'Zebra', 'modelo' => 'TC21', 'serie' => 'ZB-TC21-0E14', 'imei' => '351756051234564', 'telefono' => '8121001104', 'area' => 'Taller', 'estado' => 'Pendiente de reparacion', 'actualizacion' => '2026-07-14 12:12'],
    ['qr' => 'QR-ESC-005', 'id' => 'ESC-005', 'marca' => 'Chainway', 'modelo' => 'C72', 'serie' => 'CW-C72-4R88', 'imei' => '869321051234565', 'telefono' => '8121001105', 'area' => 'Patio Norte', 'estado' => 'Baja definitiva', 'actualizacion' => '2026-07-13 15:00'],
    ['qr' => 'QR-ESC-006', 'id' => 'ESC-006', 'marca' => 'Point Mobile', 'modelo' => 'PM90', 'serie' => 'PM-PM90-6Y71', 'imei' => '867530091234566', 'telefono' => '8121001106', 'area' => 'Patio Sur', 'estado' => 'Extraviado', 'actualizacion' => '2026-07-12 09:30'],
    ['qr' => 'QR-ESC-007', 'id' => 'ESC-007', 'marca' => 'Honeywell', 'modelo' => 'CT40', 'serie' => 'HW-CT40-5N40', 'imei' => '356938031234567', 'telefono' => '8121001107', 'area' => 'Anden A', 'estado' => 'Disponible', 'actualizacion' => '2026-07-15 07:50'],
    ['qr' => 'QR-ESC-008', 'id' => 'ESC-008', 'marca' => 'Urovo', 'modelo' => 'RT40', 'serie' => 'UR-RT40-8P12', 'imei' => '865421041234568', 'telefono' => '8121001108', 'area' => 'Anden B', 'estado' => 'Entregado', 'actualizacion' => '2026-07-15 06:44'],
    ['qr' => 'QR-ESC-009', 'id' => 'ESC-009', 'marca' => 'Zebra', 'modelo' => 'MC33', 'serie' => 'ZB-MC33-2Q61', 'imei' => '351756051234569', 'telefono' => '8121001109', 'area' => 'Almacen', 'estado' => 'Disponible', 'actualizacion' => '2026-07-14 18:20'],
    ['qr' => 'QR-ESC-010', 'id' => 'ESC-010', 'marca' => 'Datalogic', 'modelo' => 'Memor 11', 'serie' => 'DL-M11-3K39', 'imei' => '869997071234570', 'telefono' => '8121001110', 'area' => 'Patio Norte', 'estado' => 'Mantenimiento', 'actualizacion' => '2026-07-13 11:09'],
    ['qr' => 'QR-ESC-011', 'id' => 'ESC-011', 'marca' => 'Point Mobile', 'modelo' => 'PM85', 'serie' => 'PM-PM85-7M44', 'imei' => '867530091234571', 'telefono' => '8121001111', 'area' => 'Taller', 'estado' => 'Pendiente de reparacion', 'actualizacion' => '2026-07-12 14:55'],
    ['qr' => 'QR-ESC-012', 'id' => 'ESC-012', 'marca' => 'Chainway', 'modelo' => 'C66', 'serie' => 'CW-C66-9V17', 'imei' => '869321051234572', 'telefono' => '8121001112', 'area' => 'Anden C', 'estado' => 'Entregado', 'actualizacion' => '2026-07-15 08:05'],
    ['qr' => 'QR-ESC-013', 'id' => 'ESC-013', 'marca' => 'Honeywell', 'modelo' => 'EDA61K', 'serie' => 'HW-EDA61K-2L10', 'imei' => '356938031234573', 'telefono' => '8121001113', 'area' => 'Patio Sur', 'estado' => 'Disponible', 'actualizacion' => '2026-07-14 10:18'],
    ['qr' => 'QR-ESC-014', 'id' => 'ESC-014', 'marca' => 'Urovo', 'modelo' => 'DT40', 'serie' => 'UR-DT40-5C28', 'imei' => '865421041234574', 'telefono' => '8121001114', 'area' => 'Almacen', 'estado' => 'Disponible', 'actualizacion' => '2026-07-15 07:41'],
    ['qr' => 'QR-ESC-015', 'id' => 'ESC-015', 'marca' => 'Zebra', 'modelo' => 'TC22', 'serie' => 'ZB-TC22-8W52', 'imei' => '351756051234575', 'telefono' => '8121001115', 'area' => 'Anden D', 'estado' => 'Entregado', 'actualizacion' => '2026-07-15 05:59'],
];

$estadoClass = static function (string $estado): string {
    $map = [
        'Disponible' => 'ce-badge--disponible',
        'Entregado' => 'ce-badge--entregado',
        'Mantenimiento' => 'ce-badge--mantenimiento',
        'Pendiente de reparacion' => 'ce-badge--pendiente',
        'Baja definitiva' => 'ce-badge--baja',
        'Extraviado' => 'ce-badge--extraviado',
    ];

    return $map[$estado] ?? 'ce-badge--disponible';
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VASCOR OPS | Control de Escaneres | Catalogo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/importador.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/control-escaneres/catalogo-fase1.css">
</head>
<body>
    <div class="dashboard-shell">
        <div class="sidebar-backdrop" aria-hidden="true"></div>

        <header class="topbar">
            <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false" aria-controls="sidebarNav">
                <span class="menu-toggle__icon">☰</span>
            </button>
            <div class="brand">
                <div class="brand-logo" aria-label="Logo VASCOR OPS">
                    <div class="brand-mark">
                        <span class="brand-mark__rail"></span>
                        <span class="brand-mark__rail brand-mark__rail--secondary"></span>
                        <span class="brand-mark__core">VO</span>
                    </div>
                </div>
                <div>
                    <h1>VASCOR OPS</h1>
                    <p>Plataforma Operativa</p>
                </div>
            </div>
            <div class="topbar-meta">
                <div class="info-panel" aria-live="polite">
                    <div class="info-panel__item info-panel__item--active">Version v1.0</div>
                    <div class="info-panel__item"><span class="meta-label">Fecha</span><span id="currentDate">--</span></div>
                    <div class="info-panel__item"><span class="meta-label">Hora</span><span id="currentTime">--:--:--</span></div>
                </div>
            </div>
        </header>

        <div class="dashboard-body">
            <aside class="sidebar" id="sidebarNav" data-collapsed="false" aria-label="Navegacion lateral">
                <div class="sidebar__section">
                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=dashboard" class="sidebar__item" data-label="Dashboard">
                        <span class="sidebar__icon">🏠</span>
                        <span class="sidebar__text">Dashboard</span>
                    </a>

                    <details class="sidebar-group">
                        <summary class="sidebar__item sidebar__item--summary" data-label="FerroCheck">
                            <span class="sidebar__icon">🚂</span>
                            <span class="sidebar__text">FerroCheck</span>
                        </summary>
                        <div class="sidebar-submenu">
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=consulta-vin" class="sidebar-submenu__item">Consulta VIN</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=importar-excel" class="sidebar-submenu__item">Importar Excel</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=busqueda-multiple" class="sidebar-submenu__item">Busqueda multiple</a>
                            <a href="<?php echo BASE_URL; ?>/index.php?modulo=ferrocheck&amp;seccion=configuracion" class="sidebar-submenu__item">Configuracion</a>
                        </div>
                    </details>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=inventario-material" class="sidebar__item" data-label="Inventario de Material">
                        <span class="sidebar__icon">📦</span>
                        <span class="sidebar__text">Inventario de Material</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=operaciones-patio" class="sidebar__item" data-label="Inventario de Patio">
                        <span class="sidebar__icon">🚛</span>
                        <span class="sidebar__text">Inventario de Patio</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=control-escaneres" class="sidebar__item active" data-label="Control de Escaneres">
                        <span class="sidebar__icon">📡</span>
                        <span class="sidebar__text">Control de Escaneres</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=reportes" class="sidebar__item" data-label="Reportes">
                        <span class="sidebar__icon">📊</span>
                        <span class="sidebar__text">Reportes</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=administracion" class="sidebar__item" data-label="Administracion">
                        <span class="sidebar__icon">👤</span>
                        <span class="sidebar__text">Administracion</span>
                    </a>

                    <a href="<?php echo BASE_URL; ?>/index.php?modulo=configuracion-general" class="sidebar__item" data-label="Configuracion General">
                        <span class="sidebar__icon">⚙</span>
                        <span class="sidebar__text">Configuracion General</span>
                    </a>
                </div>
            </aside>

            <main class="main-content ce-catalogo-main">
                <section class="panel-card fade-in module-page-header">
                    <p class="eyebrow">Control de Escaneres</p>
                    <h2>Catalogo de Escaneres</h2>
                    <p>Administracion y registro de los equipos disponibles.</p>
                </section>

                <section class="panel-card fade-in ce-toolbar">
                    <div class="ce-toolbar__actions">
                        <button type="button" class="btn btn-primary" id="ceNuevoEscanerBtn">+ Nuevo Escaner</button>
                        <button type="button" class="btn btn-secondary">Filtros</button>
                    </div>
                    <div class="ce-search">
                        <label for="ceSearch">Buscar por: ID, Serie, IMEI, Modelo, Area</label>
                        <input id="ceSearch" type="text" placeholder="Buscar escaner..." disabled>
                    </div>
                </section>

                <section class="result-panel fade-in ce-table-panel ce-desktop-table">
                    <div class="table-wrapper">
                        <table class="results-table">
                            <thead>
                                <tr>
                                    <th>QR</th>
                                    <th>ID</th>
                                    <th>Marca</th>
                                    <th>Modelo</th>
                                    <th>Numero de Serie</th>
                                    <th>IMEI</th>
                                    <th>Numero Telefonico</th>
                                    <th>Area Asignada</th>
                                    <th>Estado</th>
                                    <th>Ultima Actualizacion</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($registros as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['qr'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['marca'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['modelo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['serie'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['imei'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['telefono'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?php echo htmlspecialchars($item['area'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><span class="ce-badge <?php echo $estadoClass($item['estado']); ?>"><?php echo htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                        <td><?php echo htmlspecialchars($item['actualizacion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td>
                                            <div class="ce-actions">
                                                <button type="button" class="action-link" aria-label="Ver">👁</button>
                                                <button type="button" class="action-link" aria-label="Editar">✎</button>
                                                <button type="button" class="action-link" aria-label="Historial">🕘</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="ce-mobile-cards">
                    <?php foreach ($registros as $item): ?>
                        <article class="panel-card fade-in ce-card">
                            <div class="ce-card__head">
                                <p class="eyebrow"><?php echo htmlspecialchars($item['id'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <span class="ce-badge <?php echo $estadoClass($item['estado']); ?>"><?php echo htmlspecialchars($item['estado'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <h3><?php echo htmlspecialchars($item['marca'] . ' ' . $item['modelo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <div class="ce-card__grid">
                                <p><strong>QR:</strong> <?php echo htmlspecialchars($item['qr'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Serie:</strong> <?php echo htmlspecialchars($item['serie'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>IMEI:</strong> <?php echo htmlspecialchars($item['imei'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($item['telefono'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Area:</strong> <?php echo htmlspecialchars($item['area'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p><strong>Actualizacion:</strong> <?php echo htmlspecialchars($item['actualizacion'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="ce-actions ce-actions--mobile">
                                <button type="button" class="action-link" aria-label="Ver">👁 Ver</button>
                                <button type="button" class="action-link" aria-label="Editar">✎ Editar</button>
                                <button type="button" class="action-link" aria-label="Historial">🕘 Historial</button>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </section>
            </main>
        </div>

        <footer id="footer" class="footer">
            <div class="footer__content">
                <p class="footer__title">VASCOR OPS v1.0</p>
                <p class="footer__subtitle">Plataforma Operativa</p>
                <p class="footer__label">Desarrollado por</p>
                <p class="footer__developer">Ing. Azarel Fuentes Luciano</p>
                <p class="footer__copyright">© 2026 VASCOR OPS. Todos los derechos reservados.</p>
            </div>
        </footer>
    </div>

    <div class="ce-modal" id="ceModalNuevo" aria-hidden="true">
        <div class="ce-modal__overlay" data-close="1"></div>
        <div class="ce-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="ceModalTitulo">
            <div class="ce-modal__header">
                <div>
                    <p class="eyebrow">Catalogo de Escaneres</p>
                    <h3 id="ceModalTitulo">Nuevo Escaner</h3>
                </div>
                <button type="button" class="btn btn-secondary" data-close="1">Cancelar</button>
            </div>

            <form class="ce-form" action="#" method="post" novalidate>
                <div class="ce-form-grid">
                    <label class="ce-form-field">ID Interno<input type="text" placeholder="ESC-016"></label>
                    <label class="ce-form-field">Codigo QR<input type="text" placeholder="QR-ESC-016"></label>
                    <label class="ce-form-field">Marca<input type="text" placeholder="Marca"></label>
                    <label class="ce-form-field">Modelo<input type="text" placeholder="Modelo"></label>
                    <label class="ce-form-field">Numero de Serie<input type="text" placeholder="Serie"></label>
                    <label class="ce-form-field">IMEI<input type="text" placeholder="IMEI"></label>
                    <label class="ce-form-field">IMEI 2 (opcional)<input type="text" placeholder="IMEI 2"></label>
                    <label class="ce-form-field">Numero Telefonico<input type="text" placeholder="Telefono"></label>
                    <label class="ce-form-field">ICCID<input type="text" placeholder="ICCID"></label>
                    <label class="ce-form-field">Operador Telefonico<input type="text" placeholder="Operador"></label>
                    <label class="ce-form-field">Area Asignada<input type="text" placeholder="Area"></label>
                    <label class="ce-form-field">Estado
                        <select>
                            <option>Disponible</option>
                            <option>Entregado</option>
                            <option>Mantenimiento</option>
                            <option>Pendiente de reparacion</option>
                            <option>Baja definitiva</option>
                            <option>Extraviado</option>
                        </select>
                    </label>
                    <label class="ce-form-field">Fecha de Compra<input type="date"></label>
                    <label class="ce-form-field">Garantia<input type="text" placeholder="12 meses"></label>
                    <label class="ce-form-field ce-form-field--full">Observaciones<textarea rows="4" placeholder="Notas del equipo"></textarea></label>
                    <label class="ce-form-field ce-form-field--full">Fotografia del Equipo<input type="file" accept="image/*"></label>
                </div>
                <div class="actions actions-left ce-modal__actions">
                    <button type="button" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-close="1">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="<?php echo BASE_URL; ?>/assets/js/control-escaneres/catalogo-fase1.js"></script>
</body>
</html>

# Mapa de dependencias actual

## Flujo de carga

```text
public/index.php
├── config/config.php
├── vendor/autoload.php
├── controllers según campos POST
└── DashboardController::index()
    └── app/Views/inventario/importar.php
        ├── shell global (header, sidebar, main, footer)
        ├── vistas inline de Dashboard y FerroCheck
        └── Control de Escáneres
            └── app/Views/control-escaneres/plantilla.php
                └── vista seleccionada por seccion
```

## Clasificación de archivos

| Clasificación | Archivos / grupos | Motivo |
|---|---|---|
| GLOBAL | `public/index.php`, `config/config.php`, `app/Core/Database.php`, `vendor/autoload.php` | Entrada, configuración, conexión y autoload compartidos |
| GLOBAL | `app/Views/inventario/importar.php` | Contiene shell y decide módulos, aunque su ruta nominal sea inventario |
| GLOBAL | `public/assets/css/vascor-design-system.css` | Tokens y componentes visuales compartidos |
| GLOBAL | Parte de `public/assets/css/importador.css` | Define `body`, topbar, sidebar, layout, footer y componentes genéricos |
| FERROCHECK | `InventarioController`, `VerificadorController`, `DetallePlataformaController`, `ExportacionInventarioController` | Operaciones ferroviarias e inventario |
| FERROCHECK | `InventarioService`, `ExcelService`, `VerificadorService`, `DetallePlataformaService`, `DashboardService`, servicios de exportación | Lógica del módulo |
| FERROCHECK | `InventarioRepository.php`, tabla `inventario`, `config/catalogo_columnas.php` | Persistencia y mapeo |
| FERROCHECK | Parte de `importar.php`, `importador.css`, `importador.js` | Vistas y comportamiento mezclados con el shell |
| CONTROL_ESCANERES | `ControlEscaneresController.php` | Fachada del módulo/API |
| CONTROL_ESCANERES | `app/Services/ControlEscaneres/` | Catálogo e importación |
| CONTROL_ESCANERES | `app/Repositories/ControlEscaneres/`, tabla `scanners` | Persistencia propia |
| CONTROL_ESCANERES | `app/Views/control-escaneres/{plantilla,dashboard,catalogo,expediente,entrega,recepcion,historial,reporte}.php` | Vistas integradas |
| CONTROL_ESCANERES | `public/assets/css/control-escaneres/control-escaneres.css` | Estilos prefijados `.ce-` |
| CONTROL_ESCANERES | `public/control-escaneres-api.php` | Endpoint separado |
| DUPLICADO | `catalogo-fase1.php`, `catalogo-fase1.css`, `catalogo-fase1.js` | Implementación autónoma alternativa del catálogo y shell |
| DUPLICADO | Control de sidebar/reloj en `importador.js`, `control-escaneres.js`, `catalogo-fase1.js` | Misma infraestructura repetida |
| LEGADO | `php/importar.php` | Entrada histórica fuera del front controller actual; validar antes de retirar |
| PENDIENTE_DE_CLASIFICAR | `UsuarioRepository.php`, `FotografiaRepository.php` | No tienen integración completa visible en los dos módulos auditados |
| PENDIENTE_DE_CLASIFICAR | `app/Core/{Router,Request,Response,View,Controller}.php` | Estructuras vacías aún no conectadas |

## CSS cargado

Todas las páginas integradas cargan:

1. Google Fonts Poppins.
2. `/assets/css/importador.css`.
3. `/assets/css/vascor-design-system.css`.

Control de Escáneres agrega desde `plantilla.php`:

4. `/assets/css/control-escaneres/control-escaneres.css`.

`catalogo-fase1.php` carga su CSS alternativo y constituye una página autónoma.

### Clases globales críticas

`dashboard-shell`, `topbar`, `brand`, `dashboard-body`, `sidebar`, `sidebar__item`, `sidebar-submenu`, `main-content`, `panel-card`, `stat-card`, `btn`, `actions`, `result-panel`, `table-wrapper`, `footer`, `is-open`, `is-collapsed`, `active`.

Las clases de Control de Escáneres usan mayormente el prefijo `ce-`, lo que reduce colisiones. Las reglas sobre `body`, controles HTML y variables `:root` siguen siendo transversales.

## JavaScript cargado

- FerroCheck carga `/assets/js/importador.js`.
- Dashboard general y Control de Escáneres cargan `/assets/js/operaciones-patio.js` por la condición actual del layout.
- La plantilla integrada de Control de Escáneres no carga `control-escaneres.js`.
- `catalogo-fase1.php` carga `catalogo-fase1.js`.

### IDs y selectores consumidos por `importador.js`

- Importación: `fileInput`, `dropzone`, `fileInfo`, `fileName`, `fileSize`, `fileType`, `recordCount`, `fileStatus`, `importBtn`, `progressFill`, `progressPercent`, `statusMessage`.
- Consulta: `#verificacion`, `.verifier-textarea`, `.results-table tbody`, `exportExcelBtn`, `.action-link`.
- Shell: `currentDate`, `currentTime`, `.menu-toggle`, `.sidebar`, `.sidebar-backdrop`, `.sidebar__item`, `.sidebar-group`, `.sidebar-submenu__item`.
- Dashboard: `.stat-card`, `.counter`, `.status-banner__footer`, `.info-panel__item`.
- Modal creado dinámicamente: `detalleModalCerrar`, `detalleModalTitulo`, `detalleModalMeta`, `detalleModalContenido`.

## Variables PHP compartidas

- Globales/configuración: `BASE_URL`, constantes `DB_*`.
- Layout: `$modulo`, `$ferroSeccion`, `$esFerrocheck`, `$esModulo`, `$tituloPagina`.
- Control de Escáneres: `$contenidoModulo`, `$vistaActual`, `$baseModulo`, `$navegacion`.
- Contexto de operaciones de patio: `$contexto`.

## Endpoints consumidos

`importador.js` usa `fetch(window.location.href)` y diferencia acciones mediante `dashboard_stats`, `codigo_equipo`, `equipos`, `archivo` y `accion`. Esto lo acopla al orden de despacho de `public/index.php` y a la URL visible. El API de escáneres usa `/control-escaneres-api.php`, aunque las vistas actuales son principalmente visuales.

## Tablas

- FerroCheck: `inventario`.
- Control de Escáneres: `scanners`.
- Conexión compartida: `App\Core\Database` y constantes de `config/config.php`.

## Riesgos de acoplamiento de línea base

1. `importar.php` es simultáneamente layout, router visual y vista de varios módulos.
2. `importador.css` mezcla shell y estilos de negocio.
3. `importador.js` mezcla shell, importación, consulta, exportación y dashboard.
4. `DashboardController` renderiza indirectamente casi todas las páginas.
5. El router formal no gobierna las rutas actuales.
6. Existen implementaciones duplicadas del shell y catálogo.
7. Los endpoints dependen de nombres de campos POST y de su prioridad.
8. Cambiar IDs o clases globales puede romper comportamiento sin error PHP.

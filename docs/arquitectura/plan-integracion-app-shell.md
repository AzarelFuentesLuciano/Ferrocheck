# Plan de integración del App Shell

> Este documento no ejecuta la integración. Únicamente define el plan previo a la modificación del sistema activo.

## 1. Estado actual

- Rama de trabajo: `refactor/modularizacion-vascor-ops`.
- Commit base del análisis: `c23e1f4 fix: corregir desbordamiento móvil del App Shell`.
- Línea base: `114 PASS`, `0 FAIL`.
- El App Shell existe en paralelo en `app/Views/layouts/app.php`, sus partials y sus assets.
- La demo manual está aislada en `tests/manual/app-shell-demo.php`.
- Ninguna ruta activa requiere el nuevo layout o carga `app-shell.css`/`app-shell.js`.
- El documento activo sigue siendo `app/Views/inventario/importar.php`.
- No hay cambios de esquema ni migraciones requeridos para la integración.

## 2. Flujo de renderizado actual

### 2.1 Solicitudes HTML GET

```text
GET /public/index.php?modulo={modulo}&seccion={seccion}
  → public/index.php
  → caso especial modulo=operaciones-patio: OperacionPatioController::index()
  → resto de solicitudes GET: DashboardController::index()
  → require app/Views/inventario/importar.php
  → importar.php lee modulo/seccion directamente de $_GET
  → calcula módulo y sección activos
  → construye documento HTML, header, sidebar y footer legacy
  → selecciona el contenido con condicionales
  → para control-escaneres requiere plantilla.php
  → respuesta HTML completa
```

`routes/web.php` sólo declara `/ => DashboardController@index`; no participa en el despacho ejecutado por `public/index.php`. Las clases `App\Core\Router`, `Controller` y `View` son actualmente esqueletos y tampoco intervienen.

### 2.2 Despacho actual en `public/index.php`

El orden es significativo:

1. Inicia sesión y carga configuración/autoload.
2. `GET modulo=operaciones-patio` → `OperacionPatioController::index()`.
3. `POST dashboard_stats` → `DashboardController::resumenTarjetas()`.
4. `POST codigo_equipo` → `DetallePlataformaController::detalle()`.
5. `POST accion=exportar_xlsx` → `ExportacionInventarioController::exportar()`.
6. `POST equipos` → `VerificadorController::verificar()`.
7. `POST` con archivo → `InventarioController::importar()`.
8. Cualquier otro caso → `DashboardController::index()`.

La futura integración no debe cambiar este orden ni convertir respuestas JSON/XLSX en HTML.

### 2.3 Selección dentro de `importar.php`

- `$modulo`: `trim($_GET['modulo'] ?? 'dashboard')`; vacío vuelve a `dashboard`.
- `$ferroSeccion`: `trim($_GET['seccion'] ?? 'consulta-vin')`.
- `$esFerrocheck`: indica `modulo=ferrocheck`.
- `$esModulo`: closure de comparación con `$modulo`.
- `$tituloPagina`: deriva del módulo.
- El contenido se selecciona dentro de `<main class="main-content">`.
- FerroCheck selecciona `dashboard`, `consulta-vin`, `importar-excel`, `busqueda-multiple` o, por fallback, configuración.
- Control de Escáneres requiere `app/Views/control-escaneres/plantilla.php`.
- Módulos no implementados muestran estados vacíos; un módulo desconocido muestra “Vista no disponible”.

### 2.4 Control de Escáneres

`plantilla.php` tiene dos modos:

1. Sin `$contenidoModulo`: lee `$_GET['seccion']`, selecciona una vista y la requiere.
2. Con `$contenidoModulo`: usa `$vistaActual`, genera cabecera/navegación del módulo y emite el HTML capturado.

Las vistas `dashboard.php`, `catalogo.php`, `expediente.php`, `entrega.php`, `recepcion.php`, `historial.php` y `reporte.php` usan `ob_start()`, asignan `$contenidoModulo` y vuelven a requerir `plantilla.php`. Alias admitido: `reportes` y `reporte` apuntan a `reporte.php`; la navegación genera `seccion=reporte`.

### 2.5 Flujo POST relevante

| Entrada | Controlador | Respuesta | Escritura potencial |
|---|---|---|---|
| `dashboard_stats=1` | `DashboardController::resumenTarjetas()` | JSON | No esperada |
| `codigo_equipo` | `DetallePlataformaController::detalle()` | JSON | No esperada |
| `accion=exportar_xlsx` + `equipos_filtrados` | `ExportacionInventarioController::exportar()` | XLSX/JSON | No esperada en BD |
| `equipos` | `VerificadorController::verificar()` | JSON | Por verificar en servicio; tratar como lectura hasta confirmación |
| `archivo`, sin `accion=importar` | `InventarioController::importar()` | JSON de análisis | Procesa temporal; no debe persistir |
| `archivo` + `accion=importar` | `InventarioController::importar()` | JSON | **Sí escribe inventario** |

Todos los `fetch` de `importador.js` usan `window.location.href`; por ello deben conservarse URL, query string y reglas de despacho.

## 3. Estrategias evaluadas

### Estrategia A — Convertir `importar.php` en delegador del layout

- Archivos: `importar.php`, posiblemente `importador.js` y `plantilla.php`.
- Riesgo: alto; el archivo de 522 líneas mezcla documento, shell y todos los contenidos.
- Dificultad: media-alta.
- Rollback: revertir un archivo grande con alta probabilidad de conflicto.
- Duplicación: baja después del cambio.
- FerroCheck: impacto alto por residir directamente en el archivo.
- Control de Escáneres: impacto medio por el `require` embebido.
- Rutas/endpoints: no necesitan cambiar.
- Impacto visual: alto y simultáneo.
- Deuda: mantiene selección y preparación dentro de una vista.

### Estrategia B — El controlador renderiza el layout y `importar.php` se usa como contenido

- Archivos: `DashboardController.php`, `importar.php`, `importador.js` y layout/adapter.
- Riesgo: alto tal como está planteada: `importar.php` emite `doctype`, `head`, shell y scripts; no puede anidarse como contenido.
- Dificultad: alta porque exige transformar `importar.php` antes del primer uso.
- Rollback: razonable en controlador, débil en la transformación masiva de la vista.
- Duplicación: baja al final.
- Impacto FerroCheck/Control: alto durante la extracción.
- Rutas: sin impacto.
- Impacto visual: alto.
- Deuda: menor al final, pero el corte inicial es demasiado grande.

### Estrategia C — Adaptador de contrato

- Archivos: `DashboardController.php`, adaptador nuevo, vista de contenido nueva y `importador.js`.
- Riesgo: medio si se modifica el legacy; medio-bajo si `importar.php` permanece intacto.
- Dificultad: media.
- Rollback: alto; el controlador puede volver a requerir el legacy.
- Duplicación: temporal, explícita y acotada al contenido extraído.
- FerroCheck: impacto controlable con pruebas por sección.
- Control de Escáneres: sus vistas pueden reutilizarse por buffer sin modificarlas inicialmente.
- Rutas/endpoints: ninguno.
- Impacto visual: sólo estructura exterior; CSS del contenido se conserva.
- Deuda: adaptador y contenido duplicado deben retirarse en una fase posterior.

### Estrategia D — Strangler paralelo con conmutador local (recomendada)

Es una variante segura de C: `DashboardController::index()` conserva el `require importar.php` como fallback y delega, mediante una única decisión local, a un adaptador de renderizado. El adaptador prepara el contrato y requiere una vista de contenido paralela; no intenta insertar el documento legacy completo.

- Archivos futuros mínimos: controlador, adaptador nuevo, vista de contenido nueva, `importador.js` y pruebas de humo.
- Riesgo: medio-bajo al conservar intacto el camino legacy.
- Dificultad: media.
- Rollback: inmediato por cambio de una decisión/include o por `git revert`.
- Duplicación: sí, temporal, sólo para hacer el corte reversible.
- FerroCheck: se conserva el DOM funcional y se valida sección por sección.
- Control de Escáneres: reutiliza sus vistas actuales mediante captura de salida.
- Rutas/endpoints/parámetros: sin cambios.
- Impacto visual: reemplazo exclusivo del shell exterior.
- Deuda: retirar fallback y deduplicar contenido sólo después de estabilizar la integración.

## 4. Estrategia recomendada y punto exacto de integración

**Estrategia recomendada: D, adaptador de renderizado con fallback legacy.**

Punto exacto: `DashboardController::index()`, actualmente compuesto sólo por:

```php
require __DIR__ . '/../Views/inventario/importar.php';
```

Ese es el único punto común para los GET HTML atendidos por el dashboard y no interviene en los POST JSON/XLSX ni en `operaciones-patio`. La decisión futura debe ser local, explícita y fácil de revertir. No se recomienda una bandera vía GET pública; durante el despliegue puede ser una constante de configuración local con default seguro o, preferentemente, un cambio atómico del include respaldado por el commit anterior.

El adaptador deberá:

1. Normalizar `modulo`/`seccion` con exactamente los defaults actuales.
2. Preparar navegación, títulos y assets.
3. Capturar exclusivamente el contenido de la vista paralela.
4. Requerir `app/Views/layouts/app.php` una sola vez.
5. No interceptar ni reinterpretar POST.

## 5. Archivos de la futura implementación

Propuesta de alcance mínimo (nombres nuevos sujetos a aprobación):

| Archivo | Acción futura | Motivo |
|---|---|---|
| `app/Controllers/DashboardController.php` | Modificar | Único punto de conmutación HTML y rollback. |
| `app/Views/adapters/app-shell-context.php` | Crear | Traducir GET/estado legacy al contrato neutral. |
| `app/Views/inventario/importar-contenido.php` | Crear | Contenido sin `doctype`, header, sidebar, footer ni scripts. |
| `public/assets/js/importador.js` | Modificar mínimamente | Delegar reloj/sidebar al App Shell y conservar funciones FerroCheck. |
| `tests/smoke/smoke.php` | Ampliar | Detectar shell único, assets únicos y ausencia de shell legacy. |

No sería necesario modificar inicialmente `importar.php`, las vistas de Control de Escáneres, rutas, servicios, repositorios, consultas, tablas, `app.php`, partials ni los assets del App Shell. Si la extracción evidencia una dependencia imposible de conservar sin tocar otro archivo, el paso debe detenerse y replanificarse.

## 6. Contratos actuales

### 6.1 Variables PHP

| Contrato | Uso actual | Clasificación | Debe conservarse |
|---|---|---|---|
| `$_GET['modulo']` | Selección global | Shell compartido/Legacy | Sí, incluidos valores y fallback. |
| `$_GET['seccion']` | FerroCheck y Escáneres | Compartido entre módulos | Sí. |
| `$modulo` | Módulo activo | Legacy → Shell | Sí semánticamente. |
| `$ferroSeccion` | Sección FerroCheck | FerroCheck | Sí. |
| `$esFerrocheck` | Condicional de módulo | FerroCheck/Legacy | Puede derivarse. |
| `$esModulo` | Closure de selección | Legacy | Puede reemplazarse dentro del adaptador, no cambia contrato público. |
| `$tituloPagina` | `<title>` | Shell compartido | Mapear a `$pageTitle`. |
| `$seccion` en `plantilla.php` | Selección de vista CE | Control de Escáneres | Sí. |
| `$vistas` | Whitelist de vistas CE | Control de Escáneres | Sí. |
| `$vistaActual` | Estado activo CE | Control de Escáneres | Sí. |
| `$contenidoModulo` | HTML capturado CE | Control de Escáneres, HTML confiable | Sí. |
| `$baseModulo`, `$navegacion` | Navegación CE | Control de Escáneres | Sí. |
| `BASE_URL` | URLs y assets | Shell/Legacy global | Sí; mapear sin alterar valor. |

`importar.php` no recibe actualmente datos preparados por `DashboardController`; toma su estado de `$_GET` y `BASE_URL`. Éste es un acoplamiento que el adaptador debe encapsular, no cambiar en esta primera integración.

### 6.2 Contratos DOM de `importador.js`

**FerroCheck funcional:**

- Importación: `#fileInput`, `#dropzone`, `#fileInfo`, `.accordion-toggle`, `.accordion-content`, `#fileName`, `#fileSize`, `#fileType`, `#recordCount`, `#fileStatus`, `#importBtn`, `#progressFill`, `#progressPercent`, `#statusMessage`.
- Verificación: `.verifier-textarea`, `#verificacion .actions .btn.btn-primary`, `.results-table tbody`, `.action-link`.
- Exportación: `#exportExcelBtn`.
- Dashboard: `.stat-card`, `.counter`, `.status-banner__footer span/strong`.
- Modal dinámico: `#detalleEquipoModal`, `#detalleModalTitulo`, `#detalleModalCerrar`, `#detalleModalMeta`, `#detalleModalContenido`.
- Navegación horizontal legacy: `.top-nav__item` y secciones dentro de `main` (hoy puede quedar vacío sin error).

**Shell legacy dentro de `importador.js`:**

- `#currentDate`, `#currentTime`, `.info-panel__item`.
- `.menu-toggle`, `.menu-toggle__icon`, `.sidebar`, `.sidebar-backdrop`.
- `.sidebar__item`, `.sidebar-group`, `.sidebar__item--summary`, `.sidebar-submenu__item`.
- Clases de estado: `is-open`, `is-collapsed`, `is-visible`, `is-sidebar-open`, `active`.
- Listeners: `DOMContentLoaded`, click, toggle, resize, temporizadores de reloj/panel y refresco de dashboard.

`updateClock()` desreferencia `currentDate/currentTime` sin comprobar `null`. Bajo el App Shell esos IDs no existen; por tanto, cargar el script sin adaptación interrumpe la ejecución antes de registrar toda la funcionalidad posterior.

### 6.3 Contratos del App Shell

- Toggle/sidebar/backdrop: `[data-app-shell-toggle]`, `[data-app-shell-sidebar]`, `[data-app-shell-backdrop]`.
- Fecha/hora: `[data-app-shell-date]`, `[data-app-shell-time]`.
- Submenús: `[data-app-shell-submenu-toggle]`, `aria-controls`, `[data-app-shell-submenu]`.
- Estados: `.is-open`, `.is-visible`, `.is-sidebar-collapsed`, `.app-shell-page--locked`.
- IDs únicos: `appShellSidebar`, `appShellMain`, `appShellSubmenu-{moduleId}`.

### 6.4 Control de Escáneres

- La navegación depende de `$vistaActual`, `$baseModulo`, `$navegacion` y `$contenidoModulo`.
- Las vistas actuales son principalmente HTML capturado; `catalogo.php` usa `$rows` local.
- `plantilla.php` inserta actualmente un `<link>` a `control-escaneres.css` dentro del contenido. Temporalmente funciona, pero el adaptador debe preferir declarar ese asset en `$additionalStyles` y, en una fase posterior, retirar la etiqueta embebida para evitar doble carga.
- `control-escaneres.js` busca selectores del shell legacy y controles de modal. No se carga desde `importar.php`/`plantilla.php` en el flujo activo auditado; no debe añadirse automáticamente durante el primer corte.
- `catalogo-fase1.php` es un documento standalone con shell/assets propios y queda fuera del corte.

### 6.5 Inline scripts, globales y dependencias

- `importar.php` no contiene scripts inline; carga scripts externos al final.
- No se observaron variables JavaScript globales deliberadas: los scripts se encapsulan en callbacks/IIFE.
- Sí existe dependencia global de DOM, `window.location.href`, `document.body` y `BASE_URL` generado por PHP.
- Footer legacy no tiene dependencia funcional salvo sus clases/ID visuales.
- Reloj y sidebar legacy dependen de `importador.js`; los nuevos dependen exclusivamente de `app-shell.js`.

## 7. Mapeo al App Shell

| Variable nueva | Origen actual | Transformación/valor esperado | Default | Escape/confianza | Responsable futuro | Riesgo |
|---|---|---|---|---|---|---|
| `$pageTitle` | `$tituloPagina` | Misma matriz módulo→título | `VASCOR OPS` | Layout escapa | Adaptador | Bajo |
| `$documentLanguage` | `<html lang="es">` | `es` | `es` | Layout escapa | Adaptador | Bajo |
| `$assetBaseUrl` | `BASE_URL` | `rtrim(BASE_URL, '/')` | `''` sólo en demo | Layout escapa | Adaptador | Medio si base difiere |
| `$activeModule` | `$modulo` | Mismo ID normalizado | `dashboard` | Sidebar escapa | Adaptador | Medio por IDs desconocidos |
| `$activeSection` | `$ferroSeccion` o `$_GET['seccion']` CE | Default FerroCheck `consulta-vin`; CE `dashboard` | Según módulo | Sidebar escapa | Adaptador | Medio |
| `$modules` | Links hardcoded del sidebar legacy | Array con mismos IDs, URLs, iconos y secciones | `[]` | Partial escapa campos | Adaptador | Alto si una URL cambia |
| `$moduleNavigation` | `.vascor-module-nav` o `.ce-nav` | HTML capturado de navegación horizontal | `''` | HTML confiable, no escapar de nuevo | Vista/adaptador | Medio por duplicación CE |
| `$content` | Rama activa de `<main>` | HTML capturado sin shell exterior | `''` | HTML confiable; datos dinámicos deben escaparse en su vista | Vista de contenido | Alto |
| `$additionalStyles` | CSS específico | `importador.css`; además CSS CE cuando corresponda | `[]` | URLs escapadas | Adaptador | Alto por cascada/orden |
| `$additionalScripts` | Script por módulo | `importador.js` sólo donde se requiere funcionalidad FerroCheck; scripts CE sólo si ya son contrato activo | `[]` | URLs escapadas | Adaptador | Alto por listeners |
| `$header` | Textos hardcoded legacy | Nombre, subtítulo, versión y etiqueta de menú equivalentes | Defaults del partial | Partial escapa | Adaptador | Bajo |
| `$footer` | Footer hardcoded legacy | Título, subtítulo, crédito, desarrollador, año | Defaults del partial | Partial escapa | Adaptador | Bajo |
| `$sidebarLabel` | `aria-label` legacy | `Navegación lateral` o `Navegación principal` | Default del partial | Partial escapa | Adaptador | Bajo |

El adaptador es responsable de validar arrays y de construir URLs. Las vistas internas siguen siendo responsables de escapar datos no confiables. `$content` y `$moduleNavigation` son fragmentos HTML confiables generados localmente y el layout los emite deliberadamente sin escape.

## 8. Assets y orden de carga

### 8.1 Orden actual

`importar.php`:

1. Preconnect Google Fonts.
2. Poppins desde Google Fonts.
3. `importador.css`.
4. `vascor-design-system.css`.
5. Al final: `importador.js` si FerroCheck; en cualquier otro módulo, `operaciones-patio.js`.
6. Para Control de Escáneres, `plantilla.php` emite además `control-escaneres.css` dentro de `<main>`.

Problemas actuales relevantes: el Design System se carga después de `importador.css`; `operaciones-patio.js` se carga como fallback para dashboard y otros módulos aunque no sean patio; el CSS CE aparece en el body.

### 8.2 Orden propuesto

1. Preconnect y Poppins, una sola vez, desde `app.php`.
2. `vascor-design-system.css`, una sola vez.
3. `app-shell.css`, una sola vez.
4. `importador.css` como estilo temporal de contenido.
5. `control-escaneres.css` sólo cuando `activeModule=control-escaneres`.
6. Contenido HTML.
7. `app-shell.js`, una sola vez y con `defer`.
8. `importador.js`, adaptado y sólo en las vistas FerroCheck que lo necesitan.
9. JS CE únicamente donde exista hoy funcionalidad comprobada; no activar `control-escaneres.js` por el mero cambio de shell.
10. No cargar `operaciones-patio.js` en páginas que no sean `operaciones-patio`; esa ruta conserva su flujo separado en esta fase.

No se deben trasladar librerías externas nuevas. La integración debe comprobar que cada URL de asset aparezca una sola vez.

### 8.3 Delegación JavaScript

Debe permanecer temporalmente en `importador.js`:

- análisis/importación de archivo;
- progreso y estados;
- verificación simple/múltiple;
- detalle/modal dinámico;
- tarjetas y resumen periódico;
- exportación XLSX;
- acordeón y navegación interna propia de FerroCheck.

Debe desactivarse o ejecutarse sólo si existe `.dashboard-shell` legacy:

- reloj `#currentDate/#currentTime`;
- ciclo `.info-panel__item`;
- control `.menu-toggle/.sidebar/.sidebar-backdrop`;
- estado `is-sidebar-open`;
- listeners del menú y grupos legacy.

`app-shell.js` será el único propietario del reloj global, sidebar, backdrop, Escape, breakpoint y `aria-expanded` del shell nuevo. No deben crearse puentes que hagan que ambos scripts controlen los mismos elementos.

## 9. Riesgos de doble shell y validaciones

| Riesgo | Causa | Validación requerida |
|---|---|---|
| Dos headers | Incluir `importar.php` completo dentro de `$content` | Exactamente un `.app-header`; cero `.topbar`. |
| Dos sidebars | Conservar `.sidebar` legacy | Exactamente un `[data-app-shell-sidebar]`; cero `.dashboard-shell > .dashboard-body > .sidebar`. |
| Dos footers | Contenido incluye `#footer` | Exactamente un `.app-footer`; cero `footer.footer`. |
| Dos mains | Documento legacy anidado | Exactamente un `main`; `#appShellMain` único. |
| Dos backdrops | Legacy y nuevo simultáneos | Exactamente un `[data-app-shell-backdrop]`; cero `.sidebar-backdrop`. |
| Dos menús móviles | Dos toggles/listeners | Un `[data-app-shell-toggle]`; cero `.menu-toggle`; una transición por click. |
| IDs duplicados | Copia de shell o contenido repetido | Script de prueba que agrupe `[id]` y falle si un ID aparece más de una vez. |
| Listeners duplicados | Asset duplicado o inicialización doble | Una etiqueta por `src`; click único; una solicitud de dashboard por ciclo. |
| Dos relojes | Ambos scripts activos | Un par `[data-app-shell-date/time]`; cero `#currentDate/#currentTime` en shell nuevo. |
| Navegación duplicada | Sidebar y navegación horizontal emitidos dos veces | Un sidebar global y como máximo una navegación horizontal del módulo. |
| CSS duplicado | `<link>` embebido más `$additionalStyles` | Una etiqueta por `href`; comprobar orden en DOM. |
| Contenido recortado/anidado | Wrapper legacy dentro de `.app-main` | Cero `.dashboard-shell`; revisión responsive y scroll. |

## 10. Secuencia de implementación futura

### Paso 0 — Protección de línea base

- Archivos: ninguno.
- Objetivo: confirmar árbol limpio y `114/0`.
- Riesgo: nulo.
- Automática: status, log, smoke GET.
- Manual: abrir rutas críticas sin acciones POST.
- Continuar si: todo coincide.
- Rollback: no aplica.

### Paso 1 — Pruebas estructurales primero

- Archivo: `tests/smoke/smoke.php`.
- Objetivo: expresar shell único, assets únicos y ausencia del shell legacy bajo el modo nuevo.
- Riesgo: bajo.
- Automática: las pruebas nuevas deben fallar antes del corte y preservar las 114 existentes.
- Manual: ninguna.
- Continuar si: el fallo es únicamente el esperado por falta de integración.
- Rollback: revertir el cambio de pruebas.

### Paso 2 — Adaptador y contenido paralelo sin activar

- Archivos: crear adaptador y `importar-contenido.php`.
- Objetivo: reproducir sólo contenido/navegación y preparar el contrato del layout.
- Riesgo: medio por omisión de DOM.
- Automática: `php -l`; prueba aislada de render; comparación de IDs/links por módulo/sección.
- Manual: render paralelo no público o fixture local, sin POST.
- Continuar si: todas las ramas producen contenido y no hay shell legacy.
- Rollback: borrar/revertir sólo archivos nuevos.

### Paso 3 — Separar propietarios JavaScript

- Archivo: `public/assets/js/importador.js`.
- Objetivo: mantener funciones FerroCheck y condicionar/desactivar lógica global legacy.
- Riesgo: medio-alto por archivo monolítico.
- Automática: smoke, ausencia de errores en páginas sin controles FerroCheck, búsqueda de selectores.
- Manual: reloj/sidebar del App Shell y funciones FerroCheck.
- Continuar si: consola limpia y no hay listeners dobles.
- Rollback: restauración controlada del archivo o revert del commit del paso.

### Paso 4 — Activar un único punto de integración

- Archivo: `DashboardController.php`.
- Objetivo: delegar GET HTML al adaptador; no tocar las ramas POST de `public/index.php`.
- Riesgo: medio, concentrado.
- Automática: PHP lint, smoke ampliado, HTTP/asset counts, HTML estructural.
- Manual: matriz completa de módulos/secciones y responsive.
- Continuar si: todas las rutas GET conservan URL/estado y todas las pruebas pasan.
- Rollback: volver al `require importar.php` o revertir el commit de integración.

### Paso 5 — Estabilización

- Archivos: idealmente ninguno; sólo correcciones pequeñas autorizadas.
- Objetivo: observación y pruebas de regresión.
- Riesgo: bajo si no se amplía alcance.
- Automática/manual: plan de la sección siguiente.
- Continuar si: dos ciclos de validación completos sin regresión.
- Rollback: revert inmediato del commit de integración.

### Paso 6 — Deuda posterior, fuera de esta integración

Retirar fallback, deduplicar contenido, mover el `<link>` CE al contrato de assets y dividir `importador.js` sólo en fases separadas con su propia autorización. No combinar estas tareas con el corte inicial.

## 11. Plan de pruebas

### 11.1 Automáticas seguras (GET/lectura)

- `php -l` en todo PHP modificado/nuevo.
- Balance de llaves CSS si se tocara CSS (no se prevé).
- Las 114 pruebas de humo actuales.
- HTTP 200 de dashboard, todas las secciones FerroCheck y todas las vistas CE.
- Ausencia de errores PHP visibles.
- Assets HTTP 200 y cada `href/src` una sola vez.
- Exactamente un header, sidebar, footer, main, backdrop y toggle.
- Cero `.dashboard-shell`, `.topbar`, `.sidebar-backdrop` y footer legacy en modo nuevo.
- IDs HTML únicos.
- Estado activo correcto para módulo y sección.
- Confirmar que POST sigue despachándose antes de `DashboardController::index()` mediante análisis/fixtures sin escritura.

### 11.2 Manuales de sólo lectura o seguras

- Dashboard.
- Consulta de VIN conocido e inexistente, **sólo tras confirmar que `VerificadorService` no escribe**.
- Búsqueda múltiple con la misma condición.
- Detalle/modal.
- Exportación XLSX (genera descarga, no debe escribir BD).
- Todas las vistas CE actuales mientras sus controles sigan demostrativos/disabled.
- Sidebar: abrir/cerrar, Escape, backdrop, resize y `aria-expanded`.
- Navegación horizontal y estados activos.
- Desktop 1440×900, laptop 1366×768, tablet 768×1024, móvil 390×844 y 360×800.
- Consola sin errores, red sin assets duplicados, scroll vertical/horizontal.

### 11.3 Pruebas con posible escritura o efecto externo

- Click final **Importar Inventario** envía `accion=importar` y escribe inventario: ejecutar sólo con autorización, respaldo y datos de prueba.
- Entrega, recepción, registro/importación de escáneres: considerar escritura aunque la vista actual parezca demostrativa; verificar endpoint/servicio antes de usar.
- Importación de catálogo fase 1: potencialmente escribe; fuera del corte.
- Cualquier prueba POST cuya implementación no haya sido auditada completamente debe tratarse como no segura.

La barra de progreso puede validarse visualmente durante el análisis previo del archivo, evitando el botón que confirma la importación. No ejecutar POST en esta fase de planificación.

## 12. Rollback

Rollback preferente después de un commit de integración aislado:

```bash
git revert <hash-del-commit-de-integracion>
```

Esto crea historial auditable y conserva los commits base. Antes de hacer revert se debe verificar que no existan cambios locales ajenos.

Rollback operativo inmediato previo al commit:

1. Cambiar de forma controlada `DashboardController::index()` para volver al único `require` de `app/Views/inventario/importar.php`.
2. Validar PHP y ejecutar smoke.
3. No borrar archivos paralelos durante la contingencia; pueden quedar inaccesibles hasta una limpieza posterior autorizada.

Restauración selectiva sugerida sólo si los cambios aún no están comprometidos y se confirma que pertenecen exclusivamente a la integración:

```bash
git restore --source=HEAD -- app/Controllers/DashboardController.php
git restore --source=HEAD -- public/assets/js/importador.js
```

No usar `git reset --hard`, `git clean` ni restauraciones amplias. Una feature flag sólo aporta seguridad si es local, tiene default legacy y no permite conmutación pública accidental; no es obligatoria si el commit es pequeño y el include se puede revertir inmediatamente.

## 13. Criterios de aceptación

- URLs, query strings, orden de despacho y respuestas POST permanecen iguales.
- `114 PASS / 0 FAIL` como mínimo, más pruebas estructurales nuevas.
- Un solo App Shell y cero shell legacy en el HTML integrado.
- Un único propietario para sidebar, backdrop, reloj, Escape y `aria-expanded`.
- Sin errores JavaScript/PHP y sin assets duplicados.
- FerroCheck conserva dashboard, consulta, búsqueda, detalle, importación/análisis, progreso y exportación.
- Todas las vistas CE conservan contenido, navegación y estado activo.
- Desktop y móvil sin desbordamiento horizontal global.
- Ninguna consulta, tabla, migración o dato se modifica por el corte visual.
- Rollback probado conceptualmente y commit de integración aislado.

## 14. Fuera de alcance

- Ejecutar la integración.
- Modificar rutas públicas o `public/index.php`.
- Reemplazar o eliminar `importar.php`.
- Dividir FerroCheck o refactorizar servicios/repositorios.
- Activar funcionalidades nuevas de Control de Escáneres.
- Integrar `catalogo-fase1.php`.
- Cambiar base de datos, consultas, tablas o migraciones.
- Reescribir Design System, CSS de módulos o App Shell.
- Dividir completamente `importador.js` en esta misma entrega.
- Hacer merge, commit o push durante esta fase de planificación.

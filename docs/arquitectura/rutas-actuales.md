# Inventario de rutas actuales de VASCOR OPS

Línea base levantada en la rama `refactor/modularizacion-vascor-ops`. Este documento describe el despacho actual; no propone rutas nuevas.

## Entrada principal

Todas las rutas visuales pasan por `public/index.php`. Salvo `operaciones-patio`, las solicitudes GET terminan en `DashboardController::index()` y este carga `app/Views/inventario/importar.php`.

| Función | Método | URL / parámetros GET | Parámetros POST | Despacho actual | Salida esperada | HTTP esperado |
|---|---|---|---|---|---|---|
| Dashboard general | GET | `/index.php?modulo=dashboard` | — | `DashboardController::index()` | HTML, dashboard general | 200 |
| FerroCheck Dashboard | GET | `/index.php?modulo=ferrocheck&seccion=dashboard` | — | `DashboardController::index()` | HTML, dashboard FerroCheck | 200 |
| Consulta VIN | GET | `/index.php?modulo=ferrocheck&seccion=consulta-vin` | — | `DashboardController::index()` | HTML, verificador | 200 |
| Importar Excel | GET | `/index.php?modulo=ferrocheck&seccion=importar-excel` | — | `DashboardController::index()` | HTML, importador | 200 |
| Búsqueda múltiple | GET | `/index.php?modulo=ferrocheck&seccion=busqueda-multiple` | — | `DashboardController::index()` | HTML, verificador múltiple | 200 |
| Configuración FerroCheck | GET | `/index.php?modulo=ferrocheck&seccion=configuracion` | — | `DashboardController::index()` | HTML, configuración | 200 |
| Escáneres Dashboard | GET | `/index.php?modulo=control-escaneres&seccion=dashboard` | — | `DashboardController` → `importar.php` → `plantilla.php` | HTML | 200 |
| Escáneres Catálogo | GET | `/index.php?modulo=control-escaneres&seccion=catalogo` | — | Igual al anterior | HTML | 200 |
| Escáneres Expediente | GET | `/index.php?modulo=control-escaneres&seccion=expediente` | — | Igual al anterior | HTML | 200 |
| Escáneres Entrega | GET | `/index.php?modulo=control-escaneres&seccion=entrega` | — | Igual al anterior | HTML | 200 |
| Escáneres Recepción | GET | `/index.php?modulo=control-escaneres&seccion=recepcion` | — | Igual al anterior | HTML | 200 |
| Escáneres Historial | GET | `/index.php?modulo=control-escaneres&seccion=historial` | — | Igual al anterior | HTML | 200 |
| Escáneres Reportes | GET | `/index.php?modulo=control-escaneres&seccion=reportes` | — | Igual al anterior; alias interno a `reporte.php` | HTML | 200 |

`seccion=reporte` también es aceptado por la plantilla de Control de Escáneres. Si falta `seccion`, FerroCheck usa `consulta-vin` y Control de Escáneres usa `dashboard`.

## Endpoints POST en `public/index.php`

El front controller decide la operación por presencia y prioridad de campos POST, no por una ruta declarativa.

| Función | Método y URL | Contrato | Controlador | Respuesta | HTTP esperado |
|---|---|---|---|---|---|
| Resumen de indicadores | POST a URL visible | `dashboard_stats=1` | `DashboardController::resumenTarjetas()` | JSON | 200; 500 ante error |
| Detalle de plataforma / modal | POST a URL visible | `codigo_equipo` | `DetallePlataformaController::detalle()` | JSON | 200; 500 ante error |
| Exportación Excel | POST a URL visible | `accion=exportar_xlsx`, `equipos_filtrados` JSON | `ExportacionInventarioController::exportar()` | XLSX o JSON de error | 200; 500 ante error |
| Consulta VIN / múltiple | POST a URL visible | `equipos`, texto separado por líneas | `VerificadorController::verificar()` | JSON | 200; 500 ante error |
| Analizar Excel | POST a URL visible | multipart `archivo` | `InventarioController::importar()` | JSON | 200 |
| Confirmar importación | POST a URL visible | multipart `archivo`, `accion=importar` | `InventarioController::importar()` | JSON; modifica `inventario` | 200; 500 ante error |

La prioridad actual es: `operaciones-patio` GET, resumen, detalle, exportación, verificación, archivo y finalmente renderizado general.

## API de Control de Escáneres

Endpoint: `/control-escaneres-api.php`. Todas las operaciones usan POST y responden JSON.

| Acción | Parámetros | Despacho | Efecto | HTTP esperado |
|---|---|---|---|---|
| `registrar_scanner` | `action` y datos del escáner | `ControlEscaneresController::registrarScanner()` | Inserta en `scanners` | 200; 400 ante error |
| `import_preview` | `action`, multipart `archivo` | `previsualizarImportacion()` | Solo lectura del archivo | 200; 400 ante error |
| `import_execute` | `action`, multipart `archivo` | `ejecutarImportacion()` | Inserta/actualiza `scanners` | 200; 400 ante error |
| Acción ausente/desconocida | `action` | endpoint directo | Ninguno | 400 |

## Rutas declaradas pero no activas

`routes/web.php` contiene únicamente `/ => DashboardController@index`, pero `public/index.php` no instancia `App\Core\Router`; por tanto, esa tabla no gobierna el despacho actual.

## Alcance de las pruebas automáticas

Las pruebas de humo de FASE 0 solo ejecutan GET sobre las 13 rutas visuales y GET sobre assets estáticos. No llaman ninguno de los endpoints POST anteriores.

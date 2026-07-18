# Manifiesto de módulos actuales

## FerroCheck

- **Identificador:** `ferrocheck`.
- **Estado:** funcional; frontend concentrado en una vista central.
- **Entrada visual:** `public/index.php` → `DashboardController::index()` → `app/Views/inventario/importar.php`.
- **Secciones:** `dashboard`, `consulta-vin`, `importar-excel`, `busqueda-multiple`, `configuracion`.
- **Controladores:** Dashboard, Inventario, Verificador, DetallePlataforma y ExportacionInventario.
- **Vistas:** bloques condicionales dentro de `importar.php`.
- **CSS:** `importador.css` y `vascor-design-system.css`.
- **JavaScript:** `importador.js`.
- **Servicios:** Dashboard, Inventario, Excel, Verificador, DetallePlataforma, InventarioExport y XlsxExport.
- **Repositorio:** `InventarioRepository.php`; fotografías pendientes de integración completa.
- **Tabla:** `inventario`.
- **Endpoints:** POST al `index.php` actual para resumen, detalle, verificación, análisis/importación y exportación.
- **Dependencias globales:** shell en `importar.php`, `BASE_URL`, conexión PDO, autoload y PhpSpreadsheet.
- **Funciones activas:** indicadores, análisis/importación, consulta individual/múltiple, detalle modal y exportación.
- **Pendientes visuales:** configuración aún es estado preparado.
- **Riesgos:** archivo de vista monolítico; JS y CSS mezclan shell con negocio; endpoints ligados a nombres POST.

## Control de Escáneres

- **Identificador:** `control-escaneres`.
- **Estado:** interfaz integrada completa; backend de catálogo/importación presente, pero controles visuales principales siguen inactivos.
- **Entrada visual:** `DashboardController` → `importar.php` → `control-escaneres/plantilla.php`.
- **Secciones:** Dashboard, Catálogo, Expediente, Entrega, Recepción, Historial y Reportes.
- **Controlador:** `ControlEscaneresController.php` para API; la página no tiene controlador visual propio.
- **Vistas:** carpeta `app/Views/control-escaneres/`.
- **CSS:** `control-escaneres.css`, más estilos globales.
- **JavaScript:** el shell integrado recibe `operaciones-patio.js`; existen `control-escaneres.js` y variante fase 1 sin carga en la plantilla principal.
- **Servicios:** `ControlEscaneresService`, `ScannerImportService`.
- **Repositorio:** `ControlEscaneresRepository`.
- **Tabla:** `scanners`.
- **Endpoint:** `public/control-escaneres-api.php`.
- **Dependencias globales:** shell, Design System, `BASE_URL`, PDO y PhpSpreadsheet.
- **Funciones activas:** consulta/registro/importación disponibles en backend/API.
- **Funciones visuales pendientes:** QR, fotos, firmas, entrega, recepción, historial y reportes todavía no conectados.
- **Riesgos:** ruta visual indirecta; doble catálogo fase 1/integrado; scripts de shell duplicados; variables `$contenidoModulo` y `$vistaActual` forman un contrato implícito.

## Shell global

- **Identificador:** no formalizado.
- **Estado:** operativo, mezclado dentro de la vista `inventario/importar.php`.
- **Componentes:** documento HTML, header, marca, reloj, sidebar, backdrop, main y footer.
- **CSS:** sección global de `importador.css` y `vascor-design-system.css`.
- **JavaScript:** funciones de shell repartidas entre `importador.js`, `operaciones-patio.js` y scripts alternativos de escáneres.
- **Dependencias:** `DashboardController`, `BASE_URL`, parámetros `modulo`/`seccion` y estructura de clases globales.
- **Funciones activas:** navegación entre módulos, sidebar responsive, reloj/fecha, estado activo, Poppins y Design System.
- **Riesgos:** no existe layout neutral ni partials; header/sidebar están duplicados en otras vistas; un cambio en `importar.php` puede afectar todos los módulos integrados.

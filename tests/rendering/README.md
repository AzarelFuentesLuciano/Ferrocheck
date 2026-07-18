# Prueba aislada del Render Adapter

Valida la implementación mínima y desconectada de `RenderContext`, `RenderAdapter` y `RenderException` contra el App Shell existente.

Ejecución:

```bash
php tests/rendering/render-adapter-test.php
```

La prueba usa `require_once` explícitos para ser autónoma. El proyecto ya dispone de autoload PSR-4 (`App\\` → `app/`) mediante Composer, pero esta prueba no carga el bootstrap global, no crea otro autoloader y no modifica `public/index.php`.

No necesita servidor web, sesión, base de datos, Excel, controladores ni solicitudes POST. No cambia rutas ni activa el adaptador.

Comprueba:

- construcción y normalización del contexto;
- renderizado del layout como string sin salida directa;
- estructura HTML/App Shell única;
- contenido HTML interno confiable;
- título y estados activos;
- deduplicación y orden de assets;
- validación de título, idioma y assets;
- error controlado cuando falta el layout.

No comprueba integración con módulos, routing, endpoints, comportamiento JavaScript, responsive, RBAC ni fallback legacy. Es complementaria a `tests/smoke/smoke.php`; las 114 pruebas de humo continúan protegiendo el flujo activo, que todavía usa `importar.php`.

## Legacy Render Bridge

`LegacyRenderBridge` transforma un arreglo explícito y filtrado de variables legacy en un `RenderContext`. No usa `get_defined_vars()`, no lee superglobales y no acepta arreglos completos como `$_GET`, `$_POST` o `$_SESSION`. La clave `vistaActual` se ignora y nunca se ejecuta ni se utiliza para incluir archivos.

Ejecución:

```bash
php tests/rendering/legacy-render-bridge-test.php
```

La prueba recorre de forma aislada `array legacy → LegacyRenderBridge → RenderContext → RenderAdapter → HTML`. No necesita base de datos, servidor, sesión, rutas, controladores, POST ni Excel. El bridge todavía no está conectado a `DashboardController` y no activa el App Shell en el flujo real.

## Vista de contenido de FerroCheck

`app/Views/inventario/partials/ferrocheck-content.php` contiene únicamente el contenido interior reutilizable del módulo FerroCheck. Requiere la variable `$ferroSeccion` y la constante `BASE_URL`; no contiene documento HTML, shell global ni cargas de assets.

`app/Views/inventario/importar.php` continúa siendo el documento legacy y el flujo activo. Conserva el header, sidebar, footer y la carga de `importador.css` e `importador.js`, e incluye la vista de contenido mediante una ruta estática basada en `__DIR__`. La vista todavía no está conectada al pipeline `app_shell`.

Ejecución:

```bash
php tests/rendering/ferrocheck-content-view-test.php
```

La prueba CLI renderiza la vista con variables mínimas, comprueba que no incorpore shell o assets, preserva los contratos estructurales de FerroCheck y verifica que el wrapper legacy genere una sola copia del contenido. Esta separación no modifica lógica de negocio, endpoints, CSS ni JavaScript.

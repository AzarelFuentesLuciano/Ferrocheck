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

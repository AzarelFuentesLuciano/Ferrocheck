# Estrategia de migraciones de Control de Escáneres

## Estado inicial y decisión sobre el archivo legacy

`database/migrations/20260716_create_scanners_table.sql` se conserva sin cambios como migración legacy. No hay evidencia versionada que confirme si fue ejecutada fuera del entorno local y no es seguro reescribir su historia.

La migración canónica posterior crea `scanners` sólo cuando no existe. Si detecta una tabla `scanners` previa, valida una columna exclusiva del esquema canónico y aborta ante incompatibilidad. De esta forma, una tabla legacy nunca se acepta silenciosamente como esquema actual.

Un ambiente que ya tenga la tabla legacy debe detenerse, respaldarse y recibir una migración de conciliación específica basada en su esquema y datos reales. Esa conciliación queda fuera de esta fase.

## Orden de aplicación

1. Esquema canónico de `scanners`.
2. `scanner_movimientos`.
3. `scanner_inspecciones`.
4. `scanner_inspeccion_detalles`.
5. `scanner_incidencias`.
6. `scanner_evidencias` y referencias circulares de firma/fotografía.
7. `auditoria_eventos`.

Las migraciones no deben ejecutarse automáticamente al desplegar código. Antes de cada ambiente se requiere inventario de esquema, respaldo verificable, ventana de cambio y prueba de rollback.

## Compatibilidad

El cliente local detectado es MariaDB 10.4.32. Esta versión admite columnas generadas `PERSISTENT`, índices UNIQUE sobre ellas, CHECK constraints y JSON como alias validado de LONGTEXT. La migración usa una columna generada nullable para permitir muchos movimientos cerrados y sólo un movimiento `abierto` por escáner.

Antes de producción debe verificarse `SELECT VERSION()` en el servidor objetivo. Para MySQL 5.7+/8 se deberá validar la sintaxis equivalente (`STORED` en lugar de `PERSISTENT`) o proporcionar una variante controlada. No se debe eliminar la validación transaccional de servicio aunque exista el índice.

## Rollback

Cada archivo incluye un bloque `ROLLBACK` comentado. El rollback completo se ejecuta en orden inverso: auditoría, evidencias, incidencias, detalles, inspecciones, movimientos y scanners.

Los DROP son destructivos. Sólo se habilitan manualmente en un ambiente autorizado y después de respaldo. Las claves circulares agregadas en evidencias se eliminan antes de las tablas involucradas.

## Datos sensibles

El esquema excluye PIN y PUK. IMEI, ICCID y teléfono requieren permiso, enmascaramiento y auditoría. Los documentos JSON de auditoría deben sanitizarse antes de persistirse. Las evidencias almacenan ruta, MIME, tamaño y hash; nunca el binario.

## Trazabilidad futura

Las columnas de actor son BIGINT UNSIGNED nullable sin FK. Cuando exista `usuarios`, una migración posterior validará valores, agregará índices/FK y hará obligatorios los actores para operaciones nuevas. Los nombres de personas físicas no sustituyen estas columnas.

## Verificación y riesgos

- Ejecutar `tests/control-escaneres/schema-test.php` antes de cualquier aplicación.
- No aplicar si existe `scanners` sin `codigo` y `codigo_qr`.
- No importar datos legacy automáticamente.
- No almacenar códigos fuera de la convención `SC-0001` sin conciliación aprobada.
- Confirmar límites y collation de índices en el servidor objetivo.
- Confirmar política de retención de auditoría y evidencias.
- Mantener un registro externo de migraciones aplicadas y checksums.

## Decisiones pendientes

- Diseño final de `usuarios`, `areas`, roles y permisos.
- Estrategia de migración de códigos `ESC-*`.
- Backend de archivos y retención de evidencias.
- Catálogos normalizados para tipos de incidencia y evidencia.
- Migración de conciliación si se descubre una tabla legacy aplicada.


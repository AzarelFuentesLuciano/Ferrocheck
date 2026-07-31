# Catálogo maestro de rutas

## Causa del bloqueo corregido

La primera integración leía `vascor_sm_db.xlsx` directamente desde
`docs/rail/consist/referencias/`. Los binarios de referencia se custodian fuera
de Git, por lo que una instalación nueva no disponía del archivo y
`RouteCodeCatalogLoader` detenía la generación antes de persistir el borrador.

`rail_catalog_versions` sólo registraba metadatos después de leer el archivo;
no contenía las rutas y no existía un importador. Las fechas del periodo
operativo nunca participaron en la selección del catálogo.

## Flujo vigente

1. Un usuario con `rail.catalogos.importar` abre Rail → Configuración →
   Catálogos.
2. Carga un XLSX de hasta 10 MB con la hoja `route_codes`.
3. Se validan los encabezados `Route Code`, `Market` y
   `Shipping Destination`.
4. Se calcula SHA-256 y se importan rutas únicas mediante una transacción.
5. Las versiones anteriores quedan inactivas y la versión importada queda
   activa.
6. Se registra auditoría con nombre, SHA-256 y cantidad de rutas; nunca se
   guarda el archivo completo.
7. Generar Consist consume exclusivamente la versión activa desde la base.

La tabla `rail_catalog_route_codes` pertenece a la migración
`20260731_017_create_rail_route_catalog.sql`. No deben insertarse rutas
ficticias ni marcas manuales en `schema_migrations`.

## Recuperación

Si no existe una versión activa, la generación queda bloqueada con una
instrucción para importar el catálogo. El preview y el token del lote se
conservan. Una falla durante la importación revierte versión, rutas, activación
y auditoría.

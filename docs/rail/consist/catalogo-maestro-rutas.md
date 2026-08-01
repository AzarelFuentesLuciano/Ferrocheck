# Catálogo maestro oficial de rutas

## Fuente aprobada

`vascor_sm_db.xlsx` es el catálogo maestro oficial. La única hoja sembrada por
el sistema es `route_codes`; las hojas auxiliares no se mezclan con ella.

- SHA-256: `42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e`
- Filas útiles: 436
- Route Codes normalizados únicos: 284
- Registros distintos considerando las once columnas: 341

El XLSX se utiliza sólo durante desarrollo para generar y verificar la
migración. Está ignorado por Git y no se necesita en producción.

## Instalación automática

La migración `20260731_019_seed_official_rail_route_catalog.sql` incluye las
436 filas como SQL determinista. Después de aplicarla:

1. `rail_catalog_versions` contiene la versión oficial con `origin=system`.
2. Su SHA-256 es el hash aprobado.
3. La versión oficial es la única activa.
4. `rail_catalog_route_codes` contiene las 436 filas originales.
5. Consist Rail consulta exclusivamente la base de datos.

`imported_by` admite `NULL` para instalaciones de sistema. Esto evita
atribuir el seed a un usuario arbitrario. Las importaciones administrativas
siguen registrando al usuario real y conservan el permiso
`rail.catalogos.importar`, aunque el formulario ya no es un requisito visible
en Configuración.

## Duplicados y modelo

`Route Code` no es una clave única: 104 códigos se repiten y la restricción
anterior habría descartado 152 filas. Tampoco bastan Production Plant y
RC-Plant: la combinación todavía deja conflictos reales para `82`, `54` y
`75`.

La clave de preservación es:

`catalog_version_id + source_row`

Se almacenan las once columnas de `route_codes`, incluyendo Production Plant,
RC-Plant, Load by y Border crossing. Las 95 repeticiones exactas también se
conservan porque forman parte de las 436 filas oficiales.

Durante la resolución:

- variantes con el mismo Market y Shipping Destination son equivalentes;
- para Carrier operativo exactamente KCSM se conserva el Market de la primera
  fila y el destino efectivo es Laredo;
- para otras variantes se compara `fdDestinationLocation` con destinos no
  vacíos mediante localidad normalizada, sin fuzzy matching genérico;
- una coincidencia única se selecciona sin comparar el Carrier operativo con
  el Carrier del catálogo;
- si la evidencia no desambigua, se conserva la menor `source_row` como
  primera coincidencia histórica y se registra una advertencia;
- un Route Code inexistente continúa deteniendo la generación.

La localidad aplica trim, mayúsculas, colapso de espacios y eliminación de
puntuación no significativa. El destino no vacío del catálogo debe coincidir
por tokens completos y tener al menos tres caracteres.

Los modos persistidos en `trace_json` son `unique`,
`equivalent_duplicates`, `kcsm_override`, `destination_match` y
`historical_first_match`. Este último agrega el detalle resumido a
`rail_consist_units.issues_json` y un resumen por Route Code a
`rail_consists.issues_json`. `auditoria_eventos` conserva sólo el resumen del
evento general de generación.

No existe una correspondencia demostrada entre los valores `Plant` de CNACS o
`fdManufacturerRouteCode2` y Production Plant/RC-Plant. Por ello no se usan
como discriminadores inventados.

## Idempotencia y versión activa

La migración reutiliza una versión que ya tenga el SHA-256 oficial, agrega las
filas faltantes por `source_row` y evita duplicarlas. Si existe otra versión
activa, se conserva pero queda inactiva; no se eliminan sus rutas ni los
Consists que la referencien.

Los `ALTER TABLE` de MariaDB producen commits implícitos. La migración lo
documenta expresamente y usa una transacción únicamente para el bloque DML.

## Actualización futura

Si cambia el catálogo oficial:

1. custodiar el nuevo XLSX fuera de Git;
2. validar encabezados, conteos, duplicados y SHA-256;
3. actualizar la constante aprobada de la herramienta;
4. ejecutar `tools/rail/generate-route-catalog-seed.php`;
5. revisar el diff SQL y ejecutar todas las pruebas Rail;
6. publicar una migración nueva; nunca editar una migración ya aplicada.

## Rollback

El rollback elimina sólo las rutas marcadas por la 019. Si la versión fue
creada por la migración, intenta retirarla; la FK desde `rail_consists`
detendrá la operación si ya fue utilizada. Si la versión oficial ya existía,
se conserva y sólo se revierten las filas agregadas por el seed.

No se eliminan las tablas de la migración 017 ni documentos operativos.

## Restricciones conocidas

- Los Route Codes con destinos divergentes usan evidencia de destino o fallback
  histórico visible; no bloquean por ambigüedad.
- El rollback no reactiva automáticamente una versión anterior.
- El endpoint y permiso de importación administrativa se conservan por
  compatibilidad, pero no forman parte del flujo normal de instalación.

## Alcance de la validación histórica

Los ocho resultados históricos de 54, 54B y 54D fueron reproducidos mediante
fixtures basados en valores observables del entregable oficial; la validación
completa desde los archivos fuente del lote permanece pendiente.

No están disponibles Vehicle Load Report, Shippers y CNACS originales del
lote de 437 VIN. La prueba 437/437 valida el exportador sobre datos finales ya
construidos y no representa equivalencia integral desde entradas.

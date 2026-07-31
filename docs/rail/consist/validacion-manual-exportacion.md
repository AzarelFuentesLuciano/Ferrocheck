# Validación manual de exportación de Consist Rail

## Flujo de prueba

1. Iniciar sesión con una cuenta local autorizada.
2. Abrir Rail → Consist Rail → Nuevo Consist.
3. Cargar Vehicle Load Report, Shippers y CNACS.
4. Validar el lote y ejecutar el análisis.
5. Capturar fecha inicial y final.
6. Generar el borrador, revisar totales, plataformas e incidencias.
7. Abrir una unidad, el historial y descargar el XLSX dos veces.

No deben ejecutarse estas pruebas contra producción ni `vascor-pruebas`.

## Archivos utilizados

- `Plantilla_Consist.xlsm`, únicamente como fuente diagnóstica.
- `vascor_sm_db.xlsx`, catálogo controlado.
- `Consist Rail del 29 al 30 de Julio de 2026.xlsx`, referencia visual.

Los originales son de sólo lectura y sus SHA-256 deben compararse antes y
después.

Los tres binarios de referencia se custodian fuera de Git y deben
aprovisionarse explícitamente en `docs/rail/consist/referencias/` con los
hashes aprobados antes de habilitar la generación. El commit no contiene ni
modifica esos XLSM/XLSX.

## Resultado esperado

- Nombre derivado del periodo en español y extensión `.xlsx`.
- Hojas exactas: `Consist`, `Summary`, `Version`; esta última oculta.
- Consist conserva todas las ocurrencias.
- Summary elimina todas las filas de un VIN cuya frecuencia sea mayor que uno.
- Los totales son dinámicos: cada plataforma admite hasta ocho unidades y la
  última puede quedar incompleta.
- Cero fórmulas, macros, conexiones, QueryTables y vínculos externos.
- VIN almacenados como texto.
- El historial, las incidencias y la trazabilidad permanecen en VASCOR OPS y
  no se agregan como columnas técnicas al entregable.

## Validación estructural

Ejecutar `php tests/rail/consist-workbook-export-test.php`. La prueba cubre
estructura OpenXML, hoja oculta, filas dinámicas, regla de duplicados, nombres
de periodo, anchos y filtros.

La prueba local persistente requiere explícitamente `CONSIST_LOCAL_RUN=1`, un
DSN hacia `127.0.0.1`/`localhost` y la base `ferrocheck`.

Antes de habilitar el flujo deben estar aplicadas, en orden, las migraciones
`20260725_015_register_rail_module.sql` y
`20260730_016_create_rail_consists.sql`. Este repositorio no dispone de un
runner que registre automáticamente 015 y 016 en `schema_migrations`; no se
deben insertar marcas ficticias. La descarga requiere autenticación, acceso
organizacional a Rail y el permiso `rail.consist.exportar`.

## Validación visual

Abrir la descarga en Microsoft Excel, sólo lectura, junto a la referencia.
Comparar orden de hojas, encabezados negros, franjas de tabla, tipografía,
alineación centrada, anchos, altura de encabezado, filtros y formatos.
Cerrar ambos libros sin guardar.

## Problemas encontrados y solución

- El comparador y dashboard conservaban cifras históricas fijas. Se
  sustituyeron por el tamaño real de la referencia y mensajes dinámicos.
- La primera exportación no reproducía el formato efectivo de tabla. Se
  incorporó `TableStyleMedium2` y el encabezado explícito de la referencia.
- La base XAMPP no tenía registrada la migración 015 del módulo Rail. Se aplicó
  exclusivamente esa migración después de un respaldo local y el recorrido
  HTTP autenticado completo quedó validado, incluyendo historial, autorización,
  CSRF, exportación y segunda descarga.

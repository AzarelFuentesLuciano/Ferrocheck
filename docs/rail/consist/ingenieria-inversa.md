# Ingeniería inversa de Consist Rail

## Alcance y custodia

Diagnóstico estático de los originales `Plantilla_Consist.xlsm` y
`vascor_sm_db.xlsx`. No se abrió Excel, no se ejecutaron macros, no se
recalcularon fórmulas y no se guardó sobre los archivos.

| Archivo | Bytes | Modificación | SHA-256 inicial |
|---|---:|---|---|
| `Plantilla_Consist.xlsm` | 2,028,130 | 2026-07-25 04:05:10 -06:00 | `6a1ec3f03a0ca42374e3764771f80b2afcb78addaa400a05da4386f841ced5cb` |
| `vascor_sm_db.xlsx` | 92,261 | 2026-07-28 05:08:58 -06:00 | `42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e` |

Ambos paquetes ZIP/OpenXML son íntegros. Los tres archivos reales que
formarían un lote independiente no están disponibles en el repositorio:
`Vehicle Load Report`, `Shippers` y `CNACS`. Por ello no puede construirse
todavía un golden master independiente. Los datos incrustados en la plantilla
se usaron sólo para caracterizar el proceso.

## Inventario de la plantilla

| Orden | Hoja | Estado | Dimensión declarada | Filas con contenido | Fórmulas | Función |
|---:|---|---|---|---:|---:|---|
| 1 | `Consist` | visible | A1:AR8426 | 657 | 4,992 | Resultado por VIN y controles auxiliares |
| 2 | `Summary` | visible | A1:V8989 | 8,426 | 33,700 | Resumen por plataforma |
| 3 | `  Vehicle Load Report` | visible | A1:O625 | 625 | 0 | Entrada naranja, conserva dos espacios iniciales |
| 4 | `Shippers` | visible | A1:AN817 | 625 útiles | 0 | Entrada naranja |
| 5 | `CNACS` | visible | A1:V26604 | 649 útiles | 0 | Entrada naranja |
| 6 | `Version` | hidden | A1:C2 | 2 | 0 | Versión, detalle y fecha |

No hay hojas `veryHidden`. Hay dos tablas de consulta, dos conexiones ODBC a
Excel, dos QueryTables, ocho tablas dinámicas, tres cachés de pivote, un enlace
externo al libro maestro, un control ActiveX y un proyecto VBA.

## Flujo reconstruido

1. El usuario pega o carga los tres reportes en sus hojas naranjas.
2. La conexión **Consulta desde Excel Files** consulta la propia hoja
   `  Vehicle Load Report`, filtra `fdwholevin Like '3C%'` y ordena por
   `fdTrack`. Su resultado abastece A:O de `Consist`; ocho columnas quedan
   enlazadas directamente y siete son columnas calculadas.
3. Las fórmulas de `Consist` enriquecen cada VIN con Vehicle Load, Shippers,
   CNACS y las columnas A:D de `[1]route_codes`.
4. La conexión **Consulta desde Excel Files1** consulta A, E, F y J de
   `Consist`, ordena por `fdTrack` y abastece la tabla A:H de `Summary`.
5. Las fórmulas de `Summary` recuperan Carrier, destino y Shipper desde
   `Consist`, y cuentan unidades por `fdTransportationName1`.
6. Las tablas dinámicas hacen verificaciones laterales. Cinco están en
   `Consist` (R:S, U:V, X:Y, AA:AB y AD:AE) y tres en `Summary` (J:K).
7. El botón ActiveX **Borrar Espacios** dispara VBA; el alcance exacto de su
   transformación queda pendiente de extraer el código fuente comprimido.

## Volúmenes y regla VIN observada

- Vehicle Load: 624 VIN, 624 únicos, sin duplicados.
- Shippers: 624 VIN, 624 únicos; la dimensión incluye 192 filas sin VIN.
- CNACS: 648 VIN, 624 únicos; existen 24 ocurrencias adicionales distribuidas
  en 24 VIN duplicados.
- El SQL legado filtra Vehicle Load por prefijo `3C`; no se ha probado que sea
  una regla universal y no debe codificarse sin validación operativa.
- La plantilla usa `VLOOKUP`, por lo que ante duplicados toma la primera
  coincidencia. Los 24 duplicados CNACS no deben eliminarse automáticamente:
  pueden representar información de negocio legítima.

## Dependencias externas y cálculo

La plantilla depende de rutas históricas `Q:\Consist\Consist Rail` tanto para
consultarse a sí misma mediante el driver ODBC de Excel como para enlazar el
catálogo maestro. El enlace guarda copia en caché de seis hojas. Esto explica
por qué el libro puede mostrar resultados aunque la unidad Q no esté accesible,
pero una actualización real puede fallar o conservar información obsoleta.

La configuración de cálculo sólo declara `calcId=191028`; no fuerza cálculo
manual ni cálculo completo. Las conexiones permiten actualización en segundo
plano y guardan datos. Existe riesgo de carrera entre refresh, fórmulas,
pivotes y envío del archivo.

## Comparación con VASCOR OPS actual

La implementación actual:

- detecta identidad y encabezado de los tres archivos;
- normaliza VIN en mayúsculas y recorre por bloques;
- contabiliza vacíos y duplicados;
- cruza presencia por VIN entre los tres orígenes;
- marca como no elegible cualquier VIN duplicado;
- conserva los datos originales por fuente en el borrador.

Todavía no reproduce:

- el filtro `3C%`, orden por `fdTrack` ni agrupación de plataformas;
- las siete reglas de enriquecimiento de `Consist`;
- el catálogo externo y la excepción `Carrier=KCSM → Laredo`;
- los cuatro campos derivados de `Summary`;
- las verificaciones de tablas dinámicas;
- las macros de limpieza/copia;
- la semántica de múltiples filas CNACS.

La regla actual que bloquea todo VIN duplicado es más estricta que Excel, que
usa la primera coincidencia. No debe cambiarse aún: requiere decisión de
negocio y un golden master.

## Primer flujo que puede implementarse con seguridad

Sin generar todavía un archivo productivo:

1. importar las tres fuentes a DTO inmutables;
2. preservar todas las filas y su posición original;
3. normalizar únicamente claves comparables, conservando el valor original;
4. reproducir el conjunto base de Vehicle Load con filtro y orden
   configurables;
5. resolver cada columna mediante reglas explícitas y catálogos versionados;
6. emitir un resultado diagnóstico con procedencia y advertencias;
7. comparar contra un golden master antes de exportar Excel.

## Elementos sin explicación completa

- Código fuente y orden real de ejecución de `Borrar_Espaacios`,
  `Borrar_Naranjas`, `Copiar_VIN` y `CommandButton1_Click`.
- Motivo de las filas preextendidas hasta 8,426 en `Summary`.
- Semántica de todos los pivotes y si son controles obligatorios.
- Criterio operativo para elegir entre `route_codes`, `route_codes_truck`,
  `route_codes_tren` y `No Activas`.
- Identidad correcta cuando un VIN tiene dos filas CNACS.
- Uso productivo de `production_status`.
- Tres archivos reales del mismo lote y el resultado final aprobado con el que
  construir el golden master.

## Cierre de la segunda etapa

- La divergencia C/D quedó resuelta: son físicamente `Market` y
  `Shipping Destination`; `Bloque` y `Cruce` son etiquetas históricas.
- Se recuperó un golden master completo de Consist: 624 filas.
- Summary sólo conserva 472 filas/59 plataformas; está 152 unidades por detrás
  del Consist actual.
- El VBA fuente y p-code fueron recuperados y concuerdan. No ordena ni
  enriquece el Consist; sólo limpia espacios, copia una columna o borra hojas.
- Los 24 duplicados CNACS sólo difieren en `NumRemesa` y no alteran el
  Pedimento recuperado por Consist.

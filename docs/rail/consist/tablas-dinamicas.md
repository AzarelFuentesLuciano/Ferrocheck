# Tablas dinámicas

## Cachés

| Caché lógico | Fuente | Campos | recordCount guardado |
|---:|---|---|---:|
| 373 / definición 1 | Consist A:K | 11 campos | 7,856 |
| 374 / definición 2 | Summary A:H | 8 campos | 8,989 |
| 375 / definición 3 | Summary A:F | 6 campos | 8,432 |

Los rangos fuente se declaran hasta la fila 1,048,576. `refreshOnLoad` y
`missingItemsLimit` no están declarados; `saveData` no está desactivado. Los
recordCount prueban que las cachés pertenecen a estados anteriores y no al
lote actual de 624 filas.

## Definiciones

| Hoja | Pivot | Salida | Filas | Valor |
|---|---|---|---|---|
| Consist | TablaDinámica1 | R11:S88 | Cruce, Shipper | Conteo de VIN |
| Consist | TablaDinámica2 | U11:V72 | Plataforma | Conteo de VIN |
| Consist | TablaDinámica3 | X11:Y132 | Plataforma, CNACS-Pedimento | Conteo de VIN |
| Consist | TablaDinámica4 | AA11:AB132 | Plataforma, Bloque | Conteo de VIN |
| Consist | TablaDinámica21 | AD11:AE88 | Cruce, Shipper | Conteo de VIN |
| Summary | TablaDinámica1 | J1:K17 | Destino | Conteo de plataforma |
| Summary | Tabla dinámica1 | J42:K43 | Bloque | Conteo de Bloque |
| Summary | Tabla dinámica2 | J61:K78 | Cruce | Conteo de plataforma |

No hay campos de columnas ni filtros de página. Los valores son conteos. Sólo
se observan ordenamientos explícitos en algunos campos: destino ascendente y
un par de campos auxiliares descendentes.

## Papel operativo

Las cinco tablas en Consist son controles laterales: plataforma, pedimento,
mercado/bloque, destino/cruce y shipper. Las tres de Summary son resúmenes de
destino, mercado y cruce. Ninguna alimenta las columnas A:O de Consist ni A:H
de Summary.

Los resultados cacheados de pivote no son confiables como golden master del
lote actual debido a sus recordCount históricos. Summary depende de su
QueryTable y fórmulas guardadas, no de los resultados de los pivotes.

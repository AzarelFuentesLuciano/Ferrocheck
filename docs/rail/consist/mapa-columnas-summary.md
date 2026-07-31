# Mapa de columnas de Summary

La tabla `Tabla_Consulta_desde_Excel_Files3` ocupa A1:H8426. Sólo 472
plataformas tienen valor cacheado en las columnas directas, mientras las
fórmulas están preextendidas a 8,425 filas.

| Col. | Salida | Fuente o regla |
|---|---|---|
| A | `fdTransportationName1` | Consulta de `Consist`, agrupador de plataforma |
| B | `Carrier` | VLOOKUP de A en `Consist!A:D`, índice 4 |
| C | `Bloque` | Consulta de `Consist` |
| D | `fdTrack` | Consulta de `Consist`; criterio de orden |
| E | `fdDestinationLocation` | VLOOKUP de A en `Consist!A:I`, índice 9 |
| F | `Cruce` | Consulta de `Consist` |
| G | `Shippers` | VLOOKUP de A en `Consist!A:G`, índice 7; `Pending` si vacío |
| H | `UT` | COUNTIF de plataforma en la columna A de `Consist` |

Las columnas J:K contienen salidas de tablas dinámicas, no columnas del
Summary contractual. Las fórmulas B, E, G y H dependen de la primera
coincidencia o del conteo total de la plataforma; si una plataforma mezcla
Carrier, bloque, destino, cruce o Shipper, Excel oculta esa discrepancia.

## Trazabilidad

`Summary.A/C/D/F` ← conexión SQL sobre `Consist.A/E/F/J`.

`Summary.B/E/G` ← primera fila coincidente de la plataforma en `Consist`.

`Summary.H` ← número de filas/VIN de la plataforma en `Consist`.

No se observó una regla explícita de deduplicación de plataformas en el SQL.
El resultado cacheado tiene 472 filas, pero sólo 59 plataformas repetidas ocho
veces. Consist contiene 78 plataformas/624 unidades. Summary es por tanto un
snapshot parcial con una diferencia comprobada de 19 plataformas/152 unidades.

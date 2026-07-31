# Resolución de la divergencia del catálogo

## Conclusión

La plantilla consulta físicamente las columnas C y D de la hoja
`route_codes`. En el libro maestro entregado esas columnas son `Market` y
`Shipping Destination`. Los valores cacheados de las 624 filas de `Consist`
coinciden exactamente con esas columnas actuales.

La divergencia está en las etiquetas históricas de salida:

- `Consist!E` se llama `Bloque`, pero contiene `Market`;
- `Consist!J` se llama `Cruce`, pero contiene `Shipping Destination`, salvo la
  rama explícita `Carrier="KCSM" → "Laredo"`.

Nivel de certeza: **alto**. Coincidencia comprobada: 624/624 filas.

## Evidencia del enlace

`externalLink1.xml` registra seis hojas, pero sólo contiene caché para
`route_codes`: 437 filas, incluidos encabezados:

1. `Route Code`
2. `Route King`
3. `Market`
4. `Shipping Destination`

Las otras cinco hojas tienen cero filas cacheadas en el enlace. No existe
tabla nombrada, QueryTable ni consulta que reordene el catálogo.

## Matriz de resolución

| Regla | Hoja/celda | Archivo externo | Hoja | Columna física | Encabezado actual | Encabezado esperado por salida | Valor almacenado | Conclusión | Certeza |
|---|---|---|---|---|---|---|---|---|---|
| `VLOOKUP(Route Code,[1]route_codes!A:C,3,0)` | Consist E2:E625 | `vascor_sm_db.xlsx` | route_codes | C | Market | Bloque | Coincide 624/624 con C | Etiqueta de salida obsoleta | Alta |
| `VLOOKUP(Route Code,[1]route_codes!A:D,4,0)` | Consist J2:J625 | `vascor_sm_db.xlsx` | route_codes | D | Shipping Destination | Cruce | Coincide 624/624 con D o excepción KCSM | Etiqueta de salida obsoleta | Alta |
| Enlace externo cacheado | `externalLink1.xml` | libro maestro | route_codes | A:D | Route Code, Route King, Market, Shipping Destination | No aplica | 437 filas cacheadas | Esquema actual ya estaba en el enlace | Alta |
| QueryTables | Consist/Summary | propia plantilla | Vehicle/Consist | No aplica | No consultan maestro | No aplica | No transforman catálogo | Descarta reordenamiento por consulta | Alta |
| Otras hojas | enlace externo | libro maestro | truck/tren/status/No Activas/version | Sin caché | Varios | No aplica | Cero filas cacheadas | No intervienen en estas dos fórmulas visibles | Media-alta |

El detalle por código de ruta está en
`golden-master/catalog-resolution.json`. No se renombró ni corrigió ninguna
columna.

# Golden master recuperado de Plantilla_Consist.xlsm

## Entradas

- Vehicle Load: 624 registros, 624 VIN únicos.
- Shippers: 624 registros, 624 VIN únicos.
- CNACS: 648 registros, 624 VIN únicos y 24 ocurrencias duplicadas.
- Los tres conjuntos contienen los mismos 624 VIN únicos.

## Consist

- 624 filas de salida, 624 VIN únicos.
- Todos los VIN pertenecen al conjunto de entrada.
- 78 plataformas, exactamente 8 unidades por plataforma.
- 74 valores de `fdTrack`: 70 con 8 unidades y 4 con 16.
- Las 4,992 fórmulas relevantes tienen valor cacheado.
- No hay errores Excel cacheados, filas ocultas, columnas ocultas ni celdas
  combinadas en A:O.

El orden guardado es monotónico por `fdTrack`, coherente con el SQL
`ORDER BY fdTrack`. Dentro de empates no existe segundo criterio en la
consulta. Sólo 3 de 74 grupos conservan el orden físico original; por tanto el
orden exacto dentro del empate sólo puede tratarse como evidencia cacheada, no
como una regla determinista adicional.

## Summary

- 472 filas cacheadas.
- 59 plataformas únicas.
- Cada plataforma se repite 8 veces y cada fila presenta `UT=8`.
- Total bruto de UT sobre filas repetidas: 3,776.
- Total reconciliado tomando una fila por plataforma: 472.
- Consist tiene 624 unidades; diferencia: 152 unidades, equivalentes a 19
  plataformas de 8.

Conclusión: Summary es un snapshot parcial/desactualizado respecto del Consist
actual. Puede conservarse como golden master parcial de 59 plataformas, pero
no como salida completa esperada. No se inventaron las 19 plataformas
ausentes.

## Artefactos

- `inputs-summary.json`: conteos de entradas.
- `consist-output.json`: celda, fórmula, valor cacheado, tipo, estilo, formato
  y encabezado de A:O.
- `summary-output.json`: la misma evidencia para A:H de filas recuperables.
- `vin-order.json`: posiciones comparadas en las cuatro hojas.
- `duplicate-analysis.json`: los 24 casos CNACS.
- `catalog-resolution.json`: resolución por route code.
- `consistency.json`: cifras reconciliadas.

Los JSON se generaron directamente desde XML; no se recalculó el workbook.

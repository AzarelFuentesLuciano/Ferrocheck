# Reglas y catálogos

## Inventario del maestro

| Hoja | Estado | Registros | Encabezados |
|---|---|---:|---|
| `route_codes` | visible | 436 | Route Code, Route King, Market, Shipping Destination, Carrier, Heavy Duty, USA/Canada, Production Plant, RC-Plant, Load by, Border crossing |
| `route_codes_truck` | hidden | 281 útiles + 4 vacíos | RC, Route Code, Bloque, Punto de Cruce, Mercado, Route Description, Carrier, Heavy Duty, Old Route Description, USA/Canada, Modelo, Ruta y Modelo, Load by, Punto de Cruce2 |
| `route_codes_tren` | hidden | 356 | Los mismos 14 campos |
| `production_status` | visible | 49 | Estatus, Estatus Base |
| `No Activas` | hidden | 21 | Los mismos 14 campos de rutas |
| `v28052026` | hidden | 0 | Sin contenido |

## Claves aparentes y calidad

- `route_codes.Route Code`: 284 valores únicos entre 436 filas. No es clave
  única por sí sola; la repetición parece responder a planta/modelo/medio.
- `route_codes.RC-Plant`: 103 únicos y 2 vacíos. Tampoco es clave única.
- `route_codes_truck.RC`: 204 únicos entre 281 filas útiles.
- `route_codes_tren.RC`: 252 únicos entre 356 filas.
- `No Activas.RC`: 21 únicos; identifica sus filas, pero son rutas retiradas.
- `production_status.Estatus`: 48 únicos en 49 filas; hay una duplicidad.

Las hojas truck y tren calculan `Ruta y Modelo` principalmente concatenando
`Route Code` y `Modelo`. Existen espacios no separables y espacios iniciales;
una normalización futura debe registrar el valor original y detectar colisiones
antes de recortar.

## Uso demostrado por la plantilla

La única dependencia directa encontrada en las fórmulas productivas es
`route_codes!A:D`:

- A: clave `Route Code`;
- C: `Bloque`;
- D: `Punto de Cruce` según la copia externa cacheada.

El archivo maestro actual llama C `Market` y D `Shipping Destination`. La
segunda etapa comprobó que la copia enlazada usaba esos mismos encabezados y
que 624/624 valores de salida coinciden. La divergencia no es de versión:
`Bloque` y `Cruce` son encabezados históricos de Consist para valores que
semánticamente son mercado y destino.

`route_codes_truck`, `route_codes_tren`, `production_status` y `No Activas`
están presentes en el enlace externo, pero no aparecen referenciadas por las
fórmulas visibles de Consist/Summary. Su participación por macro o proceso
manual queda sin resolver.

## Regla futura recomendada

Crear catálogos versionados con:

- clave compuesta explícita;
- vigencia y estado activo;
- medio (`rail`, `truck`, mixto);
- planta/modelo;
- bloque, destino, carrier y cruce como campos nominales;
- fuente, fecha de importación y checksum;
- resolución determinista y reporte de ambigüedad.

Nunca resolver por posición de columna ni tomar silenciosamente la primera de
varias coincidencias.

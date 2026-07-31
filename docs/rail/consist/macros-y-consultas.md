# Macros, consultas y pivotes

## Consultas

### Consulta desde Excel Files

Origen: la propia `Plantilla_Consist.xlsm`, hoja
`  Vehicle Load Report`. Selecciona plataforma, VIN, campos personalizados,
código de ruta del fabricante, Load ID y track; filtra VIN con prefijo `3C` y
ordena por track. La QueryTable escribe en la tabla A:O de `Consist`, donde
varias columnas son no enlazadas y contienen fórmulas.

### Consulta desde Excel Files1

Origen: `Consist`. Selecciona plataforma, bloque, track y cruce, y ordena por
track. La QueryTable escribe en A:H de `Summary`; Carrier, destino, Shippers y
UT son columnas calculadas.

Ambas conexiones:

- usan el driver ODBC `Excel Files`;
- apuntan a una unidad Q histórica;
- permiten refresh en segundo plano;
- conservan datos cacheados;
- no son Power Query/M: no se encontraron partes `customXml` o Mashup con una
  definición M utilizable.

## VBA y controles

Existe `xl/vbaProject.bin` (33,280 bytes) y un CommandButton ActiveX rotulado
**Borrar Espacios**. La inspección binaria estática encontró estos símbolos:

- `Consist_Rail`;
- `Borrar_Espaacios`;
- `Borrar_Naranjas`;
- `Copiar_VIN`;
- `CommandButton1_Click`;
- `ThisWorkbook`.

La segunda etapa extrajo estáticamente fuente y p-code con `olevba` y
`pcodedmp`. Ambos concuerdan. El detalle íntegro está en `vba-extraido.md`.
No se ejecutó VBA.

## Tablas dinámicas

| Hoja | Nombre | Rango |
|---|---|---|
| Consist | TablaDinámica1 | R11:S88 |
| Consist | TablaDinámica2 | U11:V72 |
| Consist | TablaDinámica3 | X11:Y132 |
| Consist | TablaDinámica4 | AA11:AB132 |
| Consist | TablaDinámica21 | AD11:AE88 |
| Summary | TablaDinámica1 | J1:K17 |
| Summary | Tabla dinámica1 | J42:K43 |
| Summary | Tabla dinámica2 | J61:K78 |

Hay tres cachés: `Consist!A:K`, `Summary!A:H` y `Summary!A:F`, declarados hasta
la fila 1,048,576. El texto visible en la plantilla indica controles de carga
por shipper, plataforma, plataforma/pedimento y bloque. Falta validar cuáles
son bloqueantes y cuáles son sólo apoyo visual.

## Dependencias y riesgo

- El enlace externo contiene una copia cacheada del maestro y tres rutas
  alternativas (absoluta/relativa/histórica).
- La plantilla puede producir resultados distintos según disponibilidad de Q,
  orden de refresh y versión del maestro.
- Los pivotes pueden permanecer obsoletos aunque las fórmulas se recalculen.
- El botón de limpieza puede alterar entradas o resultados; su efecto no debe
  reemplazarse por una normalización especulativa.

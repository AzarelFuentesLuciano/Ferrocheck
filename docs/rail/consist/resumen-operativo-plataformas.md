# Resumen operativo de plataformas

## Fuente y reglas

- **Total de Plataformas Cargadas:** valores únicos, no vacíos y normalizados de
  `Vehicle Load Report.fdTransportationName1`.
- **Plataformas Pendientes de Confirmar:** plataformas únicas de
  `Consist.fdTransportationName1` cuya columna `CNACS-Pedimento` está vacía o
  contiene uno de los marcadores verificados: `Pending` o
  `Falta agregar este VIN en hoja de CNACS`.
- **Plataformas Confirmadas:** total cargadas menos pendientes. El resultado
  nunca es negativo. Si pendientes supera el total, el sistema conserva los
  conteos originales y registra una inconsistencia visible.

La comparación ignora mayúsculas/minúsculas, espacios al inicio o final y
secuencias repetidas de espacios. Varios VIN de una misma plataforma cuentan
una sola vez.

## Encabezados de plataforma

El encabezado canónico es `fdTransportationName1`. Para tolerar archivos
operativos equivalentes se admiten también:

- `fd Transportation Name 1`
- `Transportation Name 1`
- `Plataforma`
- `Numero de plataforma`
- `Número de plataforma`

Las filas sin plataforma no se cuentan.

## Persistencia

El total se obtiene antes de descartar unidades incompletas, directamente del
Vehicle Load procesado. Como una unidad sin Shippers o CNACS puede no quedar en
`rail_consist_units`, el resumen no puede reconstruirse con certeza únicamente
desde esas unidades. La migración 018 agrega
`rail_consists.operational_summary_json` como snapshot trazable del cálculo.

## Columna histórica Bloque

La plantilla histórica mostraba `Bloque` en `Consist!E` y `Summary!C`. Las
salidas operativas actuales no la incluyen. El dato se conserva internamente
como `market` y en la trazabilidad para no afectar reglas ni diagnóstico.

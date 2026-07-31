# Plan de implementación

Este documento conserva la secuencia utilizada para implementar el generador.
La versión productiva descrita por este plan ya está implementada y validada
localmente; las notas de evidencia incompleta se mantienen como contexto
histórico de la ingeniería inversa.

## Fase 0 — evidencia reproducible

Obtener un lote real completo y aprobado:

1. Vehicle Load Report original;
2. Shippers original;
3. CNACS original;
4. Consist final aprobado;
5. Summary final aprobado;
6. versión exacta del maestro utilizada.

Registrar hashes, fechas y una expectativa por columna. Confirmar con negocio
la semántica de los 24 duplicados CNACS.

Estado: parcialmente cerrada. La plantilla permitió recuperar un golden master
completo de Consist y uno parcial de Summary. Siguen faltando un Summary final
aprobado de 624 unidades y la confirmación operativa de `NumRemesa`.

## Fase 1 — analizador puro

- DTO por fuente y por fila, sin perder duplicados.
- Normalización reversible de encabezados, VIN y claves.
- Índices multivalor por VIN/plataforma.
- Catálogo versionado con resolución explícita.
- Motor de reglas que devuelva valor, procedencia, regla y advertencias.
- Reporte de diferencias contra el golden master.

## Fase 2 — equivalencia de Consist

Implementar las quince columnas en el orden documentado, empezando por las
ocho directas y después las siete derivadas. Reproducir y parametrizar filtro
`3C%`, orden por track, fallbacks y excepción KCSM. No ocultar ambigüedades con
“primera coincidencia” sin una regla aprobada.

Reglas cerradas: 15 columnas, catálogo físico C/D, orden primario por fdTrack,
excepción KCSM y comportamiento VLOOKUP ante duplicados. Pendiente: criterio
determinista para empates de fdTrack.

## Fase 3 — equivalencia de Summary

Definir la lista única de plataformas, sus reglas de orden y consistencia.
Generar las ocho columnas y controles equivalentes a los pivotes.

Bloqueada parcialmente: el caché sólo cubre 59 de 78 plataformas. No debe
aceptarse como golden master completo.

## Fase 4 — exportador

Crear un libro nuevo desde una copia controlada o una plantilla sin datos.
Nunca editar los originales. Generar fórmulas o valores según el contrato,
preservar formato sólo donde sea necesario y producir checksum del resultado.

## Fase 5 — integración gradual

- feature flag local;
- modo diagnóstico sin persistencia;
- comparación automatizada de golden master;
- descarga separada del flujo estable;
- auditoría, permisos, límites y borrado seguro de temporales;
- activación sólo tras aceptación operativa.

## Criterios de aceptación

- equivalencia exacta de columnas para el golden master;
- ninguna pérdida silenciosa de duplicados;
- resultado determinista sin unidad Q ni Excel instalado;
- trazabilidad de todo valor;
- cero cambios al flujo actual durante el diagnóstico;
- pruebas de tamaño, encabezados, orden, vacíos, ambigüedades y catálogos
  desactualizados.

## Herramienta diagnóstica

`tools/rail/inspect_consist_workbooks.py` inspecciona ZIP/OpenXML en modo de
solo lectura y emite JSON estructural. Uso:

```powershell
python tools\rail\inspect_consist_workbooks.py `
  docs\rail\consist\referencias\Plantilla_Consist.xlsm `
  docs\rail\consist\referencias\vascor_sm_db.xlsx `
  --output "$env:TEMP\vascor-consist-inspection.json"
```

La herramienta no evalúa fórmulas, no refresca conexiones y no ejecuta VBA.

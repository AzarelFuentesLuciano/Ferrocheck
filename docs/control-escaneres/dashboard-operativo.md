# Dashboard operativo de Control de Escáneres

## Objetivo y usuarios

Ofrece a supervisores una lectura rápida y accionable del inventario, custodia, incidencias y actividad persistida del módulo. No es el Dashboard ejecutivo global y no combina datos de FerroCheck ni de otros módulos.

## Arquitectura

`HTTP → ScannerDashboardController → ScannerDashboardQueryInterface → PdoScannerDashboardQuery → DTO de lectura → ScannerDashboardViewModelFactory → ViewModels → vista`.

La consulta usa agregados y límites; no carga expedientes, evidencias ni payloads de auditoría. La vista no conoce PDO, repositorios, SQL ni entidades.

## Definiciones y fuentes

| Métrica | Fuente y definición | Periodo | Acción |
|---|---|---|---|
| Total | Todos los registros de `scanners`, incluidos inactivos | Actual | Catálogo |
| Activos | `scanners.activo = 1`, sin depender del estado | Actual | Catálogo |
| Inactivos | `scanners.activo = 0` | Actual | Catálogo |
| Disponibles | Activo, estado `disponible` y sin movimiento `abierto`, `vencido` o `con_incidencia` | Actual | Catálogo filtrado |
| Entregados | Estado canónico del escáner `entregado` | Actual | Catálogo filtrado |
| Mantenimiento | Estado canónico `mantenimiento` | Actual | Catálogo filtrado |
| Incidencias abiertas | Incidencias cuyo estado no es `resuelta` ni `descartada` | Actual | Catálogo con incidencia |
| Equipos afectados | Escáneres distintos con al menos una incidencia abierta | Actual | Catálogo con incidencia |
| Críticas | Incidencias abiertas con severidad `critica` | Actual | Expediente |
| Entregas | Movimientos cuyo `entregado_at` cae en el rango | Rango | Expediente |
| Recepciones | Movimientos cuyo `recibido_at` cae en el rango | Rango | Expediente |

Los porcentajes de estado usan el total canónico como denominador y devuelven cero cuando no hay inventario.

## Rangos y zona horaria

La allowlist admite `today`, `7d` y `30d`; cualquier valor inválido vuelve a `today`. El inicio es medianoche del reloj de negocio y el final es `BusinessClock::now()`. No se usa la zona horaria del navegador.

## Atención requerida

Incluye solamente incidencia crítica abierta, estado `extraviado` y estado `pendiente_reparacion`. Se deduplica por escáner y se prioriza crítica, alta y media. No se implementan mantenimiento prolongado ni movimientos vencidos por antigüedad porque el proyecto no tiene una política temporal aprobada.

## Actividad y tendencia

La actividad combina entregas, recepciones e incidencias reportadas, se ordena de forma descendente y se limita a diez elementos. La tendencia presenta conteos diarios para esas mismas fuentes y siempre ofrece una tabla textual. Si todos los valores son cero, no se fabrica una visualización.

## Rendimiento

Se emplean conteos agregados, `COUNT(DISTINCT scanner_id)`, agrupación por estado y consultas acotadas. Los índices actuales cubren estado/activo, fechas de entrega y fecha/estado de incidencias. Una evolución futura debería evaluar un índice sobre `scanner_movimientos(recibido_at)` y otro compuesto para actividad por rango; no se modifica el esquema en esta fase.

## Seguridad y errores

No se recuperan ni muestran PIN, PUK, IMEI, ICCID, teléfono, IP, fingerprints, sesión, SQL o payloads. Los errores técnicos se registran por clase y la interfaz muestra mensajes neutros. No se ejecutan migraciones automáticamente.

## Limitaciones y evolución

El esquema no conserva historial completo de transiciones, por lo que no es posible calcular tiempo fuera de servicio, mantenimiento prolongado ni cambios de severidad con precisión. Esas métricas requieren diseño y política futura. También quedan fuera actores nominales y Dashboard ejecutivo global.

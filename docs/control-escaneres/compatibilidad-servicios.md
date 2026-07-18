# Compatibilidad arquitectónica para servicios
La Fase 3.5 completó la matriz entre migraciones, DTO, entidades, contratos y PDO. Movimientos, inspecciones e incidencias ahora representan todos los campos persistidos. Evidencias soportan relaciones con escáner, movimiento, inspección e incidencia.

## Matriz de trazabilidad

| Migración/campos | DTO | Entidad | Contrato/PDO | Prueba |
|---|---|---|---|---|
| `scanner_movimientos`, custodia, fechas, duración y actores | `ScannerMovementCreateData`, `ScannerReceptionData` | `ScannerMovement` | `ScannerMovementRepositoryInterface` / `PdoScannerMovementRepository` | duración y mapping completo |
| `scanner_inspecciones`, condición, firmas y actor | `ScannerInspectionCreateData` | `ScannerInspection` | `ScannerInspectionRepositoryInterface` / PDO | consultas por scanner/movimiento |
| `scanner_inspeccion_detalles` | `ScannerInspectionDetailData` | detalle tipado por DTO | repositorio de inspecciones | detalles y comparación de dominio |
| `scanner_incidencias`, severidad y resolución | DTO de creación, resolución y severidad | `ScannerIncident` | repositorio de incidencias / PDO | cambio de severidad |
| `scanner_evidencias`, cuatro relaciones | `ScannerEvidenceMetadata` | metadata inmutable | repositorio de evidencias / PDO | consultas por cada relación |
| `auditoria_eventos`, correlación y JSON | `AuditEventData` | `AuditEvent` | repositorios separados append/query | request y lectura |
| agregados operativos | `DashboardSummary`, métricas y timeline | modelos existentes | query ports Dashboard/History | consultas SQLite compatibles |

## Command/Query
Los repositorios transaccionales conservan comandos y lecturas de agregado. Dashboard, historial y lectura de auditoría usan puertos separados. `AuditRepositoryInterface` continúa siendo exclusivamente append-only.

## Puertos compartidos
`TransactionManagerInterface` desacopla casos de uso de PDO. `BusinessClockInterface` evita fechas no deterministas; producción usa `SystemBusinessClock` y pruebas `FrozenBusinessClock`. `ScannerStateMachine` centraliza transiciones. `InspectionComparisonService` centraliza la jerarquía excelente, bueno, regular, dañado, no funciona, faltante y no aplica; valores numéricos se comparan por magnitud y textos desconocidos quedan no comparables.

## Limitaciones
SQLite usa índice parcial en vez de columna generada y no prueba bloqueo `FOR UPDATE`. JSON se trata como TEXT. El esquema no conserva historial de cada transición de estado, por lo que el tiempo fuera de servicio no puede calcularse correctamente; el query port lanza una limitación explícita en vez de inventar cero. Percentiles exactos dependerán de la variante MariaDB objetivo.

Esta separación deja preparada la Fase 4 sin conectar frontend, rutas o endpoints.

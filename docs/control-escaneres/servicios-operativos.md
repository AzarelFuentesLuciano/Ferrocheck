# Servicios operativos de Control de Escáneres

## Alcance

La FASE 4 incorpora servicios de aplicación transaccionales para entrega, recepción, incidencias y mantenimiento. No activa rutas, controladores ni vistas y no altera el App Shell, FerroCheck o la base de datos desplegada.

## Contratos y límites

- Los servicios dependen de interfaces de repositorio, reloj, máquina de estados, comparador y administrador de transacciones.
- El actor autenticado y el contexto de solicitud se reciben separados del comando operativo. Los campos heredados de actor y folio en algunos DTO se conservan por compatibilidad, pero entrega genera un folio propio y persiste el actor explícito.
- `effectiveAt`, `resolvedAt` y estados finales opcionales existen para pruebas, importaciones controladas y procesos administrativos; en operación normal se usa el reloj de negocio.
- Evidencias se registran como metadatos ya almacenados. Estos servicios no reciben archivos ni escriben directamente en el filesystem.

## Entrega

Bloquea el escáner, valida actividad, disponibilidad, transición y ausencia de movimiento abierto. Genera un folio `MOV-YYYYMMDD-<16 hex>`, crea movimiento e inspección, relaciona evidencias, cambia el estado a `entregado` y audita dentro de una sola transacción.

## Recepción

Bloquea escáner y movimiento abierto, valida pertenencia y fecha, crea la inspección de recepción, compara detalles con la entrega y calcula duración en segundos. Si existe deterioro a `dañado`, `no funciona` o `faltante`, deja el equipo en `pendiente_reparacion`; en otro caso vuelve a `disponible`. Finalmente cierra el movimiento y audita.

## Incidencias

Permite reportar, cambiar severidad y resolver. La política puede llevar un equipo a `pendiente_reparacion` o `extraviado`; la resolución exige un estado final válido por la máquina de estados. Una incidencia cerrada no puede resolverse nuevamente. Evidencias, estado y auditoría comparten transacción.

## Mantenimiento

Impide operar equipos inactivos o con movimientos abiertos. `send` transita a `mantenimiento`; `return` requiere uno de los estados finales permitidos (`disponible`, `pendiente_reparacion` o `baja_definitiva`). El cambio y la auditoría son atómicos.

## Atomicidad, concurrencia y rollback

Las operaciones usan `lockScannerForUpdate` y, en recepción, `lockOpenMovementForUpdate`. Cualquier excepción revierte movimiento, inspección, evidencia, estado y auditoría. SQLite valida atomicidad en integración; MariaDB mantiene el bloqueo pesimista mediante la adaptación PDO existente.

## Deuda compatible conocida

Los DTO heredados incluían folio y actor dentro del comando. Se ampliaron únicamente con parámetros opcionales al final para no romper consumidores existentes. Una fase futura puede separar comandos de aplicación de DTO de persistencia cuando todos los adaptadores hayan migrado.

## Validación de persistencia de incidencias

La relación incidencia-movimiento fue corregida en el adaptador PDO: `movimiento_id` se persiste cuando el comando incluye `movementId` y permanece `NULL` cuando no existe. Las consultas por ID, escáner, movimiento y rango, así como los cambios de severidad y resolución, conservan la relación en el mapper.

La validación se ejecuta sobre SQLite efímero y usa SQL preparado compatible con PDO/MariaDB. No fue necesario modificar el esquema ni ejecutar migraciones sobre la base de datos productiva.

## Pruebas

`php tests/control-escaneres/run-services.php` valida entrega, folio, inspecciones, recepción con daño y duración, integración del servicio de incidencias con `movementId`, idempotencia de cierre, mantenimiento, auditoría y rollback. `php tests/control-escaneres/run-incident-persistence.php` cubre directamente el adaptador PDO, consultas, valores nulos, actualizaciones y rollback. Ambas suites usan SQLite efímero y no acceden a la base de datos productiva.

## Integración web interna

La composición se realiza en `ControlEscaneresServiceFactory` y la frontera HTTP en `ControlEscaneresWebController`. El catálogo usa una query dedicada y ViewModels; los formularios operativos construyen DTO y aplican CSRF, actor de sesión y POST/Redirect/GET. El expediente transforma historial y auditoría a datos seguros de presentación.

No existe todavía un módulo completo de usuarios. `SessionAuthenticatedActorProvider` exige un `user_id` previamente confiable en sesión y falla si falta; no hay actor de desarrollo activado automáticamente. La interfaz muestra un error amigable cuando el esquema canónico aún no existe y nunca ejecuta migraciones por cuenta propia.

# Arquitectura de Control de Escáneres

## Propósito y alcance

Control de Escáneres administra el catálogo técnico, la custodia, las inspecciones, las evidencias, las incidencias y la trazabilidad operativa de los equipos. El módulo permanece aislado de FerroCheck y utilizará namespaces, vistas y assets propios.

Esta fase define persistencia y contratos. No activa formularios, endpoints, autenticación, importaciones ni escritura de datos.

## Convención de códigos

La única convención válida para nuevos escáneres será `SC-0001`, con prefijo `SC-` y cuatro dígitos como mínimo. Los códigos son identificadores visibles inmutables; el `id` numérico continúa siendo la clave interna. Cualquier dato legacy `ESC-*` requerirá una migración de conciliación explícita antes de incorporarse.

## Entidades y relaciones

- `scanners` es la raíz del agregado y conserva identidad, estado y baja lógica.
- `scanner_movimientos` representa un ciclo de entrega y recepción. Un escáner sólo puede tener un movimiento abierto.
- `scanner_inspecciones` conserva por separado la condición de entrega y recepción.
- `scanner_inspeccion_detalles` registra la condición de cada componente inspeccionado.
- `scanner_evidencias` guarda metadatos y rutas; nunca almacena el binario.
- `scanner_incidencias` registra daños, pérdidas, anomalías y su resolución.
- `auditoria_eventos` es append-only y registra acciones del sistema.

Relaciones principales:

```text
scanners 1 ── N scanner_movimientos
scanners 1 ── N scanner_inspecciones
scanner_movimientos 1 ── N scanner_inspecciones
scanner_inspecciones 1 ── N scanner_inspeccion_detalles
scanners/movimientos/inspecciones/incidencias 1 ── N scanner_evidencias
scanners 1 ── N scanner_incidencias
```

Las referencias de firma desde inspecciones hacia evidencias se agregan después de crear ambas tablas para evitar una dependencia circular durante el despliegue.

## Estados

Escáner: `disponible`, `entregado`, `mantenimiento`, `pendiente_reparacion`, `baja_definitiva`, `extraviado`.

Movimiento: `abierto`, `devuelto`, `vencido`, `con_incidencia`, `cancelado`.

Inspección: `entrega`, `recepcion`.

Incidencia: `abierta`, `en_revision`, `en_mantenimiento`, `resuelta`, `descartada`; severidad `baja`, `media`, `alta` o `critica`.

## Reglas de integridad

1. Código, QR, serie, IMEI e ICCID son únicos cuando existen.
2. El índice de conservación y la batería se limitan a 0–100; la calificación a 1–5.
3. Una clave generada nullable más un índice UNIQUE impide dos movimientos abiertos para el mismo escáner.
4. Cada movimiento admite una sola inspección de cada tipo.
5. Cada inspección admite un solo detalle por componente.
6. La recepción nunca sobrescribe la inspección de entrega.
7. Las bajas son lógicas y conservan la historia.
8. Entrega, inspección, cambio de estado y auditoría deberán ser transaccionales en la capa de servicio.
9. La base aporta restricciones; el servicio deberá además bloquear el escáner con `SELECT ... FOR UPDATE` y validar su estado.

## Trazabilidad de usuarios

Los nombres de personas físicas describen quién recibe o devuelve el equipo. Las columnas `created_by`, `updated_by`, `registrada_por`, `entrega_registrada_por` y equivalentes identifican al usuario autenticado que ejecuta la acción.

Mientras no exista el catálogo general de usuarios, estas columnas son nullable y no tienen clave foránea. El backend futuro deberá obtener el actor desde la sesión; nunca aceptará un identificador arbitrario enviado por el navegador.

## Campos sensibles

IMEI, ICCID y teléfono son datos sensibles y requieren autorización específica, enmascaramiento en vistas y auditoría de acceso. PIN, PUK, contraseñas, tokens, cookies y secretos quedan excluidos del esquema. `auditoria_eventos` tampoco puede recibirlos dentro de JSON o metadata.

## Auditoría

`auditoria_eventos` no tendrá flujos de UPDATE o DELETE en la aplicación. Cada mutación futura deberá registrar actor, acción, entidad, resultado, valores anterior/nuevo sanitizados, IP y correlación de request. La política de permisos de base de datos para append-only se implementará cuando existan usuarios técnicos separados.

## Métricas soportadas

Los timestamps e índices permiten calcular tiempos de entrega y recepción, duración de custodia, movimientos por turno/área/usuario, pendientes, vencimientos, incidencias, disponibilidad y tiempo fuera de servicio. No se almacenan cifras estimadas de ahorro; se calcularán a partir de una línea base real.

## Riesgos y decisiones pendientes

- Confirmar en cada ambiente si la migración legacy fue ejecutada antes de aplicar el esquema canónico.
- Definir tablas generales `usuarios` y `areas`; por ahora sus referencias son BIGINT nullable sin FK.
- Definir almacenamiento, retención, cifrado y borrado lógico de evidencias.
- Definir normalización o conciliación de códigos legacy `ESC-*`.
- Definir catálogo definitivo de tipos de incidencia y evidencia.
- Definir política de inmutabilidad de folios y correcciones administrativas.
- Confirmar compatibilidad del servidor objetivo con columnas generadas e índices UNIQUE.


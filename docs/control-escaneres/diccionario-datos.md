# Diccionario de datos de Control de Escáneres

Todas las tablas usan InnoDB, `utf8mb4` y timestamps en la zona horaria acordada por la aplicación. Las columnas de usuario son referencias futuras y permanecen nullable hasta crear el módulo de identidad.

## `scanners`

| Campo | Tipo | Nulo | Regla |
|---|---|---:|---|
| id | BIGINT UNSIGNED | no | PK autoincremental |
| codigo | VARCHAR(20) | no | UNIQUE, formato `SC-0001` |
| codigo_qr | VARCHAR(100) | no | UNIQUE |
| numero_serie | VARCHAR(120) | sí | UNIQUE cuando exista |
| imei | VARCHAR(20) | sí | UNIQUE, sensible |
| marca/modelo | VARCHAR(100/120) | no | Identificación técnica |
| telefono | VARCHAR(30) | sí | Sensible |
| iccid | VARCHAR(32) | sí | UNIQUE, sensible |
| area_id | BIGINT UNSIGNED | sí | FK futura a áreas |
| estado | VARCHAR(30) | no | Estado controlado |
| indice_conservacion | TINYINT UNSIGNED | sí | 0–100 |
| fotografia_oficial_id | BIGINT UNSIGNED | sí | FK añadida después a evidencias |
| activo | TINYINT(1) | no | Baja lógica |
| created_by/updated_by | BIGINT UNSIGNED | sí | FK futura a usuarios |
| created_at/updated_at | DATETIME(6) | no | Trazabilidad |
| deactivated_at | DATETIME(6) | sí | Fecha de baja lógica |

## `scanner_movimientos`

| Campo | Tipo | Regla |
|---|---|---|
| id | BIGINT UNSIGNED | PK |
| scanner_id | BIGINT UNSIGNED | FK a scanners |
| folio | VARCHAR(40) | UNIQUE |
| estado | VARCHAR(30) | Estado controlado |
| movimiento_abierto_scanner_id | BIGINT UNSIGNED generado | Scanner sólo cuando estado=`abierto`; UNIQUE |
| persona_entrega_nombre | VARCHAR(160) | Persona física |
| numero_empleado | VARCHAR(40) | Identificador laboral |
| area_id | BIGINT UNSIGNED nullable | FK futura |
| turno | VARCHAR(40) | Turno operativo |
| entregado_at/recibido_at/vence_at | DATETIME(6) | Ciclo de custodia |
| entrega_registrada_por/recepcion_registrada_por | BIGINT UNSIGNED nullable | Usuario de sistema futuro |
| devolucion_recibida_por_nombre | VARCHAR(160) nullable | Persona física |
| duracion_segundos | BIGINT UNSIGNED nullable | Métrica calculada |
| observaciones | TEXT nullable | Notas operativas |
| cancelado_por/cancelado_at/motivo_cancelacion | varios nullable | Trazabilidad de cancelación |
| created_at/updated_at | DATETIME(6) | Timestamps |

## `scanner_inspecciones`

Incluye `id`, `movimiento_id`, `scanner_id`, `tipo`, `bateria_porcentaje`, `calificacion`, `observaciones`, dos referencias nullable a evidencias de firma, `inspeccionada_at`, `registrada_por`, `created_at` y `updated_at`. UNIQUE `(movimiento_id, tipo)`.

## `scanner_inspeccion_detalles`

Incluye `id`, `inspeccion_id`, `componente`, `estado`, `valor_numerico`, `valor_texto`, `observaciones` y `created_at`. Componentes: batería, pantalla, touch, botones, lector, Wi-Fi, datos móviles y accesorios. UNIQUE `(inspeccion_id, componente)`.

## `scanner_evidencias`

Incluye `id`, referencias a escáner/movimiento/inspección/incidencia, `tipo`, `ruta_storage`, `mime_type`, `tamano_bytes`, `hash_sha256`, `capturada_at`, `registrada_por`, `activo`, `created_at` y `updated_at`. Guarda metadatos; el archivo permanece fuera de la base.

## `scanner_incidencias`

Incluye `id`, `scanner_id`, `movimiento_id`, `tipo`, `severidad`, `descripcion`, `estado`, `reportado_por_nombre`, `registrada_por`, `reportada_at`, `resolucion`, `resuelta_por`, `resuelta_at`, `created_at` y `updated_at`.

## `auditoria_eventos`

Incluye `id`, `usuario_id`, `accion`, `modulo`, `entidad`, `entidad_id`, `resultado`, tres documentos JSON nullable, `ip`, `request_id`, `session_fingerprint` y `created_at`. Es append-only y no almacena secretos.

## Índices de consulta

- Escáneres por estado/activo, área y actualización.
- Movimientos por escáner/estado, vencimiento, empleado, área, turno y actores.
- Inspecciones por escáner, tipo y fecha.
- Evidencias por cada entidad relacionada, hash y fecha.
- Incidencias por estado/severidad, escáner y fecha.
- Auditoría por entidad, usuario, acción, módulo, request y fecha.


# Activación local de Control de Escáneres

## Entorno

Activación realizada en XAMPP local, PHP 8.2.12, MariaDB 10.4.32, host `localhost`, puerto 3306, base de desarrollo `ferrocheck` y `APP_ENV=local`. No se utilizó acceso remoto.

## Preflight y respaldo

El preflight inicial informó `schema: absent` con exit code 1. Antes de escribir se creó el respaldo ignorado por Git:

`storage/backups/database/control-escaneres-pre-migration-20260718-152118.sql`

Tamaño: 403378 bytes. SHA-256: `316aaddb3f3fd6f032297e85decdfe5ac0494b12907faa7a6304041d92074854`. El encabezado SQL se verificó como legible. El dump no se añadió a Git.

## Aplicación

Se ejecutó primero el dry-run. Después se usaron `--execute`, `--confirm-local`, `--ci-local`, base explícita y checksum exacto del respaldo. Las siete migraciones del manifest se aplicaron en orden y se registraron individualmente en `schema_migrations` con execution ID `2dc6b7289def7b8d61c425958ec684c5`.

MariaDB realiza commits implícitos para DDL; el runner se detiene ante el primer fallo y no intenta rollback destructivo automático. Una segunda ejecución omite migraciones registradas cuyo checksum coincide.

## Resultado

El preflight posterior devolvió `canonical_complete`, todos sus checks en PASS y exit code 0. Schema-test: 67 PASS. El Dashboard dejó el fallback y muestra información persistida.

## Fixtures y piloto

El CSV ficticio validó 2 filas. Se insertaron `SC-9001` y `SC-9002` mediante transacción. El piloto sobre `SC-9001` completó entrega, recepción, reporte de incidencia, cambio de severidad, resolución, envío y retorno de mantenimiento. Se usaron exclusivamente Persona Demo, Recepción Demo, `DEMO-001` y datos técnicos artificiales.

La limpieza permanece en dry-run. Detecta `SC-9001` con un movimiento y bloquea eliminación automática; `SC-9002` no tiene movimientos. No se limpió al finalizar para conservar evidencia local del piloto.

## Rollback local

Si se requiere volver al estado previo, detener el módulo y restaurar el respaldo completo en un entorno aislado antes de afectar la base local. Dado que ya existen operaciones ficticias relacionadas, no usar `DROP TABLE` ni borrado parcial automático.

## Riesgos

El filtro manual `q=SC-900` no devolvió filas durante una comprobación, aunque el catálogo completo sí mostró ambos fixtures y la suite de integración valida los filtros. Debe observarse en la revisión manual. El respaldo contiene datos locales y debe conservar permisos restrictivos y permanecer fuera de Git.

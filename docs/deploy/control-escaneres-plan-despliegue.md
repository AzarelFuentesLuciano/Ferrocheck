# Plan de despliegue de Control de Escáneres

## Matriz de precondiciones

| Requisito | Verificación | Éxito | Bloqueo | Corrección |
|---|---|---|---|---|
| Versión aprobada | `git rev-parse HEAD` | Hash aprobado | Cambios locales | Detener y conciliar |
| PHP/PDO | Preflight | PASS | Extensión ausente | Ajustar servidor |
| MariaDB | Preflight y administrador | 10.4 compatible | Motor/sintaxis desconocida | Prueba efímera previa |
| Esquema | Inspector de sólo lectura | Ausente o canónico conocido | Legacy/parcial | Diseñar conciliación |
| Respaldo | Operador verifica archivo, tamaño y SHA-256 | Restauración aislada probada | Sin respaldo verificable | No migrar |
| Manifest | Dry-run | Todos los checksums PASS | Archivo alterado/desconocido | Revisar artefacto |
| Ventana | Aprobación operativa | Módulo bloqueado | Operaciones activas | Reprogramar |

## Respaldo MariaDB

El administrador genera fuera del repositorio un nombre `vascor_ops_YYYYMMDD_HHMMSS.sql`, permisos restrictivos y almacenamiento fuera del contenedor. Plantilla sin credenciales: `mysqldump --host=<host> --user=<usuario> --single-transaction --routines --triggers --events --default-character-set=utf8mb4 <base> > <ruta-segura>`. Las credenciales se solicitan de forma interactiva o mediante el gestor autorizado, nunca en el comando versionado.

Verificar tamaño no nulo, `sha256sum <archivo>`, lectura del encabezado sin exponer datos y restauración en una instancia aislada. Conservar según política corporativa; nunca guardar dumps en Git.

## Aplicación controlada

1. Operador confirma hash desplegado y ticket aprobado.
2. Supervisor abre la ventana y bloquea operaciones del módulo.
3. Administrador confirma respaldo, checksum y restauración de prueba.
4. Sistemas ejecuta preflight de sólo lectura y dry-run del manifest.
5. Administrador aplica manualmente las siete migraciones en orden, sólo tras aprobación explícita.
6. Operador registra archivo, checksum, resultado, actor y hora en el registro externo de cambios.
7. Sistemas ejecuta schema-test, pruebas específicas GET y smoke.
8. Supervisor valida Dashboard, catálogo y expediente.
9. Supervisor desbloquea el módulo y registra hora de cierre.

No existe tabla versionada de migraciones. Las tablas existentes no demuestran qué archivo se ejecutó; se requiere registro externo hasta diseñar un mecanismo canónico.

## Rollback

| Momento | Impacto | Opción | Pérdida posible | Responsable |
|---|---|---|---|---|
| Antes de datos | Sólo estructura | Down manual inverso con aprobación | Ninguna operación | DBA |
| Tras carga inicial | Estructura y catálogo | Restaurar respaldo o reversión transaccional validada | Carga inicial | DBA + supervisor |
| Tras operaciones reales | Historial operativo | Restaurar respaldo y conciliar ventana | Operaciones posteriores al backup | Comité de incidente |

Después de operar con datos reales no se recomienda ejecutar simplemente `DROP TABLE`. Se congela el módulo, preserva evidencia, evalúa restauración y concilia operaciones posteriores.

## Carga, verificación y monitoreo

Validar CSV con `--validate-only`; revisar duplicados, estados, formato y reporte antes de diseñar cualquier ejecución. Durante piloto registrar requestId, correlationId, módulo, operación, resultado, duración y actor ID seguro. No registrar secretos, cookies, tokens ni payloads completos.

La aprobación requiere pruebas automatizadas, restauración aislada, recorrido manual móvil/escritorio y conformidad del supervisor. El cierre documenta resultados, riesgos aceptados y responsable de monitoreo.

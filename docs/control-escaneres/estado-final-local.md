# Control de Escáneres — estado técnico local

## Alcance y aislamiento

El módulo usa namespaces `App\...\ControlEscaneres`, controlador web, DTO, dominio, servicios, repositorios PDO, ViewModels, vistas, CSS, JavaScript, migraciones y pruebas propios. No consulta tablas, repositorios ni JavaScript internos de FerroCheck. Los puntos compartidos se limitan a `public/index.php`, `config/config.php`, PDO, Composer, sesión y el layout global `app/Views/inventario/importar.php` que hospeda el App Shell. `catalogo-fase1.php` es un prototipo no registrado ni enrutable.

## Arquitectura

Flujo: `public/index.php` → `ControlEscaneresWebController` → `ControlEscaneresServiceFactory` → servicios transaccionales → contratos/repositorios PDO → tablas `scanner_*`. Las vistas sólo reciben ViewModels o arreglos preparados. Las operaciones POST exigen CSRF y actor autenticado; errores técnicos se registran sin exponer SQL ni stack traces.

Los agregados funcionales son: catálogo y expediente; alta/edición/baja lógica; QR; entrega/recepción e inspecciones; diferencias; incidencias y seguimientos; mantenimiento; áreas; evidencias; auditoría; dashboard; PDF y reportes.

## Esquema y migraciones

El manifiesto contiene 11 migraciones ordenadas y verificadas por SHA-256. Las extensiones finales son:

- `008`: metadatos canónicos, historial, áreas y operaciones.
- `009`: diferencias, seguimientos de incidencias y mantenimiento/evidencias.
- `010`: valoración normalizada 0–100; convierte valores históricos 1–5 multiplicando por 20.
- `011`: estados de incidencia `abierta`, `en_seguimiento`, `resuelta`, `cancelada`.

El runner `bin/control-escaneres-migrate.php` bloquea hosts remotos, exige `APP_ENV=local`, base esperada, respaldo válido, checksum, confirmación y dry-run. Una segunda ejecución omite migraciones ya registradas.

## Datos

La base local conserva 46 escáneres: 2 preexistentes y 44 importados. El importador no lee PIN/PUK ni la sección Módem, no sobrescribe valores existentes con vacíos y confirma mediante un XLSX temporal ligado a sesión y SHA-256. El estado de un equipo se cambia mediante máquina de estados y nunca se elimina físicamente.

## QR y cámara

El QR se genera localmente con `endroid/qr-code` y contiene la URL del expediente. Es reproducible, individual, descargable e imprimible; una baja lógica no cambia su identidad. `qr-scanner.js` usa `BarcodeDetector`, solicita cámara trasera, detiene las pistas al cerrar o leer y mantiene captura manual como fallback. No almacena video.

La cámara requiere un contexto seguro. En teléfono, HTTPS es la opción recomendada; algunos navegadores bloquean `getUserMedia` sobre HTTP usando una IP LAN. En ese caso se usa captura manual o se configura HTTPS local.

## Fotografías y firmas

`EvidenceFileStorage` acepta JPEG, PNG y WebP reales hasta 5 MB, verifica MIME con `finfo`, dimensiones, nombre aleatorio, SHA-256, tamaño y ruta contenida. Los archivos quedan en `storage/evidencias/control-escaneres`, fuera de `public`, y se sirven por endpoint autenticado con verificación de integridad y `no-store`. La interfaz ofrece vista previa, eliminación y compresión cliente de imágenes grandes.

Las firmas se capturan en canvas con mouse, touch o stylus, se rechazan vacías, se guardan como PNG (no base64 en base de datos) y quedan ligadas a inspección/movimiento. Entrega y recepción conservan archivos distintos.

## Operaciones y valoración

Entrega y recepción bloquean las filas dentro de transacciones, impiden doble movimiento, guardan inspecciones, fotos, firmas, historial y auditoría. Recepción muestra la condición/fotos de entrega, compara componentes y valoración, y exige confirmación humana ante deterioro importante, daño crítico o faltante. La UI usa 1–5 estrellas; se persiste 0–100 y se presenta también 0–10. Un daño crítico prevalece sobre una valoración positiva.

Incidencias soporta reporte, fotos, severidad, seguimiento, cambio de severidad, resolución y cancelación. Mantenimiento conserva motivo, técnico/proveedor, diagnóstico, costo, fecha estimada, resultado, fotos y estado final. El expediente reúne todos esos registros.

## PDF y reportes

El comprobante PDF de movimiento incluye folio, QR, equipo/TAG, custodia, áreas/responsables, inspecciones, valoraciones, diferencias, incidencias, evidencias, firmas, duración y estado final. Se regenera sin eliminar historial.

Los reportes diario, semanal, mensual, por área, escáner, responsable, estado, incidencia, mantenimiento, pendientes, deterioro y valoración exportan PDF/XLSX. IMEI, ICCID y teléfono no se incluyen.

## Instalación local y dependencias

Requisitos: PHP 8.2+, PDO MySQL, JSON, mbstring, fileinfo, GD, MariaDB y Composer. Dependencias principales: PhpSpreadsheet, Dompdf y Endroid QR Code. La configuración vive en `.env` local no versionado; nunca se imprimen secretos.

Comandos de verificación:

```powershell
$env:APP_ENV='local'
php bin/control-escaneres-schema-preflight.php
php bin/control-escaneres-migrate.php
php tests/control-escaneres/run-completion.php
```

## Respaldo y restauración

Respaldos relevantes:

- `ferrocheck-pre-import-20260718-44-scanners.sql` (no modificar).
- `ferrocheck-pre-phase-completion-20260718.sql`.
- `ferrocheck-pre-rating-normalization-20260718.sql`.
- `ferrocheck-pre-incident-statuses-20260718.sql`.

Antes de restaurar se detiene la aplicación, se verifica SHA-256, se crea un respaldo del estado actual y se importa con el cliente MySQL exclusivamente en `localhost/ferrocheck`. La restauración es una acción manual deliberada; no forma parte del runner.

## Riesgos y limitaciones

- La autenticación/RBAC definitivo es compartido y futuro; las mutaciones actuales exigen el actor de sesión disponible.
- La cámara depende de soporte `BarcodeDetector` y contexto seguro; existe fallback manual.
- `app/Views/inventario/importar.php` sigue siendo el puente de layout global para conservar compatibilidad del App Shell.
- El XLSX original contiene filas duplicadas; el flujo confirmado conserva el resultado validado de 44 altas y tres conflictos excluidos.
- No se ha conectado ni desplegado al servidor desde esta fase local.

## Despliegue futuro

No desplegar directamente. En una fase autorizada: rotar/revisar credenciales, respaldar servidor, verificar extensiones/dependencias, ejecutar preflight, dry-run, migraciones con checksum y prueba piloto; después validar rutas, archivos privados, permisos de `storage`, QR con URL pública, HTTPS, PDF y rollback. No ejecutar estos pasos sin autorización explícita.

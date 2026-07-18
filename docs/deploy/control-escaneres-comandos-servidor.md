# Plantilla futura de comandos para servidor

> **NO EJECUTAR HASTA APROBACIÓN DEL DESPLIEGUE EN SERVIDOR.**

Esta plantilla no contiene host, usuario, base, contraseña ni checksum reales. Debe completarla un administrador autorizado dentro de una ventana aprobada.

1. Confirmar versión desplegada y árbol limpio.
2. Definir `APP_ENV` conforme a la política del servidor.
3. Crear respaldo lógico con credenciales obtenidas desde el gestor autorizado, nunca escritas en Git.
4. Verificar tamaño, SHA-256 y restauración aislada.
5. Ejecutar el preflight de sólo lectura.
6. Ejecutar `php bin/control-escaneres-migrate.php --dry-run`.
7. Solicitar aprobación final y ejecutar el mecanismo autorizado con base y checksum reales.
8. Ejecutar preflight posterior, schema-test, integración y smoke.
9. Registrar responsable, execution ID, checksums, inicio y cierre.

No copiar las banderas locales `--confirm-local` o `--ci-local` como sustituto del procedimiento de producción. El runner actual rechaza hosts remotos; el despliegue en servidor requerirá una fase posterior y una autorización específica.

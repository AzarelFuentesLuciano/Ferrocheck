# Prueba manual de integración interna

## Precondiciones

Usar un entorno de desarrollo con el esquema canónico aplicado de forma controlada y una sesión autenticada que contenga `user_id`. La aplicación no ejecuta migraciones automáticamente. No usar credenciales reales en capturas o reportes.

## Recorrido

1. Abrir `index.php?modulo=control-escaneres&seccion=catalogo` y confirmar que no aparecen registros de demostración.
2. Filtrar por código, marca, modelo, estado, actividad e incidencia; comprobar paginación y enmascaramiento.
3. Elegir un equipo `disponible` y abrir **Entrega**.
4. Completar persona, empleado, turno e inspección; enviar una vez y comprobar la redirección 303.
5. Verificar el folio generado y que el estado sea `entregado`.
6. Abrir **Recepción** para el mismo equipo y confirmar que muestra su movimiento abierto.
7. Registrar la inspección de retorno y revisar estado final y diferencias mediante el mensaje de resultado y expediente.
8. Abrir **Incidencias**, reportar una con tipo, severidad y descripción.
9. Cambiar la severidad mediante el flujo interno cuando corresponda.
10. Resolver la incidencia seleccionando únicamente uno de los estados ofrecidos por el servidor.
11. Desde un equipo permitido, abrir **Mantenimiento** y enviarlo al taller.
12. Regresar el equipo eligiendo un estado final permitido.
13. Abrir **Expediente** y comprobar movimientos, inspecciones, incidencias, evidencias, timeline y auditoría resumida.

## Seguridad y regresión

- Repetir un POST sin `_csrf` y confirmar rechazo amigable.
- Confirmar que modificar `scanner_id`, folio, actor o estado fuera de las opciones permitidas no evita validaciones del servidor.
- Confirmar que IMEI, teléfono e ICCID sólo muestran cuatro caracteres finales; PIN y PUK nunca aparecen.
- Confirmar que no se muestran SQL, stack traces, rutas locales ni rutas de storage.
- Verificar Dashboard, FerroCheck, importación Excel y navegación lateral sin ejecutar operaciones de escritura.

## Rutas adoptadas

Se conserva el router legacy por query string: `index.php?modulo=control-escaneres&seccion=<seccion>`. GET y POST comparten la misma URL; incidencias usa el campo interno `operation` para reportar, cambiar severidad o resolver. Esta decisión evita alterar la resolución global de rutas.

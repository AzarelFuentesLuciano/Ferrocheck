# Checklist de despliegue — Control de Escáneres

## Antes

- [ ] Hash y rama aprobados; árbol limpio.
- [ ] Ventana, responsables y canal de incidente confirmados.
- [ ] Operaciones del módulo detenidas.
- [ ] Respaldo lógico fuera del contenedor, tamaño y SHA-256 verificados.
- [ ] Restauración de respaldo probada en entorno aislado.
- [ ] Preflight sin BLOCKED.
- [ ] Manifest y dry-run en PASS.
- [ ] Tabla legacy descartada; versión MariaDB y zona horaria confirmadas.

## Durante

- [ ] Migraciones aplicadas en orden por administrador autorizado.
- [ ] Archivo, checksum, resultado y hora registrados externamente.
- [ ] No hubo SQL improvisado ni datos reales expuestos.
- [ ] Cualquier fallo detuvo el procedimiento.

## Después

- [ ] Schema-test y pruebas del Dashboard ejecutados.
- [ ] Smoke y recorridos GET correctos.
- [ ] Catálogo, entrega, recepción, incidencia, expediente y Dashboard revisados.
- [ ] Piloto aprobado en móvil y escritorio.
- [ ] Módulo desbloqueado y cierre documentado.

## Rollback

- [ ] Módulo bloqueado y evidencia preservada.
- [ ] Momento del fallo y operaciones posteriores identificados.
- [ ] DBA y supervisor aprobaron estrategia.
- [ ] Restauración o down manual probado antes de producción.
- [ ] Integridad y reconciliación verificadas antes de reabrir.

# Estrategia de rollback de la modularización

Esta guía no autoriza ejecutar un rollback. Debe existir aprobación, respaldo y ventana de mantenimiento.

## Línea base

- Commit funcional previo a FASE 0: `a7e70a6` (`chore: ignorar log de diagnóstico de Excel`).
- Commit de consolidación funcional: `5e84f6f`.
- Confirmar siempre con `git log --oneline --decorate -n 20`; no depender solo de hashes documentados si la historia cambia.

## Preparación antes de desplegar

1. Registrar commit actualmente desplegado y commit objetivo.
2. Respaldar base de datos con herramienta autorizada y verificar restauración en ambiente aislado.
3. Respaldar archivos subidos, configuración de servidor y variables de entorno.
4. Guardar inventario de permisos, propietario y configuración de Apache/PHP.
5. Ejecutar pruebas de humo y checklist manual sobre el commit candidato.
6. Confirmar que no hay migraciones irreversibles en el despliegue.

## Reversión conservando historial

Preferir `git revert` sobre reset en ramas compartidas:

```bash
git log --oneline
git revert <commit-problematico>
```

Para un rango de commits, preparar la reversión en orden controlado y revisar el diff antes de confirmar. No usar `git reset --hard`, `git clean` ni sobrescribir archivos del servidor.

## Restauración en servidor

1. Poner la aplicación en mantenimiento si el procedimiento operativo lo exige.
2. Crear el commit de reversión y revisarlo en repositorio local/controlado.
3. Desplegar ese nuevo commit por el mecanismo normal.
4. Restaurar base de datos solo si el despliegue afectó esquema/datos y existe autorización explícita.
5. Limpiar únicamente cachés documentadas; no borrar uploads ni logs.
6. Reiniciar servicios solo si el procedimiento de infraestructura lo requiere.

## Verificación posterior

- Ejecutar `tests/smoke/smoke.php`.
- Probar las cinco secciones de FerroCheck y siete de Control de Escáneres.
- Confirmar header, sidebar, footer, assets y estados activos.
- Ejecutar las pruebas manuales críticas de consulta, detalle y exportación.
- Confirmar que los controles pendientes de escáneres sigan inactivos.
- Comparar recuentos o checksums de datos cuando aplique.

## Logs a revisar

- Logs de Apache/Nginx.
- Log de errores PHP.
- `logs/excel_diagnostico.log` para pruebas de Excel.
- Logs de despliegue y del sistema operativo.
- Consola y red del navegador.

## Criterio de cierre

La reversión termina solo cuando las pruebas de humo pasan, las funciones críticas se validan manualmente y no existen errores 404/500 ni cambios de datos no explicados.

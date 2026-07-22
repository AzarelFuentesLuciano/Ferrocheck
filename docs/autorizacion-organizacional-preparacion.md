# Áreas organizacionales, módulos y alcance

Estado: preparado, no activado. La migración `20260722_013_prepare_organizational_access.sql` no debe ejecutarse sin autorización expresa, respaldo y validación de áreas reales.

## Separación del modelo

- `areas_organizacionales` representa departamentos de adscripción y alcance de datos.
- `scanner_areas` conserva su significado operativo actual: área habitual o de entrega dentro de Control de Escáneres.
- `roles` sigue agrupando permisos de acción. No incorpora nombres de áreas.
- `modulos` registra secciones funcionales mediante una clave estable; la URL no es el identificador de autorización.
- `usuario_areas` permite varias áreas activas y garantiza una sola principal mediante un índice generado.
- `area_modulos` concede acceso base heredado.
- `usuario_modulos` representa excepciones individuales, no permisos de acción.

## Precedencia

1. Denegación individual activa.
2. Permiso `modulos.acceso_global`.
3. Permiso individual activo.
4. Asociación activa entre un área activa del usuario y el módulo.
5. Sin acceso.

Un módulo activo y autorizado no concede acciones. Cada controlador debe seguir exigiendo el permiso concreto. La sesión válida y el usuario activo continúan siendo precondiciones de `AuthenticatedUser`.

## Alcance de datos

`OrganizationalAccess::authorizedAreaIds()` obtiene IDs exclusivamente desde el backend. `canAccessArea()` rechaza áreas inexistentes o inactivas y sólo permite alcance global con `areas.acceso_global`.

No se agrega todavía `WHERE area_organizacional_id IN (...)` a repositorios existentes. Primero deben existir asignaciones verificadas y un backfill completo. Para escritura, el futuro controlador deberá ejecutar `requireAreaAccess($areaId, $permiso)` antes de invocar el servicio de dominio.

## Estrategia de migración y backfill

1. Respaldar esquema y datos de autenticación, auditoría y Control de Escáneres.
2. Validar en preproducción versión de MySQL, columna generada, `CHECK`, `ENUM`, claves foráneas y charset.
3. Autorizar nombres y claves de áreas reales. Esta entrega no crea ninguna.
4. Ejecutar la migración en ventana controlada.
5. Crear áreas organizacionales desde Administración y auditar cada alta.
6. Asignar área principal a usuarios activos. Identificar por separado cuentas técnicas justificadas.
7. Asociar módulos a áreas después de aprobación del dueño funcional.
8. Conceder a `azarel`, mediante el rol Administrador, `areas.acceso_global` y `modulos.acceso_global` como contempla la migración.
9. Mapear `scanner_areas` hacia áreas organizacionales en una tabla de correspondencias revisada manualmente. No copiar por coincidencia de nombre.
10. Poblar `scanners.area_organizacional_id` únicamente con mapeos aprobados; conservar `area_id` y `area_habitual` sin cambios.
11. Medir registros sin propietario y resolverlos antes de activar filtros.
12. Activar primero validación de escritura, después consultas internas y finalmente sidebar dinámico.
13. Mantener una bandera de compatibilidad durante el despliegue y retirar el fallback sólo cuando no haya usuarios/registros pendientes.

## Módulos iniciales

La migración registra únicamente los ocho módulos ya visibles: Dashboard, FerroCheck, Inventario de Material, Inventario de Patio, Control de Escáneres, Reportes, Administración y Configuración General. No crea módulos operativos nuevos ni asociaciones con áreas.

FerroCheck sólo se clasifica en el catálogo. Su lógica interna y sus datos permanecen sin cambios hasta que el área responsable y el alcance sean aprobados.

## Activación del sidebar

`ModuleNavigationBuilder` genera navegación ordenada desde módulos activos, visibles y autorizados. No se conecta todavía al shell productivo porque las tablas no existen en la base actual. Conectarlo antes de migrar dejaría el menú vacío o produciría errores SQL.

Administración debe seguir requiriendo `administracion.acceder`, aunque el módulo esté autorizado. La visibilidad nunca sustituye la protección de URL y POST.

## Riesgos pendientes

- MySQL anterior puede no soportar todas las formas de columna generada o `CHECK` usadas.
- Usuarios activos sin área quedarían fuera del modelo después de retirar el modo compatible.
- Nombres iguales entre `scanner_areas` y áreas organizacionales no garantizan equivalencia.
- Un filtro prematuro ocultaría registros históricos sin backfill.
- Las rutas del catálogo deben tratarse como configuración protegida y no editarse libremente desde la interfaz.
- Falta aprobación de áreas reales, asociaciones área-módulo y responsables de FerroCheck.

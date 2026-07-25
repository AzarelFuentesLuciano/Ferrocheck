# ADR-014 — Sentinel: Super Administrador y usuarios protegidos

## Contexto

VASCOR OPS ya dispone de roles, permisos y sesiones persistentes. Ese modelo expresa capacidades operativas, pero no distingue una autoridad técnica de plataforma ni protege cuentas especiales frente a administradores ordinarios.

## Decisión

Sentinel introduce dos atributos booleanos directamente en `usuarios`:

- `es_super_administrador`: autoridad técnica independiente de roles.
- `es_usuario_protegido`: cuenta cuya consulta y administración requieren dicha autoridad.

No se implementa como rol porque un rol puede editarse, perder permisos o asignarse mediante los flujos administrativos ordinarios. Sentinel complementa los permisos existentes y no concede automáticamente permisos funcionales.

## Autenticación y sesión

`AuthRepository` proyecta ambos atributos en login y reconstrucción. Antes de aplicar la migración detecta la ausencia de columnas y devuelve `0`, permitiendo un despliegue compatible.

`AuthenticatedUser` agrega propiedades opcionales con valor predeterminado `false` y los métodos tipados `isSuperAdministrator()` e `isProtectedUser()`.

Al iniciar sesión, `AuthService` guarda ambos atributos. Una sesión anterior que no los contiene se interpreta como no privilegiada. Una revocación en base de datos prevalece inmediatamente sobre un valor previamente guardado en sesión.

Después de aplicar la migración 014, la cuenta inicial Sentinel debe cerrar sesión y volver a iniciarla. Las sesiones creadas antes de Sentinel no adquieren privilegios automáticamente aunque la base ya contenga el atributo. Este comportamiento *fail-closed* es una decisión deliberada de seguridad.

## Autorización

`Authorization::requireSuperAdministrator()` exige autenticación y el atributo Sentinel. No sustituye `Authorization::require()` ni modifica permisos ordinarios.

`ProtectedUserPolicy` es una política sin SQL ni vistas. Considera protegido únicamente `true`, `1` o `'1'`; un atributo ausente equivale a no protegido. Una cuenta protegida solo puede verse o administrarse por un Super Administrador.

## Migración y rollback

La migración `20260724_014_create_sentinel_super_admin.sql`:

- agrega columnas, checks e índices mediante verificaciones de `information_schema`;
- inicializa exclusivamente al usuario `azarel`;
- crea tres permisos técnicos sin asignarlos a roles.

El nombre inicial aparece únicamente en SQL de bootstrap y no participa en autorización PHP.

El rollback elimina índices, checks y columnas Sentinel. La inspección de todas las migraciones y esquemas SQL del proyecto identificó `rol_permisos.permiso_id` como la única relación hacia `permisos.id`. Por ello, un permiso técnico solo se elimina cuando no existe ninguna fila relacionada en `rol_permisos` y conserva exactamente la descripción creada por Sentinel.

La precondición del rollback es ejecutarlo contra una versión del esquema representada por las migraciones del repositorio. Si una versión futura incorpora otra relación hacia `permisos.id`, el rollback deberá actualizarse antes de ejecutarse.

## Alcance de Fase 1A

Incluye migración, modelo autenticado, persistencia de sesión, autorización, política central, pruebas y este ADR.

No incluye filtros administrativos, protección de operaciones sobre usuarios, formularios, insignias, navegación ni Administración Técnica. Estos elementos corresponden a Fases 1B y 1C.

## Riesgos

- La cuenta inicial solo adquiere privilegio después de aplicar la migración.
- Las sesiones abiertas antes de la migración permanecen sin privilegio hasta volver a iniciar sesión.
- La protección todavía no está integrada en repositorios o controladores administrativos.
- El rollback conserva permisos relacionados para no romper integridad referencial.

## Pruebas

La suite de Fase 1A cubre compatibilidad del constructor, autorización, política, formatos booleanos, esquema anterior, proyección del repositorio, sesión, estructura de migración, rollback y ausencia de autorización PHP por usuario o ID fijo.

## Trabajo futuro

Fase 1B integrará filtrado y controles backend en administración de usuarios. Fase 1C añadirá interfaz, insignias, navegación y la pantalla inicial de Administración Técnica.

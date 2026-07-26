-- VASCOR OPS / Rail Etapa 3: registro organizacional y permiso mínimo.
-- No crea tablas de negocio ni modifica módulos existentes.

START TRANSACTION;

INSERT INTO modulos (clave,nombre,descripcion,ruta,icono,orden,activo,visible_menu)
VALUES ('rail','Rail','Operación ferroviaria modular','rail','▰',15,1,1)
ON DUPLICATE KEY UPDATE
    nombre=VALUES(nombre),
    descripcion=VALUES(descripcion),
    ruta=VALUES(ruta),
    icono=VALUES(icono),
    orden=VALUES(orden),
    activo=VALUES(activo),
    visible_menu=VALUES(visible_menu);

INSERT INTO permisos (clave,nombre,descripcion)
VALUES ('rail.ver','Consultar Rail','Acceder al módulo Rail')
ON DUPLICATE KEY UPDATE
    nombre=VALUES(nombre),
    descripcion=VALUES(descripcion);

INSERT INTO area_modulos (area_id,modulo_id,activo,created_by)
SELECT a.id,m.id,1,NULL
FROM areas_organizacionales a
JOIN modulos m ON m.clave='rail'
WHERE a.clave='sistemas'
ON DUPLICATE KEY UPDATE activo=1;

INSERT INTO rol_permisos (rol_id,permiso_id)
SELECT r.id,p.id
FROM roles r
JOIN permisos p ON p.clave='rail.ver'
WHERE r.nombre='Administrador'
AND NOT EXISTS (
    SELECT 1
    FROM rol_permisos existing
    WHERE existing.rol_id=r.id
      AND existing.permiso_id=p.id
);

COMMIT;

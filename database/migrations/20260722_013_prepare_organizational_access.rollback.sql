-- Rollback de 20260722_013. Ejecutar sólo con respaldo y autorización expresa.
ALTER TABLE scanners DROP FOREIGN KEY fk_ce_scanners_area_organizacional;
ALTER TABLE scanners DROP INDEX idx_ce_scanners_area_organizacional;
ALTER TABLE scanners DROP COLUMN area_organizacional_id;

DELETE rp FROM rol_permisos rp
JOIN permisos p ON p.id = rp.permiso_id
WHERE p.clave IN (
    'areas.ver','areas.crear','areas.editar','areas.desactivar','areas.asignar','areas.acceso_global',
    'modulos.ver','modulos.asignar','modulos.acceso_global'
);

DELETE FROM permisos WHERE clave IN (
    'areas.ver','areas.crear','areas.editar','areas.desactivar','areas.asignar','areas.acceso_global',
    'modulos.ver','modulos.asignar','modulos.acceso_global'
);

DROP TABLE IF EXISTS usuario_modulos;
DROP TABLE IF EXISTS area_modulos;
DROP TABLE IF EXISTS usuario_areas;
DROP TABLE IF EXISTS modulos;
DROP TABLE IF EXISTS areas_organizacionales;

START TRANSACTION;

DELETE rp
FROM rol_permisos rp
JOIN roles r ON r.id=rp.rol_id AND r.nombre='Administrador'
JOIN permisos p ON p.id=rp.permiso_id AND p.clave='rail.catalogos.importar';

DELETE FROM permisos
WHERE clave='rail.catalogos.importar'
AND NOT EXISTS (
    SELECT 1 FROM rol_permisos rp WHERE rp.permiso_id=permisos.id
);

DROP TABLE IF EXISTS rail_catalog_route_codes;

COMMIT;

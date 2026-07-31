START TRANSACTION;

DELETE rp FROM rol_permisos rp
JOIN roles r ON r.id=rp.rol_id AND r.nombre='Administrador'
JOIN permisos p ON p.id=rp.permiso_id
WHERE p.clave IN (
    'rail.consist.ver','rail.consist.generar','rail.consist.exportar',
    'rail.consist.ver_detalle','rail.consist.ver_historial'
);

DELETE FROM permisos
WHERE clave IN (
    'rail.consist.ver','rail.consist.generar','rail.consist.exportar',
    'rail.consist.ver_detalle','rail.consist.ver_historial'
)
AND NOT EXISTS (SELECT 1 FROM rol_permisos rp WHERE rp.permiso_id=permisos.id);

DROP TABLE IF EXISTS rail_consist_units;
DROP TABLE IF EXISTS rail_consist_platforms;
DROP TABLE IF EXISTS rail_consists;
DROP TABLE IF EXISTS rail_catalog_versions;
DROP TABLE IF EXISTS rail_consist_sequences;

COMMIT;

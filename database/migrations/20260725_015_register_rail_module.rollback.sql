-- Rollback seguro de Rail Etapa 3.
-- Conserva módulo o permiso cuando mantienen relaciones ajenas a esta migración.

START TRANSACTION;

DELETE rp
FROM rol_permisos rp
JOIN roles r ON r.id=rp.rol_id AND r.nombre='Administrador'
JOIN permisos p ON p.id=rp.permiso_id AND p.clave='rail.ver';

DELETE am
FROM area_modulos am
JOIN areas_organizacionales a ON a.id=am.area_id AND a.clave='sistemas'
JOIN modulos m ON m.id=am.modulo_id AND m.clave='rail';

DELETE p
FROM permisos p
WHERE p.clave='rail.ver'
  AND NOT EXISTS (
      SELECT 1
      FROM rol_permisos rp
      WHERE rp.permiso_id=p.id
  );

DELETE m
FROM modulos m
WHERE m.clave='rail'
  AND NOT EXISTS (
      SELECT 1
      FROM area_modulos am
      WHERE am.modulo_id=m.id
  )
  AND NOT EXISTS (
      SELECT 1
      FROM usuario_modulos um
      WHERE um.modulo_id=m.id
  );

COMMIT;

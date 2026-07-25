-- Rollback exclusivo de Sentinel Fase 1A.
-- Conserva permisos técnicos si ya tienen relaciones o si su descripción no
-- corresponde exactamente a la creada por esta migración.

DELETE FROM permisos
WHERE NOT EXISTS (
    SELECT 1
    FROM rol_permisos
    WHERE rol_permisos.permiso_id=permisos.id
) AND (
    (p.clave='administracion_tecnica.acceder' AND p.descripcion='Acceso exclusivo al centro técnico Sentinel') OR
    (p.clave='administracion_tecnica.estado.ver' AND p.descripcion='Consultar el estado técnico general de la plataforma') OR
    (p.clave='administracion_tecnica.auditoria.ver' AND p.descripcion='Consultar eventos técnicos autorizados por Sentinel')
);

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_activo_super_administrador'),
    'ALTER TABLE usuarios DROP INDEX idx_usuarios_activo_super_administrador',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_protegido'),
    'ALTER TABLE usuarios DROP INDEX idx_usuarios_protegido',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_super_administrador'),
    'ALTER TABLE usuarios DROP INDEX idx_usuarios_super_administrador',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='usuarios' AND constraint_name='chk_usuarios_protegido'),
    'ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_protegido',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='usuarios' AND constraint_name='chk_usuarios_super_administrador'),
    'ALTER TABLE usuarios DROP CONSTRAINT chk_usuarios_super_administrador',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='es_usuario_protegido'),
    'ALTER TABLE usuarios DROP COLUMN es_usuario_protegido',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='es_super_administrador'),
    'ALTER TABLE usuarios DROP COLUMN es_super_administrador',
    'SELECT 1'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

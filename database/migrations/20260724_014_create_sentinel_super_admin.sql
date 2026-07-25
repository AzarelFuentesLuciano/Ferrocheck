-- VASCOR OPS / Sentinel Fase 1A: privilegio de plataforma y cuentas protegidas.
-- No asigna permisos técnicos a roles. La referencia a "azarel" se usa únicamente
-- para inicializar la primera cuenta Sentinel durante esta migración.

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='es_super_administrador'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD COLUMN es_super_administrador TINYINT(1) NOT NULL DEFAULT 0 AFTER activo'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name='usuarios' AND column_name='es_usuario_protegido'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD COLUMN es_usuario_protegido TINYINT(1) NOT NULL DEFAULT 0 AFTER es_super_administrador'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='usuarios' AND constraint_name='chk_usuarios_super_administrador'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_super_administrador CHECK (es_super_administrador IN (0,1))'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='usuarios' AND constraint_name='chk_usuarios_protegido'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD CONSTRAINT chk_usuarios_protegido CHECK (es_usuario_protegido IN (0,1))'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_super_administrador'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD INDEX idx_usuarios_super_administrador (es_super_administrador)'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_protegido'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD INDEX idx_usuarios_protegido (es_usuario_protegido)'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

SET @sentinel_sql = IF(
    EXISTS(SELECT 1 FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='usuarios' AND index_name='idx_usuarios_activo_super_administrador'),
    'SELECT 1',
    'ALTER TABLE usuarios ADD INDEX idx_usuarios_activo_super_administrador (activo,es_super_administrador)'
);
PREPARE sentinel_stmt FROM @sentinel_sql; EXECUTE sentinel_stmt; DEALLOCATE PREPARE sentinel_stmt;

UPDATE usuarios
SET es_super_administrador=1,es_usuario_protegido=1
WHERE usuario='azarel';

INSERT INTO permisos(clave,nombre,descripcion)
SELECT 'administracion_tecnica.acceder','Acceder a Administración Técnica','Acceso exclusivo al centro técnico Sentinel'
WHERE NOT EXISTS(SELECT 1 FROM permisos WHERE clave='administracion_tecnica.acceder');

INSERT INTO permisos(clave,nombre,descripcion)
SELECT 'administracion_tecnica.estado.ver','Consultar estado técnico','Consultar el estado técnico general de la plataforma'
WHERE NOT EXISTS(SELECT 1 FROM permisos WHERE clave='administracion_tecnica.estado.ver');

INSERT INTO permisos(clave,nombre,descripcion)
SELECT 'administracion_tecnica.auditoria.ver','Consultar auditoría técnica','Consultar eventos técnicos autorizados por Sentinel'
WHERE NOT EXISTS(SELECT 1 FROM permisos WHERE clave='administracion_tecnica.auditoria.ver');


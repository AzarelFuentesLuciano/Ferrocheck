-- VASCOR OPS: áreas organizacionales, catálogo de módulos y alcance de acceso.
-- Aplicación controlada aprobada. Requiere respaldo verificado antes de ejecutarse.
-- Inserta únicamente las cuatro áreas y asociaciones expresamente autorizadas.

CREATE TABLE areas_organizacionales (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_areas_organizacionales_clave (clave),
    UNIQUE KEY uq_areas_organizacionales_nombre (nombre),
    KEY idx_areas_organizacionales_activo_nombre (activo, nombre),
    CONSTRAINT chk_areas_organizacionales_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_areas_organizacionales_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_areas_organizacionales_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE modulos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(80) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(500) NULL,
    ruta VARCHAR(255) NOT NULL,
    icono VARCHAR(40) NULL,
    orden SMALLINT UNSIGNED NOT NULL DEFAULT 100,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    visible_menu TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_modulos_clave (clave),
    UNIQUE KEY uq_modulos_ruta (ruta),
    KEY idx_modulos_menu (activo, visible_menu, orden),
    CONSTRAINT chk_modulos_activo CHECK (activo IN (0, 1)),
    CONSTRAINT chk_modulos_visible CHECK (visible_menu IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_areas (
    usuario_id BIGINT UNSIGNED NOT NULL,
    area_id BIGINT UNSIGNED NOT NULL,
    es_principal TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by BIGINT UNSIGNED NULL,
    principal_usuario_id BIGINT UNSIGNED GENERATED ALWAYS AS (
        CASE WHEN es_principal = 1 AND activo = 1 THEN usuario_id ELSE NULL END
    ) PERSISTENT,
    PRIMARY KEY (usuario_id, area_id),
    UNIQUE KEY uq_usuario_areas_principal (principal_usuario_id),
    KEY idx_usuario_areas_area_estado (area_id, activo, usuario_id),
    CONSTRAINT chk_usuario_areas_principal CHECK (es_principal IN (0, 1)),
    CONSTRAINT chk_usuario_areas_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_usuario_areas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_areas_area FOREIGN KEY (area_id) REFERENCES areas_organizacionales(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_areas_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE area_modulos (
    area_id BIGINT UNSIGNED NOT NULL,
    modulo_id BIGINT UNSIGNED NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (area_id, modulo_id),
    KEY idx_area_modulos_modulo_estado (modulo_id, activo, area_id),
    CONSTRAINT chk_area_modulos_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_area_modulos_area FOREIGN KEY (area_id) REFERENCES areas_organizacionales(id) ON DELETE RESTRICT,
    CONSTRAINT fk_area_modulos_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_area_modulos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_modulos (
    usuario_id BIGINT UNSIGNED NOT NULL,
    modulo_id BIGINT UNSIGNED NOT NULL,
    tipo ENUM('permitir', 'denegar') NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    created_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (usuario_id, modulo_id),
    KEY idx_usuario_modulos_modulo_tipo (modulo_id, activo, tipo, usuario_id),
    CONSTRAINT chk_usuario_modulos_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_usuario_modulos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_modulos_modulo FOREIGN KEY (modulo_id) REFERENCES modulos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_modulos_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO modulos (clave, nombre, descripcion, ruta, icono, orden, activo, visible_menu) VALUES
('dashboard', 'Dashboard', 'Resumen general de VASCOR OPS', 'dashboard', '🏠', 10, 1, 1),
('ferrocheck', 'FerroCheck', 'Consulta y gestión ferroviaria', 'ferrocheck', '🚂', 20, 1, 1),
('inventario_material', 'Inventario de Material', 'Inventario de materiales', 'inventario-material', '📦', 30, 1, 1),
('inventario_patio', 'Inventario de Patio', 'Operación e inventario de patio', 'operaciones-patio', '🚛', 40, 1, 1),
('control_escaneres', 'Control de Escáneres', 'Control operativo de equipos', 'control-escaneres', '📡', 50, 1, 1),
('reportes', 'Reportes', 'Reportes generales de la plataforma', 'reportes', '📊', 60, 1, 1),
('administracion', 'Administración', 'Usuarios, roles, áreas y módulos', 'administracion', '👤', 70, 1, 1),
('configuracion_general', 'Configuración General', 'Configuración general de la plataforma', 'configuracion-general', '⚙', 80, 1, 1)
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),descripcion=VALUES(descripcion),ruta=VALUES(ruta),icono=VALUES(icono),orden=VALUES(orden),activo=VALUES(activo),visible_menu=VALUES(visible_menu);

INSERT INTO areas_organizacionales (clave,nombre,descripcion,activo,created_by,updated_by)
SELECT seed.clave,seed.nombre,seed.descripcion,1,u.id,u.id
FROM (
    SELECT 'sistemas' clave,'Sistemas' nombre,'Tecnología, soporte y operación de sistemas' descripcion
    UNION ALL SELECT 'calidad','Calidad','Gestión y control de calidad'
    UNION ALL SELECT 'embarques','Embarques','Operación y seguimiento de embarques'
    UNION ALL SELECT 'administracion','Administración','Administración interna de la plataforma'
) seed
LEFT JOIN usuarios u ON u.usuario='azarel'
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),descripcion=VALUES(descripcion),activo=1,updated_by=VALUES(updated_by);

INSERT INTO permisos (clave, nombre, descripcion) VALUES
('areas.ver', 'Consultar áreas organizacionales', 'Consultar el catálogo organizacional'),
('areas.crear', 'Crear áreas organizacionales', 'Crear áreas sin asignaciones automáticas'),
('areas.editar', 'Editar áreas organizacionales', 'Modificar nombre y descripción'),
('areas.desactivar', 'Desactivar áreas organizacionales', 'Aplicar baja lógica cuando no deje asignaciones inválidas'),
('areas.asignar', 'Asignar áreas', 'Asignar áreas activas a usuarios y módulos'),
('areas.acceso_global', 'Alcance global de áreas', 'Consultar y operar sobre todas las áreas activas'),
('modulos.ver', 'Consultar módulos', 'Consultar el catálogo general de módulos'),
('modulos.asignar', 'Asignar módulos', 'Configurar herencia y excepciones de acceso'),
('modulos.acceso_global', 'Acceso global a módulos', 'Acceder a todos los módulos activos')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),descripcion=VALUES(descripcion);

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id
FROM roles r
JOIN permisos p ON p.clave IN (
    'areas.ver','areas.crear','areas.editar','areas.desactivar','areas.asignar','areas.acceso_global',
    'modulos.ver','modulos.asignar','modulos.acceso_global'
)
WHERE r.nombre = 'Administrador'
AND NOT EXISTS (SELECT 1 FROM rol_permisos existing WHERE existing.rol_id=r.id AND existing.permiso_id=p.id);

INSERT INTO area_modulos(area_id,modulo_id,activo,created_by)
SELECT a.id,m.id,1,u.id
FROM areas_organizacionales a
JOIN modulos m ON
    (a.clave='sistemas' AND m.clave IN('dashboard','control_escaneres','configuracion_general')) OR
    (a.clave='calidad' AND m.clave IN('dashboard')) OR
    (a.clave='embarques' AND m.clave IN('dashboard','ferrocheck','inventario_patio','reportes')) OR
    (a.clave='administracion' AND m.clave IN('dashboard','administracion','reportes','configuracion_general'))
LEFT JOIN usuarios u ON u.usuario='azarel'
ON DUPLICATE KEY UPDATE activo=1;

INSERT INTO usuario_areas(usuario_id,area_id,es_principal,activo,created_by)
SELECT u.id,a.id,1,1,u.id
FROM usuarios u JOIN areas_organizacionales a ON a.clave='sistemas'
WHERE u.usuario='azarel' AND u.activo=1
ON DUPLICATE KEY UPDATE es_principal=1,activo=1;

-- Integración controlada futura: propiedad organizacional, distinta de scanner_areas.
-- La columna es nullable y no activa filtros hasta completar el backfill.
ALTER TABLE scanners
    ADD COLUMN area_organizacional_id BIGINT UNSIGNED NULL AFTER area_id,
    ADD KEY idx_ce_scanners_area_organizacional (area_organizacional_id),
    ADD CONSTRAINT fk_ce_scanners_area_organizacional
        FOREIGN KEY (area_organizacional_id) REFERENCES areas_organizacionales(id) ON DELETE RESTRICT;

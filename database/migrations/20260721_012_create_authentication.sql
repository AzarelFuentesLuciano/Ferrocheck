-- VASCOR OPS: autenticación y autorización general. No crea usuarios.
CREATE TABLE usuarios (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    numero_empleado VARCHAR(40) NOT NULL,
    usuario VARCHAR(80) NOT NULL,
    correo VARCHAR(190) NULL,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    ultimo_acceso DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    UNIQUE KEY uq_usuarios_numero_empleado (numero_empleado),
    UNIQUE KEY uq_usuarios_correo (correo),
    KEY idx_usuarios_activo_nombre (activo, nombre),
    CONSTRAINT chk_usuarios_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_usuarios_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    CONSTRAINT fk_usuarios_updated_by FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    descripcion VARCHAR(255) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    es_sistema TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_nombre (nombre),
    KEY idx_roles_activo (activo),
    CONSTRAINT chk_roles_activo CHECK (activo IN (0, 1)),
    CONSTRAINT chk_roles_sistema CHECK (es_sistema IN (0, 1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permisos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clave VARCHAR(120) NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    descripcion VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_permisos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_roles (
    usuario_id BIGINT UNSIGNED NOT NULL,
    rol_id BIGINT UNSIGNED NOT NULL,
    asignado_por BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (usuario_id, rol_id),
    KEY idx_usuario_roles_rol (rol_id, usuario_id),
    CONSTRAINT fk_usuario_roles_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_roles_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT,
    CONSTRAINT fk_usuario_roles_actor FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permisos (
    rol_id BIGINT UNSIGNED NOT NULL,
    permiso_id BIGINT UNSIGNED NOT NULL,
    asignado_por BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (rol_id, permiso_id),
    KEY idx_rol_permisos_permiso (permiso_id, rol_id),
    CONSTRAINT fk_rol_permisos_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    CONSTRAINT fk_rol_permisos_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE RESTRICT,
    CONSTRAINT fk_rol_permisos_actor FOREIGN KEY (asignado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuario_sesiones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NOT NULL,
    session_hash CHAR(64) NOT NULL,
    ip VARCHAR(45) NULL,
    user_agent_hash CHAR(64) NULL,
    creada_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    ultimo_uso DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expira_at DATETIME(6) NOT NULL,
    revocada_at DATETIME(6) NULL,
    revocada_por BIGINT UNSIGNED NULL,
    motivo_revocacion VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuario_sesiones_hash (session_hash),
    KEY idx_usuario_sesiones_usuario_estado (usuario_id, revocada_at, expira_at),
    KEY idx_usuario_sesiones_expira (expira_at),
    CONSTRAINT fk_usuario_sesiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_usuario_sesiones_actor FOREIGN KEY (revocada_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (nombre, descripcion, activo, es_sistema) VALUES
('Administrador', 'Administración completa de VASCOR OPS', 1, 1),
('Supervisor', 'Supervisión operativa', 1, 1),
('Operador', 'Operación diaria autorizada', 1, 1),
('Consulta', 'Acceso de sólo consulta', 1, 1);

INSERT INTO permisos (clave, nombre, descripcion) VALUES
('usuarios.ver', 'Consultar usuarios', 'Acceso al listado administrativo de usuarios'),
('usuarios.crear', 'Crear usuarios', 'Crear cuentas internas'),
('usuarios.editar', 'Editar usuarios', 'Modificar datos y roles'),
('usuarios.desactivar', 'Activar o desactivar usuarios', 'Cambiar el estado lógico'),
('usuarios.restablecer_password', 'Restablecer contraseñas', 'Establecer una contraseña administrativa'),
('roles.ver', 'Consultar roles', 'Acceso al catálogo de roles'),
('roles.crear', 'Crear roles', 'Crear roles internos'),
('roles.editar', 'Editar roles', 'Modificar datos y estado de roles'),
('roles.asignar_permisos', 'Asignar permisos', 'Modificar permisos de roles'),
('escaneres.ver', 'Consultar escáneres', 'Consultar Control de Escáneres'),
('escaneres.crear', 'Crear escáneres', 'Registrar equipos'),
('escaneres.editar', 'Editar escáneres', 'Modificar equipos'),
('escaneres.entregar', 'Entregar escáneres', 'Registrar entregas'),
('escaneres.recibir', 'Recibir escáneres', 'Registrar recepciones'),
('escaneres.reportes', 'Reportes de escáneres', 'Consultar reportes'),
('escaneres.administrar', 'Administrar escáneres', 'Operaciones administrativas'),
('inventario.ver', 'Consultar inventario', 'Consultar inventarios'),
('inventario.editar', 'Editar inventario', 'Modificar inventarios'),
('reportes.ver', 'Consultar reportes', 'Consultar reportes generales'),
('administracion.acceder', 'Acceder a administración', 'Abrir el módulo administrativo');

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permisos p WHERE r.nombre = 'Administrador';

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN
('escaneres.ver','escaneres.crear','escaneres.editar','escaneres.entregar','escaneres.recibir','escaneres.reportes','inventario.ver','reportes.ver')
WHERE r.nombre = 'Supervisor';

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN
('escaneres.ver','escaneres.crear','escaneres.entregar','escaneres.recibir','inventario.ver')
WHERE r.nombre = 'Operador';

INSERT INTO rol_permisos (rol_id, permiso_id)
SELECT r.id, p.id FROM roles r JOIN permisos p ON p.clave IN
('escaneres.ver','inventario.ver','reportes.ver')
WHERE r.nombre = 'Consulta';


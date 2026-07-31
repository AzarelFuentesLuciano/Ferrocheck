-- VASCOR OPS / Consist Rail: catálogos versionados y borradores productivos.
START TRANSACTION;

CREATE TABLE IF NOT EXISTS rail_consist_sequences (
    anio SMALLINT UNSIGNED NOT NULL,
    ultimo_folio INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (anio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rail_catalog_versions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_filename VARCHAR(255) NOT NULL,
    source_sha256 CHAR(64) NOT NULL,
    imported_by BIGINT UNSIGNED NOT NULL,
    imported_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    active TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rail_catalog_versions_sha (source_sha256),
    KEY idx_rail_catalog_versions_active (active, imported_at),
    CONSTRAINT fk_rail_catalog_versions_user FOREIGN KEY (imported_by) REFERENCES usuarios(id),
    CONSTRAINT chk_rail_catalog_versions_active CHECK (active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rail_consists (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    folio VARCHAR(30) NOT NULL,
    analysis_token CHAR(64) NOT NULL,
    fecha_inicio DATE NOT NULL,
    fecha_fin DATE NOT NULL,
    status ENUM('borrador') NOT NULL DEFAULT 'borrador',
    total_units INT UNSIGNED NOT NULL,
    total_platforms INT UNSIGNED NOT NULL,
    source_catalog_version_id BIGINT UNSIGNED NOT NULL,
    issues_json JSON NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    exported_at DATETIME(6) NULL,
    exported_by BIGINT UNSIGNED NULL,
    export_sha256 CHAR(64) NULL,
    confirmed_at DATETIME(6) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_rail_consists_folio (folio),
    KEY idx_rail_consists_analysis (analysis_token),
    KEY idx_rail_consists_status (status, created_at),
    CONSTRAINT fk_rail_consists_catalog FOREIGN KEY (source_catalog_version_id) REFERENCES rail_catalog_versions(id),
    CONSTRAINT fk_rail_consists_created_by FOREIGN KEY (created_by) REFERENCES usuarios(id),
    CONSTRAINT fk_rail_consists_exported_by FOREIGN KEY (exported_by) REFERENCES usuarios(id),
    CONSTRAINT chk_rail_consists_period CHECK (fecha_fin >= fecha_inicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rail_consist_platforms (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consist_id BIGINT UNSIGNED NOT NULL,
    platform_number VARCHAR(64) NOT NULL,
    position INT UNSIGNED NOT NULL,
    track VARCHAR(255) NULL,
    total_units INT UNSIGNED NOT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_rail_consist_platform_position (consist_id, position),
    KEY idx_rail_consist_platform_number (platform_number),
    CONSTRAINT fk_rail_consist_platform_consist FOREIGN KEY (consist_id) REFERENCES rail_consists(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS rail_consist_units (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    consist_id BIGINT UNSIGNED NOT NULL,
    platform_id BIGINT UNSIGNED NOT NULL,
    global_position INT UNSIGNED NOT NULL,
    platform_position TINYINT UNSIGNED NOT NULL,
    vin VARCHAR(64) NOT NULL,
    track VARCHAR(255) NULL,
    route_code VARCHAR(64) NULL,
    market VARCHAR(255) NULL,
    shipping_destination VARCHAR(255) NULL,
    final_data_json JSON NOT NULL,
    vehicle_load_data_json JSON NULL,
    shippers_data_json JSON NULL,
    cnacs_selected_data_json JSON NULL,
    cnacs_additional_data_json JSON NULL,
    trace_json JSON NOT NULL,
    issues_json JSON NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_rail_consist_unit_position (consist_id, global_position),
    KEY idx_rail_consist_units_vin (consist_id, vin),
    KEY idx_rail_consist_units_consist (consist_id),
    KEY idx_rail_consist_units_platform (platform_id),
    CONSTRAINT fk_rail_consist_unit_consist FOREIGN KEY (consist_id) REFERENCES rail_consists(id) ON DELETE CASCADE,
    CONSTRAINT fk_rail_consist_unit_platform FOREIGN KEY (platform_id) REFERENCES rail_consist_platforms(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (clave,nombre,descripcion) VALUES
('rail.consist.ver','Ver Consist Rail','Consultar borradores de Consist Rail'),
('rail.consist.generar','Generar Consist Rail','Generar y guardar borradores de Consist Rail'),
('rail.consist.exportar','Exportar Consist Rail','Descargar el snapshot XLSX oficial de Consist Rail'),
('rail.consist.ver_detalle','Ver detalle Consist','Consultar trazabilidad de unidades de Consist Rail'),
('rail.consist.ver_historial','Ver historial Consist','Consultar historial de borradores de Consist Rail')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),descripcion=VALUES(descripcion);

INSERT INTO rol_permisos (rol_id,permiso_id)
SELECT r.id,p.id FROM roles r
JOIN permisos p ON p.clave IN (
    'rail.consist.ver','rail.consist.generar','rail.consist.exportar',
    'rail.consist.ver_detalle','rail.consist.ver_historial'
)
WHERE r.nombre='Administrador'
AND NOT EXISTS (
    SELECT 1 FROM rol_permisos rp WHERE rp.rol_id=r.id AND rp.permiso_id=p.id
);

COMMIT;

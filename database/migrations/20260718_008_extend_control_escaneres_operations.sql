-- Extensión incremental del módulo Control de Escáneres.
-- No elimina ni reemplaza datos existentes.
CREATE TABLE IF NOT EXISTS scanner_areas (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_ce_scanner_areas_nombre (nombre),
    KEY idx_ce_scanner_areas_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE scanners ADD COLUMN IF NOT EXISTS tag_original VARCHAR(40) NULL AFTER codigo_qr;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS red VARCHAR(80) NULL AFTER iccid;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS plan VARCHAR(80) NULL AFTER red;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS actividad_habitual VARCHAR(255) NULL AFTER plan;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS area_habitual VARCHAR(120) NULL AFTER actividad_habitual;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS ubicacion VARCHAR(255) NULL AFTER area_habitual;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS antiguedad_descriptiva VARCHAR(120) NULL AFTER ubicacion;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS observaciones TEXT NULL AFTER antiguedad_descriptiva;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS fotografia_principal VARCHAR(500) NULL AFTER fotografia_oficial_id;
ALTER TABLE scanners ADD COLUMN IF NOT EXISTS deleted_at DATETIME(6) NULL AFTER deactivated_at;
ALTER TABLE scanners ADD UNIQUE KEY IF NOT EXISTS uq_ce_scanners_tag_original (tag_original);
ALTER TABLE scanners ADD KEY IF NOT EXISTS idx_ce_scanners_area_habitual (area_habitual);

ALTER TABLE scanner_movimientos ADD COLUMN IF NOT EXISTS supervisor_nombre VARCHAR(160) NULL AFTER persona_entrega_nombre;
ALTER TABLE scanner_movimientos ADD COLUMN IF NOT EXISTS responsable_entrega_nombre VARCHAR(160) NULL AFTER supervisor_nombre;

CREATE TABLE IF NOT EXISTS scanner_mantenimientos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scanner_id BIGINT UNSIGNED NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'abierto',
    motivo VARCHAR(500) NOT NULL,
    tecnico_proveedor VARCHAR(160) NULL,
    diagnostico TEXT NULL,
    costo DECIMAL(12,2) NULL,
    iniciado_at DATETIME(6) NOT NULL,
    fecha_estimada DATE NULL,
    finalizado_at DATETIME(6) NULL,
    resultado TEXT NULL,
    estado_final VARCHAR(30) NULL,
    registrada_por BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_mantenimiento_scanner FOREIGN KEY (scanner_id) REFERENCES scanners(id),
    KEY idx_ce_mantenimiento_scanner_estado (scanner_id, estado),
    KEY idx_ce_mantenimiento_fecha (iniciado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scanner_estado_historial (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scanner_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    motivo VARCHAR(500) NULL,
    entidad_origen VARCHAR(80) NULL,
    entidad_origen_id BIGINT UNSIGNED NULL,
    registrado_por BIGINT UNSIGNED NULL,
    changed_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_estado_historial_scanner FOREIGN KEY (scanner_id) REFERENCES scanners(id),
    KEY idx_ce_estado_historial_scanner_fecha (scanner_id, changed_at),
    KEY idx_ce_estado_historial_estado (estado_nuevo, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROLLBACK MANUAL: conservar datos antes de retirar columnas o tablas.

-- Control de Escáneres: catálogo canónico de equipos.
-- Precondición: aborta si existe una tabla scanners que no tenga el esquema canónico.
SET @ce_scanners_exists := (
    SELECT COUNT(*) FROM information_schema.tables
    WHERE table_schema = DATABASE() AND table_name = 'scanners'
);
SET @ce_scanners_canonical := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'scanners'
      AND column_name IN ('codigo', 'codigo_qr')
);
SET @ce_scanners_guard := IF(
    @ce_scanners_exists = 1 AND @ce_scanners_canonical < 2,
    'SIGNAL SQLSTATE ''45000'' SET MESSAGE_TEXT = ''La tabla scanners existente es legacy; requiere conciliacion antes de continuar''',
    'SELECT 1'
);
PREPARE ce_scanners_guard_stmt FROM @ce_scanners_guard;
EXECUTE ce_scanners_guard_stmt;
DEALLOCATE PREPARE ce_scanners_guard_stmt;

CREATE TABLE IF NOT EXISTS scanners (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(20) NOT NULL COMMENT 'Codigo visible unico con formato SC-0001',
    codigo_qr VARCHAR(100) NOT NULL COMMENT 'Contenido unico del identificador QR',
    numero_serie VARCHAR(120) NULL,
    imei VARCHAR(20) NULL COMMENT 'Dato sensible; mostrar enmascarado',
    marca VARCHAR(100) NOT NULL,
    modelo VARCHAR(120) NOT NULL,
    telefono VARCHAR(30) NULL COMMENT 'Dato sensible; mostrar enmascarado',
    iccid VARCHAR(32) NULL COMMENT 'Dato sensible; mostrar enmascarado',
    area_id BIGINT UNSIGNED NULL COMMENT 'FK futura hacia el catalogo general de areas',
    estado VARCHAR(30) NOT NULL DEFAULT 'disponible',
    indice_conservacion TINYINT UNSIGNED NULL,
    fotografia_oficial_id BIGINT UNSIGNED NULL COMMENT 'FK agregada al crear scanner_evidencias',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_by BIGINT UNSIGNED NULL COMMENT 'FK futura hacia usuarios',
    updated_by BIGINT UNSIGNED NULL COMMENT 'FK futura hacia usuarios',
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    deactivated_at DATETIME(6) NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_ce_scanners_codigo UNIQUE (codigo),
    CONSTRAINT uq_ce_scanners_codigo_qr UNIQUE (codigo_qr),
    CONSTRAINT uq_ce_scanners_numero_serie UNIQUE (numero_serie),
    CONSTRAINT uq_ce_scanners_imei UNIQUE (imei),
    CONSTRAINT uq_ce_scanners_iccid UNIQUE (iccid),
    CONSTRAINT chk_ce_scanners_codigo CHECK (codigo REGEXP '^SC-[0-9]{4,}$'),
    CONSTRAINT chk_ce_scanners_estado CHECK (estado IN ('disponible', 'entregado', 'mantenimiento', 'pendiente_reparacion', 'baja_definitiva', 'extraviado')),
    CONSTRAINT chk_ce_scanners_conservacion CHECK (indice_conservacion IS NULL OR indice_conservacion BETWEEN 0 AND 100),
    CONSTRAINT chk_ce_scanners_activo CHECK (activo IN (0, 1)),
    INDEX idx_ce_scanners_estado_activo (estado, activo),
    INDEX idx_ce_scanners_area (area_id),
    INDEX idx_ce_scanners_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catalogo maestro de escaneres de VASCOR OPS';

-- ROLLBACK MANUAL (destructivo; ejecutar sólo después de respaldo):
-- ALTER TABLE scanners DROP FOREIGN KEY fk_ce_scanners_fotografia_oficial;
-- DROP TABLE IF EXISTS scanners;

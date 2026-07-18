-- Control de Escáneres: daños, faltantes y anomalías.
CREATE TABLE IF NOT EXISTS scanner_incidencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scanner_id BIGINT UNSIGNED NOT NULL,
    movimiento_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(60) NOT NULL,
    severidad VARCHAR(20) NOT NULL,
    descripcion TEXT NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'abierta',
    reportado_por_nombre VARCHAR(160) NULL COMMENT 'Persona fisica que reporta',
    registrada_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    reportada_at DATETIME(6) NOT NULL,
    resolucion TEXT NULL,
    resuelta_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    resuelta_at DATETIME(6) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_incidencias_scanner FOREIGN KEY (scanner_id) REFERENCES scanners (id),
    CONSTRAINT fk_ce_incidencias_movimiento FOREIGN KEY (movimiento_id) REFERENCES scanner_movimientos (id),
    CONSTRAINT chk_ce_incidencias_estado CHECK (estado IN ('abierta', 'en_revision', 'en_mantenimiento', 'resuelta', 'descartada')),
    CONSTRAINT chk_ce_incidencias_severidad CHECK (severidad IN ('baja', 'media', 'alta', 'critica')),
    CONSTRAINT chk_ce_incidencias_resolucion CHECK (estado NOT IN ('resuelta', 'descartada') OR resuelta_at IS NOT NULL),
    INDEX idx_ce_incidencias_scanner_estado (scanner_id, estado),
    INDEX idx_ce_incidencias_movimiento (movimiento_id),
    INDEX idx_ce_incidencias_estado_severidad (estado, severidad),
    INDEX idx_ce_incidencias_reportada_at (reportada_at),
    INDEX idx_ce_incidencias_actores (registrada_por, resuelta_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Incidencias operativas de escaneres';

-- ROLLBACK MANUAL:
-- DROP TABLE IF EXISTS scanner_incidencias;

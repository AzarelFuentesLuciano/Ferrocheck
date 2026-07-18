-- Control de Escáneres: inspecciones inmutables de salida y regreso.
CREATE TABLE IF NOT EXISTS scanner_inspecciones (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    movimiento_id BIGINT UNSIGNED NOT NULL,
    scanner_id BIGINT UNSIGNED NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    bateria_porcentaje TINYINT UNSIGNED NULL,
    calificacion TINYINT UNSIGNED NULL,
    observaciones TEXT NULL,
    firma_usuario_evidencia_id BIGINT UNSIGNED NULL COMMENT 'FK agregada al crear evidencias',
    firma_responsable_evidencia_id BIGINT UNSIGNED NULL COMMENT 'FK agregada al crear evidencias',
    inspeccionada_at DATETIME(6) NOT NULL,
    registrada_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_inspecciones_movimiento FOREIGN KEY (movimiento_id) REFERENCES scanner_movimientos (id),
    CONSTRAINT fk_ce_inspecciones_scanner FOREIGN KEY (scanner_id) REFERENCES scanners (id),
    CONSTRAINT uq_ce_inspecciones_movimiento_tipo UNIQUE (movimiento_id, tipo),
    CONSTRAINT chk_ce_inspecciones_tipo CHECK (tipo IN ('entrega', 'recepcion')),
    CONSTRAINT chk_ce_inspecciones_bateria CHECK (bateria_porcentaje IS NULL OR bateria_porcentaje BETWEEN 0 AND 100),
    CONSTRAINT chk_ce_inspecciones_calificacion CHECK (calificacion IS NULL OR calificacion BETWEEN 1 AND 5),
    INDEX idx_ce_inspecciones_scanner_tipo (scanner_id, tipo),
    INDEX idx_ce_inspecciones_fecha (inspeccionada_at),
    INDEX idx_ce_inspecciones_actor (registrada_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Inspecciones de entrega y recepcion';

-- ROLLBACK MANUAL:
-- ALTER TABLE scanner_inspecciones DROP FOREIGN KEY fk_ce_inspecciones_firma_usuario;
-- ALTER TABLE scanner_inspecciones DROP FOREIGN KEY fk_ce_inspecciones_firma_responsable;
-- DROP TABLE IF EXISTS scanner_inspecciones;

-- Control de Escáneres: metadatos de archivos; los binarios viven fuera de la base.
CREATE TABLE IF NOT EXISTS scanner_evidencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scanner_id BIGINT UNSIGNED NOT NULL,
    movimiento_id BIGINT UNSIGNED NULL,
    inspeccion_id BIGINT UNSIGNED NULL,
    incidencia_id BIGINT UNSIGNED NULL,
    tipo VARCHAR(50) NOT NULL,
    ruta_storage VARCHAR(500) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    tamano_bytes BIGINT UNSIGNED NOT NULL,
    hash_sha256 CHAR(64) NOT NULL,
    capturada_at DATETIME(6) NOT NULL,
    registrada_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_evidencias_scanner FOREIGN KEY (scanner_id) REFERENCES scanners (id),
    CONSTRAINT fk_ce_evidencias_movimiento FOREIGN KEY (movimiento_id) REFERENCES scanner_movimientos (id),
    CONSTRAINT fk_ce_evidencias_inspeccion FOREIGN KEY (inspeccion_id) REFERENCES scanner_inspecciones (id),
    CONSTRAINT fk_ce_evidencias_incidencia FOREIGN KEY (incidencia_id) REFERENCES scanner_incidencias (id),
    CONSTRAINT uq_ce_evidencias_hash_entidad UNIQUE (hash_sha256, scanner_id, movimiento_id, inspeccion_id, incidencia_id),
    CONSTRAINT chk_ce_evidencias_hash CHECK (hash_sha256 REGEXP '^[0-9a-fA-F]{64}$'),
    CONSTRAINT chk_ce_evidencias_activo CHECK (activo IN (0, 1)),
    INDEX idx_ce_evidencias_scanner (scanner_id, activo),
    INDEX idx_ce_evidencias_movimiento (movimiento_id),
    INDEX idx_ce_evidencias_inspeccion (inspeccion_id),
    INDEX idx_ce_evidencias_incidencia (incidencia_id),
    INDEX idx_ce_evidencias_capturada_at (capturada_at),
    INDEX idx_ce_evidencias_actor (registrada_por)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Metadatos y trazabilidad de evidencias';

ALTER TABLE scanners
    ADD CONSTRAINT fk_ce_scanners_fotografia_oficial
    FOREIGN KEY (fotografia_oficial_id) REFERENCES scanner_evidencias (id);

ALTER TABLE scanner_inspecciones
    ADD CONSTRAINT fk_ce_inspecciones_firma_usuario
        FOREIGN KEY (firma_usuario_evidencia_id) REFERENCES scanner_evidencias (id),
    ADD CONSTRAINT fk_ce_inspecciones_firma_responsable
        FOREIGN KEY (firma_responsable_evidencia_id) REFERENCES scanner_evidencias (id);

-- ROLLBACK MANUAL:
-- ALTER TABLE scanner_inspecciones DROP FOREIGN KEY fk_ce_inspecciones_firma_responsable;
-- ALTER TABLE scanner_inspecciones DROP FOREIGN KEY fk_ce_inspecciones_firma_usuario;
-- ALTER TABLE scanners DROP FOREIGN KEY fk_ce_scanners_fotografia_oficial;
-- DROP TABLE IF EXISTS scanner_evidencias;

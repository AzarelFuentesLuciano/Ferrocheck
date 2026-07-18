-- Control de Escáneres: condición por componente inspeccionado.
CREATE TABLE IF NOT EXISTS scanner_inspeccion_detalles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    inspeccion_id BIGINT UNSIGNED NOT NULL,
    componente VARCHAR(30) NOT NULL,
    estado VARCHAR(40) NOT NULL,
    valor_numerico DECIMAL(10,2) NULL,
    valor_texto VARCHAR(500) NULL,
    observaciones TEXT NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_inspeccion_detalles_inspeccion FOREIGN KEY (inspeccion_id) REFERENCES scanner_inspecciones (id),
    CONSTRAINT uq_ce_inspeccion_detalles_componente UNIQUE (inspeccion_id, componente),
    CONSTRAINT chk_ce_inspeccion_detalles_componente CHECK (componente IN ('bateria', 'pantalla', 'touch', 'botones', 'lector', 'wifi', 'datos_moviles', 'accesorios')),
    INDEX idx_ce_inspeccion_detalles_estado (componente, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle normalizado de componentes inspeccionados';

-- ROLLBACK MANUAL:
-- DROP TABLE IF EXISTS scanner_inspeccion_detalles;

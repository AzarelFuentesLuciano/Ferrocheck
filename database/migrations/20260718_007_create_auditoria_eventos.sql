-- VASCOR OPS: bitácora append-only para Control de Escáneres y módulos futuros.
CREATE TABLE IF NOT EXISTS auditoria_eventos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id BIGINT UNSIGNED NULL COMMENT 'FK futura hacia usuarios',
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(80) NOT NULL,
    entidad VARCHAR(100) NOT NULL,
    entidad_id BIGINT UNSIGNED NULL,
    resultado VARCHAR(30) NOT NULL,
    valor_anterior_json JSON NULL,
    valor_nuevo_json JSON NULL,
    metadata_json JSON NULL,
    ip VARCHAR(45) NULL,
    request_id VARCHAR(100) NULL,
    session_fingerprint VARCHAR(128) NULL COMMENT 'Huella no reversible; nunca cookie o token',
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT chk_ce_auditoria_resultado CHECK (resultado IN ('exito', 'rechazado', 'error')),
    INDEX idx_ce_auditoria_entidad (entidad, entidad_id, created_at),
    INDEX idx_ce_auditoria_usuario (usuario_id, created_at),
    INDEX idx_ce_auditoria_modulo_accion (modulo, accion, created_at),
    INDEX idx_ce_auditoria_request (request_id),
    INDEX idx_ce_auditoria_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Eventos append-only; la aplicacion no ejecuta UPDATE ni DELETE';

-- ROLLBACK MANUAL (elimina la bitácora completa):
-- DROP TABLE IF EXISTS auditoria_eventos;

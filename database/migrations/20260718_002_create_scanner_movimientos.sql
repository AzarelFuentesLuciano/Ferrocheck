-- Control de Escáneres: ciclos de entrega y recepción.
CREATE TABLE IF NOT EXISTS scanner_movimientos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    scanner_id BIGINT UNSIGNED NOT NULL,
    folio VARCHAR(40) NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'abierto',
    movimiento_abierto_scanner_id BIGINT UNSIGNED
        AS (CASE WHEN estado = 'abierto' THEN scanner_id ELSE NULL END) PERSISTENT
        COMMENT 'Clave nullable para impedir dos movimientos abiertos por scanner',
    persona_entrega_nombre VARCHAR(160) NOT NULL COMMENT 'Persona fisica que recibe el equipo',
    numero_empleado VARCHAR(40) NOT NULL,
    area_id BIGINT UNSIGNED NULL COMMENT 'FK futura hacia areas',
    turno VARCHAR(40) NOT NULL,
    entregado_at DATETIME(6) NOT NULL,
    recibido_at DATETIME(6) NULL,
    vence_at DATETIME(6) NULL,
    entrega_registrada_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    recepcion_registrada_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    devolucion_recibida_por_nombre VARCHAR(160) NULL COMMENT 'Persona fisica que recibe la devolucion',
    duracion_segundos BIGINT UNSIGNED NULL,
    observaciones TEXT NULL,
    cancelado_por BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado; FK futura',
    cancelado_at DATETIME(6) NULL,
    motivo_cancelacion VARCHAR(500) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_movimientos_scanner FOREIGN KEY (scanner_id) REFERENCES scanners (id),
    CONSTRAINT uq_ce_movimientos_folio UNIQUE (folio),
    CONSTRAINT uq_ce_movimientos_abierto UNIQUE (movimiento_abierto_scanner_id),
    CONSTRAINT chk_ce_movimientos_estado CHECK (estado IN ('abierto', 'devuelto', 'vencido', 'con_incidencia', 'cancelado')),
    CONSTRAINT chk_ce_movimientos_fechas CHECK (recibido_at IS NULL OR recibido_at >= entregado_at),
    INDEX idx_ce_movimientos_scanner_estado (scanner_id, estado),
    INDEX idx_ce_movimientos_vence (estado, vence_at),
    INDEX idx_ce_movimientos_empleado (numero_empleado),
    INDEX idx_ce_movimientos_area_turno (area_id, turno),
    INDEX idx_ce_movimientos_entrega_actor (entrega_registrada_por),
    INDEX idx_ce_movimientos_recepcion_actor (recepcion_registrada_por),
    INDEX idx_ce_movimientos_entregado_at (entregado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Movimientos de custodia de escaneres';

-- ROLLBACK MANUAL:
-- DROP TABLE IF EXISTS scanner_movimientos;

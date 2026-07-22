-- Completa los flujos operativos de Control de Escáneres sin eliminar datos.
ALTER TABLE scanner_movimientos ADD COLUMN IF NOT EXISTS area_nombre VARCHAR(120) NULL AFTER area_id;
ALTER TABLE scanner_movimientos ADD COLUMN IF NOT EXISTS responsable_recepcion_nombre VARCHAR(160) NULL AFTER devolucion_recibida_por_nombre;

ALTER TABLE scanner_incidencias ADD COLUMN IF NOT EXISTS area_nombre VARCHAR(120) NULL AFTER descripcion;
ALTER TABLE scanner_incidencias ADD COLUMN IF NOT EXISTS responsable_nombre VARCHAR(160) NULL AFTER area_nombre;

ALTER TABLE scanner_evidencias ADD COLUMN IF NOT EXISTS mantenimiento_id BIGINT UNSIGNED NULL AFTER incidencia_id;
ALTER TABLE scanner_evidencias ADD KEY IF NOT EXISTS idx_ce_evidencias_mantenimiento (mantenimiento_id);
ALTER TABLE scanner_evidencias ADD CONSTRAINT fk_ce_evidencia_mantenimiento FOREIGN KEY (mantenimiento_id) REFERENCES scanner_mantenimientos(id);

CREATE TABLE IF NOT EXISTS scanner_inspeccion_diferencias (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    movimiento_id BIGINT UNSIGNED NOT NULL,
    inspeccion_entrega_id BIGINT UNSIGNED NOT NULL,
    inspeccion_recepcion_id BIGINT UNSIGNED NOT NULL,
    componente VARCHAR(60) NOT NULL,
    valor_anterior VARCHAR(120) NULL,
    valor_nuevo VARCHAR(120) NULL,
    clasificacion VARCHAR(40) NOT NULL,
    requiere_revision TINYINT(1) NOT NULL DEFAULT 0,
    confirmada_por BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_ce_diferencia_movimiento_componente (movimiento_id, componente),
    CONSTRAINT fk_ce_diferencia_movimiento FOREIGN KEY (movimiento_id) REFERENCES scanner_movimientos(id),
    CONSTRAINT fk_ce_diferencia_entrega FOREIGN KEY (inspeccion_entrega_id) REFERENCES scanner_inspecciones(id),
    CONSTRAINT fk_ce_diferencia_recepcion FOREIGN KEY (inspeccion_recepcion_id) REFERENCES scanner_inspecciones(id),
    KEY idx_ce_diferencia_clasificacion (clasificacion, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS scanner_incidencia_seguimientos (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    incidencia_id BIGINT UNSIGNED NOT NULL,
    estado_anterior VARCHAR(30) NULL,
    estado_nuevo VARCHAR(30) NOT NULL,
    comentario TEXT NOT NULL,
    registrado_por BIGINT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    CONSTRAINT fk_ce_seguimiento_incidencia FOREIGN KEY (incidencia_id) REFERENCES scanner_incidencias(id),
    KEY idx_ce_seguimiento_incidencia_fecha (incidencia_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ROLLBACK MANUAL: respaldar y conservar los datos antes de retirar estas estructuras.

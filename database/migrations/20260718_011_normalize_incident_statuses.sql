-- Alinea los estados operativos de incidencias con el lenguaje de interfaz.

ALTER TABLE scanner_incidencias
    DROP CONSTRAINT chk_ce_incidencias_estado,
    DROP CONSTRAINT chk_ce_incidencias_resolucion;

UPDATE scanner_incidencias SET estado = 'en_seguimiento' WHERE estado IN ('en_revision', 'en_mantenimiento');
UPDATE scanner_incidencias SET estado = 'cancelada' WHERE estado = 'descartada';

ALTER TABLE scanner_incidencias
    ADD CONSTRAINT chk_ce_incidencias_estado
        CHECK (estado IN ('abierta', 'en_seguimiento', 'resuelta', 'cancelada')),
    ADD CONSTRAINT chk_ce_incidencias_resolucion
        CHECK (estado NOT IN ('resuelta', 'cancelada') OR resuelta_at IS NOT NULL);

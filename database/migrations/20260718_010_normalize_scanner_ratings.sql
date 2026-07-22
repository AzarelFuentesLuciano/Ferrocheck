-- Normaliza la valoración de inspecciones de 1–5 estrellas a 0–100.
-- Los valores históricos válidos se convierten de forma determinista.

ALTER TABLE scanner_inspecciones
    DROP CONSTRAINT chk_ce_inspecciones_calificacion;

UPDATE scanner_inspecciones
SET calificacion = calificacion * 20
WHERE calificacion BETWEEN 1 AND 5;

ALTER TABLE scanner_inspecciones
    ADD CONSTRAINT chk_ce_inspecciones_calificacion
        CHECK (calificacion IS NULL OR calificacion BETWEEN 0 AND 100);

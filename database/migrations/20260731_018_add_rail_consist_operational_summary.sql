-- VASCOR OPS / Rail: snapshot del resumen operativo de plataformas.
START TRANSACTION;

ALTER TABLE rail_consists
    ADD COLUMN operational_summary_json JSON NULL AFTER issues_json;

COMMIT;

START TRANSACTION;

ALTER TABLE rail_consists
    DROP COLUMN operational_summary_json;

COMMIT;

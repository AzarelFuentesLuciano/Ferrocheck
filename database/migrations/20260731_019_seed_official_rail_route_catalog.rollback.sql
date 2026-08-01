-- Retira únicamente los datos instalados por la migración 019.
-- Si un Consist referencia una versión creada por la 019, la FK impide
-- eliminarla y el runner debe detener el rollback.
START TRANSACTION;

SET @official_sha256 := '42640d6baf5de85a151c38ea6d1234d5f8a0948bb605e2e173d7a60b5bf9972e';
SET @official_catalog_version_id := (
    SELECT id FROM rail_catalog_versions WHERE source_sha256=@official_sha256 LIMIT 1
);
SET @official_seed_mode := (
    SELECT seed_migration FROM rail_catalog_versions WHERE id=@official_catalog_version_id
);

DELETE FROM rail_catalog_route_codes
WHERE catalog_version_id=@official_catalog_version_id
AND seed_migration='20260731_019_seed_official_rail_route_catalog';

DELETE FROM rail_catalog_versions
WHERE id=@official_catalog_version_id
AND @official_seed_mode='20260731_019_seed_official_rail_route_catalog:created';

UPDATE rail_catalog_versions
SET origin='manual',seed_migration=NULL
WHERE id=@official_catalog_version_id
AND @official_seed_mode='20260731_019_seed_official_rail_route_catalog:adopted';

COMMIT;

-- MariaDB ejecuta estos ALTER TABLE con commits implícitos.
ALTER TABLE rail_catalog_route_codes
    DROP INDEX IF EXISTS uq_rail_catalog_route_version_source,
    DROP COLUMN IF EXISTS seed_migration,
    DROP COLUMN IF EXISTS border_crossing,
    DROP COLUMN IF EXISTS load_by,
    DROP COLUMN IF EXISTS rc_plant,
    DROP COLUMN IF EXISTS production_plant,
    DROP COLUMN IF EXISTS usa_canada,
    DROP COLUMN IF EXISTS heavy_duty,
    ADD UNIQUE INDEX IF NOT EXISTS uq_rail_catalog_route_version_code (catalog_version_id,route_code);

ALTER TABLE rail_catalog_versions
    DROP CONSTRAINT IF EXISTS chk_rail_catalog_system_importer,
    DROP COLUMN IF EXISTS seed_migration,
    DROP COLUMN IF EXISTS origin,
    MODIFY imported_by BIGINT UNSIGNED NOT NULL;

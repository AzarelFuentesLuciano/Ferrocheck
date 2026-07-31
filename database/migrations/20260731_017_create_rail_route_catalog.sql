-- VASCOR OPS / Rail: rutas versionadas del catálogo maestro.
START TRANSACTION;

CREATE TABLE IF NOT EXISTS rail_catalog_route_codes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    catalog_version_id BIGINT UNSIGNED NOT NULL,
    source_row INT UNSIGNED NOT NULL,
    route_code VARCHAR(64) NOT NULL,
    route_king VARCHAR(255) NULL,
    market VARCHAR(255) NULL,
    shipping_destination VARCHAR(255) NULL,
    carrier VARCHAR(255) NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY uq_rail_catalog_route_version_code (catalog_version_id,route_code),
    KEY idx_rail_catalog_route_code (route_code),
    CONSTRAINT fk_rail_catalog_route_version
        FOREIGN KEY (catalog_version_id) REFERENCES rail_catalog_versions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO permisos (clave,nombre,descripcion)
VALUES ('rail.catalogos.importar','Importar catálogos Rail','Importar y activar el catálogo maestro de rutas')
ON DUPLICATE KEY UPDATE nombre=VALUES(nombre),descripcion=VALUES(descripcion);

INSERT INTO rol_permisos (rol_id,permiso_id)
SELECT r.id,p.id
FROM roles r
JOIN permisos p ON p.clave='rail.catalogos.importar'
WHERE r.nombre='Administrador'
AND NOT EXISTS (
    SELECT 1 FROM rol_permisos rp WHERE rp.rol_id=r.id AND rp.permiso_id=p.id
);

COMMIT;

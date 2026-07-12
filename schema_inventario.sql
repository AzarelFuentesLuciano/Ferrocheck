CREATE TABLE inventario (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo VARCHAR(255),
    estacion VARCHAR(255),
    via VARCHAR(255),
    producto VARCHAR(255),
    cliente VARCHAR(255),
    ultimo_movimiento VARCHAR(255),
    fecha VARCHAR(255),
    hora VARCHAR(255),
    origen VARCHAR(255),
    destino VARCHAR(255),
    estatus VARCHAR(255),
    transportista VARCHAR(255),
    ferrocarril_actual VARCHAR(255),
    pedimento VARCHAR(255),
    cantidad VARCHAR(255),
    fecha_importacion DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

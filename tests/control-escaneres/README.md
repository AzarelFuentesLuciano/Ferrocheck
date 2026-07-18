# Pruebas de persistencia
Ejecutar `php tests/control-escaneres/run-unit.php` y `php tests/control-escaneres/run-integration.php`.
SQLite usa una base en memoria. Sustituye columnas generadas por un índice parcial, no valida bloqueo real `FOR UPDATE`, trata JSON como TEXT y omite UNSIGNED/precisión MariaDB. Las migraciones MariaDB no se ejecutan en estas pruebas.

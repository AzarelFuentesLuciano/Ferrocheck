# Reproducción del seed oficial

La migración 019 se genera con:

```powershell
php tools/rail/generate-route-catalog-seed.php
```

La herramienta:

1. verifica el SHA-256 oficial;
2. lee exclusivamente `route_codes`;
3. exige los once encabezados en su orden aprobado;
4. normaliza espacios y trata Route Code como texto en mayúsculas;
5. preserva ceros iniciales y convierte vacíos opcionales a `NULL`;
6. exige exactamente 436 filas;
7. genera SQL autocontenido y determinista.

La prueba `tests/rail/official-route-catalog-seed-test.php` vuelve a generar el
contenido en memoria y lo compara byte por byte con la migración versionada.
Así se detecta cualquier edición manual o divergencia respecto del XLSX.

Esta herramienta es sólo de desarrollo. El servidor de aplicación no la
ejecuta y no necesita PhpSpreadsheet ni el XLSX para aplicar la migración.

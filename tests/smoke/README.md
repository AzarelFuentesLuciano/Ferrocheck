# Pruebas de humo de VASCOR OPS

Herramienta de línea base para la modularización. Solo realiza solicitudes HTTP GET a páginas y assets estáticos; no envía POST, archivos ni acciones operativas.

## Requisitos

- PHP CLI con extensión cURL.
- Apache/XAMPP ejecutando el proyecto localmente.
- Base de datos disponible si el renderizado actual la requiere.

## Ejecución

Desde la raíz del proyecto:

```bash
php tests/smoke/smoke.php
```

La URL predeterminada es `http://localhost/Ferrocheck/public`. Puede indicarse otra base:

```bash
php tests/smoke/smoke.php http://localhost/Ferrocheck/public
```

o mediante variable de entorno:

```bash
VASCOR_SMOKE_BASE_URL=http://localhost/Ferrocheck/public php tests/smoke/smoke.php
```

En PowerShell:

```powershell
$env:VASCOR_SMOKE_BASE_URL='http://localhost/Ferrocheck/public'
php tests/smoke/smoke.php
```

## Resultado

- Cada comprobación imprime `PASS` o `FAIL`.
- El resumen muestra el total.
- El proceso devuelve código `0` si todo pasa y un código distinto de cero si existe cualquier fallo.

## Cobertura

- Dashboard general.
- Cinco secciones de FerroCheck.
- Siete secciones de Control de Escáneres.
- Header, sidebar, footer y navegación interna.
- Estados activos.
- Referencias y respuesta de CSS/JavaScript.
- Detección básica de errores PHP visibles y respuestas distintas de 200.

## Exclusiones deliberadas

No prueba importaciones, entregas, recepciones, bajas, mantenimiento, registro de escáneres ni ningún endpoint POST. Esas funciones requieren el checklist manual y un ambiente local controlado.

# Demo visual aislada del App Shell

## Propósito

`app-shell-demo.php` permite revisar manualmente el App Shell paralelo sin conectarlo a rutas, controladores, sesiones, endpoints, base de datos o módulos reales.

La demo utiliza exclusivamente información ficticia y no modifica datos.

> Esta herramienta no forma parte de ninguna ruta de producción. No debe exponerse en un servidor productivo y debe eliminarse o excluirse del despliegue final.

## Cómo abrirla en XAMPP

1. Iniciar Apache desde XAMPP.
2. Confirmar que el proyecto se encuentra en `C:\xampp\htdocs\Ferrocheck`.
3. Abrir directamente:

```text
http://localhost/Ferrocheck/tests/manual/app-shell-demo.php
```

La demo usa `/Ferrocheck/public` como base local de assets.

## Estados simulados

- FerroCheck aparece como módulo activo.
- Consulta VIN aparece como sección activa.
- El sidebar incluye Dashboard, FerroCheck, Inventario de Material, Inventario de Patio, Control de Escáneres, Reportes y Administración.
- El contenido incluye cuatro indicadores, una tabla y una nota, todos ficticios.

## Validación por breakpoint

### Escritorio amplio — 1440 px o superior

- Header centrado y metadatos visibles.
- Sidebar completo y módulo activo.
- Botón superior colapsa/restaura el sidebar.
- Cuatro tarjetas en una fila.
- Footer sin saltos ni superposición.

### Laptop — alrededor de 1024–1366 px

- Contenido central sin amontonamiento.
- Tarjetas reorganizadas correctamente.
- Tabla contenida en su panel.
- Navegación interna con espacio suficiente.

### Tablet — 768–920 px

- Sidebar fuera del lienzo al iniciar.
- Botón abre el sidebar y muestra backdrop.
- FerroCheck permite desplegar sus secciones.
- Enlace y botón de submenú son independientes.
- Escape y backdrop cierran el sidebar.

### Teléfono — 320–480 px

- Marca y botón permanecen visibles.
- Sidebar no queda cortado y permite scroll vertical.
- Sección activa visible.
- Navegación horizontal hace scroll dentro de su barra.
- Tarjetas en una columna.
- Tabla hace scroll dentro del panel.
- No existe desplazamiento horizontal general.

## Accesibilidad e interacción

- Comprobar foco visible con Tab.
- Revisar `aria-expanded` antes y después de abrir sidebar/submenús.
- Confirmar `aria-controls` y `aria-current`.
- Cerrar el sidebar con Escape.
- Confirmar actualización de fecha y reloj.
- Probar preferencia de movimiento reducido si está disponible.

## Qué no prueba

- Lógica de FerroCheck o Control de Escáneres.
- Consultas, importaciones o exportaciones.
- Endpoints o base de datos.
- Usuarios, sesiones, roles o permisos.
- Compatibilidad de contenido real con el layout.
- Integración con rutas actuales.
- Igualdad pixel a pixel con el shell productivo.

La demo debe permanecer fuera de staging hasta que su revisión manual sea autorizada y completada.

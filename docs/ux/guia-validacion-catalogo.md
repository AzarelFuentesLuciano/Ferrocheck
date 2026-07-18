# Guía de validación del catálogo piloto

## Preparación

Usar un entorno de desarrollo con datos no sensibles y esquema canónico aplicado de forma controlada. Abrir `index.php?modulo=control-escaneres&seccion=catalogo`. La referencia de componentes está en `tests/manual/vascor-design-system.php` y no tiene ruta de producción.

## Recorrido funcional

1. Confirmar breadcrumb, título, descripción y conteo de resultados.
2. Aplicar búsqueda por código/serie y filtros de marca, modelo, estado, actividad e incidencia.
3. Limpiar filtros y comprobar recuperación del catálogo.
4. Revisar paginación conservando filtros.
5. Confirmar que las acciones disponibles corresponden al estado del equipo.
6. Confirmar que IMEI y teléfono sólo muestran cuatro caracteres finales.
7. Forzar una búsqueda sin resultados y revisar la explicación y acción de recuperación.
8. Generar mensajes flash de éxito/error mediante un flujo operativo y comprobar `role=status`/`role=alert`.

## Checklist responsive

| Ancho | Validación |
|---:|---|
| 360 px | Sin scroll horizontal global; filtros plegables; botones de 44 px; tabla anuncia desplazamiento propio |
| 768 px | Filtros legibles; header y acciones sin superposición |
| 1024 px | Tabla, sidebar y contenido no se recortan |
| 1366 px | Jerarquía clara y ancho de lectura cómodo |
| 1920 px | Contenido limitado; no se estira indefinidamente |

## Accesibilidad manual

- Recorrer con Tab y Shift+Tab; el foco debe ser visible.
- Activar filtros, links y paginación sólo con teclado.
- Confirmar labels asociados a cada control.
- Verificar que badges incluyen texto y punto indicador, no sólo color.
- Comprobar contraste con herramienta WCAG; registrar cualquier pendiente.
- Activar “reducir movimiento” en el sistema y verificar ausencia de animaciones continuas.
- Con lector de pantalla, confirmar nombre de tabla, encabezados, estado, alertas y paginación.

## Criterios de aceptación

La tarea principal se identifica en menos de cinco segundos; no hay más de una acción primaria visible por bloque; no aparecen mocks, PIN o PUK; el catálogo funciona sin JavaScript; y FerroCheck/App Shell conservan su comportamiento previo.

## Limitaciones

La validación automatizada no sustituye una auditoría WCAG, pruebas reales de contraste ni sesiones con usuarios. Entrega, recepción, incidencias, mantenimiento y expediente aún conservan el diseño anterior por alcance del piloto.

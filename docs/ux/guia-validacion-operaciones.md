# Guía de validación de operaciones

## Preparación segura

Usar un entorno local con datos de prueba. No ejecutar operaciones POST contra producción. Verificar primero Catálogo, Dashboard e Historial mediante GET.

## Anchos de revisión

Repetir la inspección en 360, 768, 1024, 1366 y 1920 px. En cada ancho confirmar ausencia de scroll horizontal global, texto legible, foco visible, controles utilizables y una acción primaria clara por bloque.

## Recorrido manual

1. Abrir Dashboard: confirmar que no hay métricas ficticias y que el acceso al Catálogo funciona.
2. Abrir Catálogo: probar filtros y seleccionar un equipo sin alterar datos.
3. Abrir Entrega: comprobar pasos, resumen, labels, ayudas y revisión previa. No confirmar en producción.
4. Abrir Recepción: comprobar inspección, comparación y confirmación. No confirmar en producción.
5. Abrir Incidencias: alternar entre reporte y registros; abrir y cerrar el detalle de resolución sin enviarlo.
6. Abrir Mantenimiento: confirmar que sólo aparece la transición válida para el estado actual. No enviarla.
7. Abrir Expediente: recorrer anclas, verificar timeline, colecciones vacías y valores sensibles enmascarados.
8. Abrir Historial: confirmar el aviso provisional y la ausencia de actividad simulada.

## Teclado y tecnología de asistencia

Recorrer enlaces, campos, `details` y botones con Tab y Shift+Tab. Activar controles con Enter/Espacio, verificar asociación de labels, orden lógico, anuncio de alertas y paso actual. Confirmar que deshabilitar JavaScript no impide enviar los formularios y que, habilitado, el doble clic no duplica el envío.

## Evidencia esperada

Registrar ancho, navegador, pantalla, resultado, captura y defecto observado. Cualquier error funcional, 404, excepción JavaScript, dato sensible visible o desbordamiento global bloquea la aprobación.

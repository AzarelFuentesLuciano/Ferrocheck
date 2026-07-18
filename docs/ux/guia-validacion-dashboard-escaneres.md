# Guía de validación del Dashboard de escáneres

Validar sólo en un entorno local o de pruebas. No ejecutar POST ni SQL de producción.

## Escenarios de datos

1. Sin datos: todos los valores en cero, estado tranquilo y sin gráfica artificial.
2. Disponibles: total, activos, disponibles y porcentaje coherentes.
3. Entregas abiertas: estado entregado, entrega del rango y actividad reciente.
4. Mantenimiento: conteo normal, sin tratamiento visual de error.
5. Incidencias: distinguir incidencias abiertas de equipos afectados.
6. Incidencia crítica: aparece primero en Atención requerida y enlaza al expediente.
7. Actividad reciente: orden descendente, máximo diez entradas y sin payloads.
8. Siete días: selector conservado y siete puntos en la tabla.
9. Treinta días: selector conservado y treinta puntos en la tabla.

## Responsive

Revisar a 360, 768, 1024, 1366 y 1920 px. En móvil confirmar KPIs y accesos apilados, Atención antes de tendencias, timeline legible, tabla desplazable localmente y ausencia de scroll horizontal global. En escritorio comprobar un máximo de cinco KPIs y dos columnas sólo donde mejora la lectura.

## Accesibilidad y teclado

Recorrer selector, actualización, KPIs, expedientes y accesos rápidos con Tab/Shift+Tab. Confirmar foco visible, encabezados jerárquicos, enlaces descriptivos, porcentaje anunciado en `progress`, tabla con encabezados y comprensión sin color. Desactivar JavaScript y repetir selector/actualización: la navegación GET debe seguir funcionando.

## Fallos seguros

Probar esquema ausente y error de consulta en un entorno aislado. La pantalla no debe revelar SQL o stack traces y debe orientar a actualizar o contactar al administrador.

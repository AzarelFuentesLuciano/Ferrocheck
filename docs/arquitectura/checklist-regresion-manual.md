# Checklist de regresión manual

Registrar ambiente, URL, commit, navegador, resolución, resultado y evidencia para cada ejecución. No probar operaciones mutables en producción.

## FerroCheck

- [ ] Navegar por Dashboard, Consulta VIN, Importar Excel, Búsqueda múltiple y Configuración.
- [ ] Confirmar módulo y sección activos en sidebar/barra horizontal.
- [ ] Consultar un VIN conocido y validar código, transportista, ubicación, fecha, evidencia y acción.
- [ ] Consultar un VIN inexistente y validar el estado sin errores de consola.
- [ ] Ejecutar una búsqueda con varios VIN, incluyendo conocido, inexistente y duplicado.
- [ ] Seleccionar un Excel válido y detenerse antes de confirmar la importación.
- [ ] Validar nombre, tamaño, tipo, registros detectados, condición y barra de progreso.
- [ ] En ambiente local controlado y con respaldo, ejecutar una importación de prueba autorizada.
- [ ] Verificar el resumen e indicadores después de una importación controlada.
- [ ] Abrir el detalle de una plataforma y cerrarlo por botón, overlay y tecla Escape.
- [ ] Exportar resultados filtrados y abrir el XLSX generado.
- [ ] Confirmar que una tabla vacía conserva un estado comprensible.

## Control de Escáneres

- [ ] Navegar por Dashboard, Catálogo, Expediente, Entrega, Recepción, Historial y Reportes.
- [ ] Confirmar módulo y sección activos.
- [ ] Revisar las columnas visibles del catálogo.
- [ ] Abrir el expediente desde el catálogo.
- [ ] Revisar datos generales, técnicos, evidencia y actividad del expediente.
- [ ] Revisar los nueve pasos visuales de Entrega.
- [ ] Revisar entrega original, comparación, fotografías y firmas de Recepción.
- [ ] Revisar la línea de tiempo del Historial.
- [ ] Revisar tipos y filtros de Reportes.
- [ ] Confirmar que captura, guardado, importación, exportación, firmas y demás controles pendientes continúan inactivos.

## Shell y responsive

Repetir en escritorio grande, laptop, tablet y teléfono:

- [ ] Header, marca, versión, reloj y fecha visibles.
- [ ] Sidebar abierto/cerrado y backdrop correctos.
- [ ] En escritorio, FerroCheck no duplica su submenú en el sidebar.
- [ ] En móvil, desplegar y cerrar el submenú de FerroCheck.
- [ ] Validar Dashboard y las cuatro opciones internas del submenú móvil.
- [ ] Confirmar estado activo del módulo y sección.
- [ ] Confirmar scroll horizontal contenido en navegación interna.
- [ ] Confirmar que las tablas no ensanchan toda la página.
- [ ] Confirmar que no existe desbordamiento horizontal general.
- [ ] Confirmar footer al final del documento.
- [ ] Validar navegación con teclado y foco visible.

## Diseño

- [ ] Poppins aplicada en body, navegación, botones, formularios y tablas.
- [ ] Encabezados azules sin cambios de color o dimensión.
- [ ] Tarjetas, sombras, bordes y espaciados consistentes.
- [ ] Botones primarios/secundarios sin regresiones.
- [ ] Badges y estados conservan significado y contraste.
- [ ] Comparar capturas contra la línea base aprobada.

## Observabilidad

- [ ] Sin errores PHP visibles.
- [ ] Sin errores JavaScript en consola.
- [ ] Sin 404/500 en la pestaña Network.
- [ ] CSS, JavaScript y Poppins cargan correctamente.
- [ ] Revisar log de Apache/PHP y `logs/excel_diagnostico.log` después de pruebas autorizadas.

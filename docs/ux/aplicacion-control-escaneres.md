# Aplicación visual en Control de Escáneres

## Alcance

Esta fase aplica el sistema visual de VASCOR OPS a las vistas operativas de Control de Escáneres. No cambia rutas, controladores, servicios, repositorios, entidades, contratos POST ni persistencia.

## Matriz por pantalla

| Pantalla | Objetivo principal | Jerarquía y componentes | Estado de datos |
|---|---|---|---|
| Catálogo | Localizar un equipo | Encabezado, filtros, tabla, badges, paginación y vacío | Real |
| Entrega | Confirmar custodia e inspección inicial | Pasos, resumen de equipo, secciones de formulario y resumen de operación | Real |
| Recepción | Confirmar devolución e inspección final | Pasos, resumen, formulario, comparación y confirmación | Real |
| Incidencias | Reportar y resolver hallazgos | Navegación interna, formulario de reporte y resolución progresiva | Real |
| Mantenimiento | Enviar o regresar un equipo | Acción contextual única, ayuda y confirmación explícita | Real |
| Expediente | Consultar trazabilidad integral | Resumen seguro, anclas, timeline y actividad agrupada | Real |
| Historial | Anticipar consulta consolidada | Aviso y estado vacío honesto | Provisional, sin datos simulados |
| Dashboard | Orientar al siguiente paso | Aviso, accesos operativos y flujo recomendado | Provisional, sin métricas simuladas |

## Criterios de experiencia

- Una acción primaria por bloque operativo.
- Mensajes que explican qué ocurrirá antes de confirmar.
- Campos agrupados por intención y etiquetas vinculadas mediante `for` e `id`.
- Resoluciones y acciones críticas bajo revelado progresivo.
- Estados vacíos que ofrecen un siguiente paso seguro.
- IMEI, teléfono e ICCID se muestran exclusivamente mediante valores ya protegidos por el presentador; no se muestran PIN, PUK ni secretos.
- La interfaz no inventa indicadores, eventos o resultados cuando no existe una fuente consolidada.

## Responsive y accesibilidad

La estructura parte de una columna útil y expande a dos columnas cuando hay espacio. En anchos reducidos, formularios, resúmenes y actividad regresan a una columna; las acciones ocupan el ancho disponible y los pasos permiten desplazamiento local. Se mantienen foco visible, labels, `aria-current`, regiones de estado y HTML semántico. El JavaScript sólo mejora foco y prevención de doble envío; los formularios siguen funcionando sin JavaScript.

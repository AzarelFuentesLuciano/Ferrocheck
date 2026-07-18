# Sistema de diseño VASCOR OPS

## Visión y personalidad

VASCOR OPS debe sentirse sereno, estable y directo. Cada pantalla comunica ubicación, tarea principal, resultado y siguiente paso sin parecer un panel técnico. La identidad usa azul institucional moderado, superficies claras ligeramente azuladas, bordes discretos y estados semánticos contenidos.

## Principios cognitivos

- **Hick:** una acción primaria por sección; acciones secundarias se agrupan.
- **Fitts:** controles táctiles de al menos 44 px y acción principal al final del flujo.
- **Jerarquía:** breadcrumb, título, descripción, acción y contenido mantienen orden constante.
- **Proximidad:** filtros y campos relacionados viven dentro de grupos explícitos.
- **Consistencia:** mismos nombres, estados y variantes en todos los módulos.
- **Reconocimiento:** labels visibles, opciones enumeradas y contexto del equipo presente.
- **Divulgación progresiva:** filtros secundarios usan `details`; el contenido esencial permanece visible sin JavaScript.
- **Prevención:** estados deshabilitados explican la causa; el servidor sigue validando.
- **Recuperación:** errores indican qué revisar y ofrecen regresar o limpiar filtros.
- **Carga cognitiva:** una tarea dominante y menos decoración competitiva.

## Paleta y tokens

Primario `#245b78`; fondo `#f5f7f8`; superficie `#ffffff`; texto `#17252d`; texto secundario `#5e6d75`; borde `#dce3e6`. Éxito, advertencia, error e información usan fondos suaves y texto oscuro. Los estados combinan indicador, etiqueta y texto, nunca sólo color. Los valores canónicos viven en `vascor-design-tokens.css`.

## Tipografía y espaciado

Pila local: `system-ui`, Segoe UI, Roboto, Helvetica, Arial. Base 16 px, altura 1.55; metadatos nunca menores de 12 px. Escala de 12 a 32 px. Espaciado basado en 4/8 px, radios de 6/10/16 px y sombras sutiles.

## Componentes

Page header, breadcrumbs, botones, tarjetas, badges, alertas, campos, filtros, tablas, paginación, tabs, timeline, actividad, KPIs, skeletons y empty/error/loading states usan prefijo `vo-`. Los partials PHP reciben datos simples, escapan texto y no consultan servicios.

## Formularios tranquilos

Título y explicación preceden secciones pequeñas. Labels siempre visibles; opcionales marcados; ayuda y errores junto al control. En escritorio el formulario no supera el ancho legible. Cancelar es secundario y la única acción primaria aparece al final.

## Acciones sensibles

La confirmación nombra acción, equipo y consecuencia. El botón peligro sólo aparece dentro de esa confirmación y siempre existe una salida segura. No se utiliza “¿Está seguro?” sin contexto.

## Accesibilidad

Foco visible, controles semánticos, labels vinculados, `aria-live`, targets de 44 px, estados con texto/icono/color y reducción de movimiento. Pendiente: auditoría WCAG automatizada y prueba formal con lector de pantalla.

## Responsive

Mobile first: una columna, filtros plegables, tabla con wrapper señalado y acciones táctiles. A partir de 768 px se amplían grupos; el contenido se limita a 1440 px en escritorio. Validar 360, 768, 1024, 1366 y 1920 px sin scroll horizontal global.

## Microcopy

Usar verbos específicos: “Aplicar filtros”, “Limpiar filtros”, “Registrar entrega”. Explicar recuperación: “No encontramos escáneres con estos filtros. Ajusta la búsqueda o limpia los filtros.” Evitar SQL, clases, códigos internos de error y lenguaje culpabilizante.

## Reglas y malas prácticas

No crear múltiples botones primarios, iconos importantes sin texto, cards para cada dato, rojo para estados normales, placeholders como label, animaciones decorativas persistentes o clases semánticas construidas desde datos externos.

## Nuevos módulos

Comenzar por tokens y estructura de página; reutilizar partials; definir estados mediante allowlist; probar teclado y 360 px; mantener lógica fuera de vistas; ampliar el sistema sólo cuando exista repetición real.

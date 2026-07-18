# Auditoría visual inicial de VASCOR OPS

## Inventario de compatibilidad

| Elemento actual | Problema de experiencia | Riesgo de compatibilidad | Componente recomendado |
|---|---|---|---|
| `importador.css` | Paleta propia, fondos decorativos, ticker y animaciones que aumentan carga visual | Alto: sostiene FerroCheck y shell legacy | Mantener intacto; migrar por módulo |
| `vascor-design-system.css` | Tokens parciales, fuente Poppins externa y nombres mezclados con componentes | Medio: ya se carga globalmente | Tokens centrales y componentes aislados |
| `app-shell.css` | Buen responsive, pero depende de fallbacks y metadatos diminutos | Alto: shell validado | Consumir tokens sin cambiar estructura |
| `control-escaneres.css` | CSS comprimido, tamaños de 0.68–0.84 rem, sombras y estados sólo por color | Medio: vistas operativas activas | Componentes `vo-*` y piloto acotado |
| Encabezados `.ce-hero` / `.vascor-module-header` | Dos patrones equivalentes, alto peso visual | Medio | `PageHeader` tranquilo y sin degradado obligatorio |
| Botones `.ce-btn` / `.vascor-btn` | Altura de 40 px y jerarquía secundaria poco clara | Medio | Botón base de 44 px con una acción primaria |
| Badges | Texto pequeño y significado visual no uniforme | Bajo | Badge con icono CSS, texto y allowlist |
| Formularios | Labels pequeños; agrupación débil; ayuda y errores no estandarizados | Medio | Sección, grupo, ayuda y error asociados |
| Tablas | Encabezados de 0.68 rem y muchas acciones con igual peso | Medio | Tabla cómoda, columna principal y menú secundario |
| Estados vacíos | Texto breve sin recuperación | Bajo | Empty state con explicación y acción segura |
| Flash messages | Tarjeta genérica sin semántica accesible | Bajo | Alert con `role=status/alert` |
| Responsive | Buen reflujo a 760/920 px; falta checklist 360/1920 y filtros plegables | Medio | Mobile first y contenedor máximo |
| Foco | Existe `focus-visible`; `.app-main` elimina outline aunque no es control | Bajo | Foco global consistente, nunca eliminarlo en controles |
| Iconografía | Mezcla de emoji, glifos y pseudoelementos | Medio | Iconos CSS/SVG locales con texto visible |

## Riesgos y decisiones

- No se reemplazan reglas globales ni assets de FerroCheck.
- El App Shell conserva layout, sidebar, header y JavaScript validados.
- El piloto se limita al catálogo mediante clases nuevas, manteniendo las clases heredadas disponibles.
- Las fuentes externas existentes no se eliminan en esta fase para evitar regresión; los componentes nuevos usan pila del sistema.
- El dashboard, formularios operativos y expediente se migrarán después de validar el catálogo.
- El modo oscuro sólo queda preparado mediante tokens; no se expone un interruptor.

## Mejoras posteriores

Retirar gradualmente duplicación de CSS, unificar iconografía local, elevar tamaños de texto legacy, migrar formularios operativos, validar contraste con herramientas automatizadas y realizar pruebas con usuarios de patio y almacén.

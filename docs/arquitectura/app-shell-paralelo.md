# App Shell paralelo de VASCOR OPS

> En esta fase el App Shell existe en paralelo y no es utilizado por ninguna ruta activa.

## Objetivo

Definir una infraestructura visual neutral que pueda recibir módulos ya renderizados sin conocer FerroCheck, Control de Escáneres, parámetros de URL, sesiones, permisos o base de datos. Su creación permite comparar y probar el contrato antes de sustituir el shell monolítico actual.

## Archivos creados

- `app/Views/layouts/app.php`: documento HTML neutral y punto de composición.
- `app/Views/partials/header.php`: marca, botón del sidebar y metadatos de reloj.
- `app/Views/partials/sidebar.php`: navegación genérica basada en datos.
- `app/Views/partials/footer.php`: identidad y créditos.
- `public/assets/css/app-shell.css`: estilos aislados del shell.
- `public/assets/js/app-shell.js`: interacción del sidebar y reloj.
- `docs/arquitectura/app-shell-paralelo.md`: contrato y plan de integración.

## Contrato del layout

| Variable | Tipo | Responsabilidad |
|---|---|---|
| `$pageTitle` | string | Título del documento |
| `$documentLanguage` | string | Atributo `lang`; predeterminado `es` |
| `$assetBaseUrl` | string | Prefijo preparado externamente para assets |
| `$activeModule` | string | Identificador activo para presentación |
| `$activeSection` | string | Identificador de sección activa |
| `$modules` | array | Contrato genérico del sidebar |
| `$moduleNavigation` | string | HTML confiable de navegación interna ya preparado por el módulo |
| `$content` | string | HTML de negocio ya renderizado y confiable |
| `$additionalStyles` | string[] | CSS propio del módulo |
| `$additionalScripts` | string[] | JavaScript propio del módulo |
| `$header` | array | Textos de marca, versión y accesibilidad |
| `$footer` | array | Textos, créditos y año |
| `$sidebarLabel` | string | Etiqueta accesible de navegación |

Los textos y URLs se escapan con `htmlspecialchars`. `$moduleNavigation` y `$content` no se escapan porque representan HTML ya renderizado; el caller es responsable de producirlos de forma segura.

El layout no lee `$_GET`, `$_POST`, `$_SESSION`, archivos de configuración ni base de datos. Tampoco selecciona vistas.

## Contrato de módulos del sidebar

```php
[
    [
        'id' => 'modulo-estable',
        'label' => 'Nombre visible',
        'url' => '/ruta-preparada',
        'icon' => '•',
        'active' => true, // opcional; si falta se compara con $activeModule
        'sections' => [
            [
                'id' => 'seccion-estable',
                'label' => 'Sección',
                'url' => '/ruta-preparada',
                'active' => true, // opcional; compara con $activeSection
            ],
        ],
    ],
]
```

La capa que llame al layout podrá filtrar `$modules` mediante RBAC en el futuro. El partial no conoce permisos ni consulta datos.

En escritorio se presenta el enlace principal y se ocultan subsecciones. En móvil, un botón separado controla cada submenú mediante `aria-expanded` y `aria-controls`; el enlace principal sigue disponible.

## Funciones que pertenecen al shell

- Composición HTML general.
- Marca, header, sidebar, backdrop, main y footer.
- Estado visual activo recibido.
- Sidebar móvil y colapsado de escritorio.
- Submenús móviles genéricos.
- Fecha, reloj, Escape, backdrop y cambio de breakpoint.
- Carga ordenada de CSS/JS adicionales recibidos.

## Funciones excluidas

- Selección o renderizado de vistas de negocio.
- Lectura de `modulo` o `seccion`.
- Consultas, endpoints, AJAX o `fetch`.
- Importación, consulta VIN, exportación o indicadores.
- Catálogo, entrega, recepción o reportes de escáneres.
- Usuarios, sesiones, roles y permisos.

## Referencia visual utilizada

`app-shell.css` toma como referencia conceptual la geometría actual de `importador.css`: header de 92 px, sidebar de 260/86 px, breakpoint de 920 px, backdrop, padding principal y footer centrado. No se eliminó ni alteró ninguna regla original.

Todos los selectores nuevos están prefijados con `app-shell-`, `app-header-`, `app-sidebar-`, `app-main` o `app-footer-`. No redefine `.btn`, `.card`, `.panel-card`, `.stat-card`, `.table-wrapper` ni `.actions`.

## Integración posterior

1. Crear un render aislado o prueba visual sin conectar rutas productivas.
2. Comparar capturas con el shell actual en cuatro breakpoints.
3. Preparar contexto equivalente en un controlador, sin cambiar rutas.
4. Migrar una página de bajo riesgo detrás de una bandera reversible.
5. Ejecutar pruebas de humo y checklist manual.
6. Migrar módulos de forma incremental.
7. Retirar duplicados únicamente en una fase posterior autorizada.

## Rollback

Mientras no esté integrado, basta con retirar/revertir estos siete archivos nuevos. Tras una integración futura, el rollback deberá restaurar el render del shell anterior mediante `git revert`, ejecutar las 114 pruebas y revisar navegación responsive.

## Riesgos y diferencias conocidas

- El shell paralelo usa IDs y clases nuevos, por lo que el JavaScript existente no lo controla.
- El sidebar recibe datos; el actual está codificado directamente en `importar.php`.
- Los submenús usan botón separado en móvil, no `<details>`.
- No se ha realizado comparación visual renderizada porque ninguna ruta puede cargarlo todavía.
- Los scripts adicionales usan `defer`; debe validarse el orden al integrar módulos.
- `$content` exige un contrato explícito de HTML confiable.
- La versión paralela no reproduce animaciones o detalles decorativos que no sean infraestructura esencial.

## Pendientes visuales

- Comparación pixel a pixel del header y footer.
- Estados colapsado/expandido en laptop.
- Foco, tabulación y lector de pantalla.
- Scroll del sidebar con muchos módulos.
- Textos largos, iconos reales y sesión futura de usuario.
- Convivencia temporal con estilos de módulo al hacer la primera integración.

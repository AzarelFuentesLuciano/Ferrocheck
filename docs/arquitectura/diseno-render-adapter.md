# Diseño del Render Adapter de VASCOR OPS

> Este documento define el Render Adapter de VASCOR OPS. No implementa ni activa cambios en el flujo de producción.

## 1. Propósito y alcance

El Render Adapter será la frontera de presentación entre el controlador que atiende la página principal, el contexto temporal del sistema legacy, los módulos actuales y `app/Views/layouts/app.php`.

```text
DashboardController
    → contexto legacy normalizado
    → contenido del módulo
    → RenderContext
    → RenderAdapter
    → App Shell
    → HTML
```

Debe resolver:

- traducir el estado actual (`modulo`, `seccion`, `BASE_URL` y contenido renderizado) al contrato neutral del App Shell;
- preservar rutas, parámetros GET, endpoints POST y URLs actuales;
- mantener temporalmente el contenido legacy sin introducir lógica HTML en el controlador;
- mantener lógica de negocio fuera del layout;
- validar el contrato antes de renderizar;
- centralizar renderizado seguro, captura de salida y errores de vista;
- aceptar navegación previamente filtrada para un RBAC futuro;
- permitir módulos nuevos sin modificar FerroCheck;
- hacer posible un rollback explícito al `require` legacy.

No debe resolver:

- routing, autenticación, autorización o consulta de permisos;
- selección o ejecución de servicios de negocio;
- consultas, transacciones o persistencia;
- saneamiento de HTML arbitrario procedente de usuarios;
- división de FerroCheck o Control de Escáneres;
- registro global de módulos, manifiestos o plugins;
- respuesta de endpoints JSON, archivos XLSX o solicitudes POST;
- feature flags genéricas de toda la aplicación;
- caché de vistas o assets en la primera versión.

## 2. Principios de diseño

1. Un único punto de corte en `DashboardController::index()`.
2. El controlador orquesta; no construye HTML ni arrays extensos de presentación.
3. El adaptador renderiza; no conoce negocio, sesión, permisos ni HTTP global.
4. El layout presenta un contrato ya validado.
5. El módulo produce datos y selecciona contenido; no controla el shell.
6. Las rutas y los POST permanecen fuera de este cambio.
7. El fallback sucede antes de enviar salida.
8. El diseño mínimo debe funcionar en PHP 8.2 y ser plenamente compatible con PHP 8.3.
9. Las abstracciones se introducen sólo cuando existe una segunda necesidad real.

## 3. Responsabilidades por componente

### A. `DashboardController`

- Recibe: control desde `public/index.php` para un GET HTML no especializado.
- Procesa: orquestación mínima y decisión explícita entre flujo nuevo/legacy.
- Entrega: HTML al cliente mediante el resultado del adaptador o ejecuta el fallback legacy.
- No debe: construir HTML, escapar contenido, leer archivos de vista arbitrarios, construir navegación completa ni capturar excepciones silenciosamente.
- Dependencias permitidas: bridge legacy, Render Adapter, logger mínimo futuro y configuración de activación.
- Dependencias prohibidas: repositorios desde el render, permisos calculados dentro del adaptador, detalles de partials y manipulación de DOM/assets.

### B. Render Adapter

- Recibe: `RenderContext` válido y una ruta de layout fija/configurada internamente.
- Procesa: validación defensiva final, exposición controlada de variables, output buffering y conversión de errores a `RenderException`.
- Entrega: HTML completo como `string`.
- No debe: emitir directamente, cambiar headers, consultar `$_GET`, `$_POST`, sesión, BD o permisos, ni seleccionar contenido de negocio.
- Dependencias permitidas: contexto, excepción, filesystem mediante rutas internas permitidas.
- Dependencias prohibidas: controladores, servicios, repositorios, router y nombres de layout provenientes de HTTP.

### C. App Shell

- Recibe: variables del contrato (`pageTitle`, navegación, contenido, assets y metadatos).
- Procesa: escape de textos/URLs, composición de header/sidebar/main/footer y carga ordenada de assets.
- Entrega: documento HTML.
- No debe: resolver módulo/sección, consultar permisos, ejecutar negocio o leer superglobales.
- Dependencias permitidas: partials fijos de header, sidebar y footer.
- Dependencias prohibidas: controllers, services, repositories y vistas elegidas desde parámetros públicos.

### D. Módulo

- Recibe: entrada normalizada y datos de sus servicios.
- Procesa: caso de uso y selección de sección dentro de una whitelist.
- Entrega: datos de vista, contenido renderizado y declaración de assets/navegación del módulo.
- No debe: crear shell global, modificar navegación de otros módulos o controlar reloj/sidebar.
- Dependencias permitidas: servicios/repositorios propios y renderer de fragmentos autorizado.
- Dependencias prohibidas: layout global y módulos vecinos.

### E. Vista de contenido

- Recibe: datos preparados y helpers de escape necesarios.
- Procesa: presentación del módulo.
- Entrega: fragmento HTML confiable capturado.
- No debe: emitir `doctype`, `<html>`, `<head>`, header/sidebar/footer globales, ejecutar consultas o decidir permisos.
- Dependencias permitidas: partials internos fijos del propio módulo.
- Dependencias prohibidas: rutas de include controladas por usuario.

### F. Assets

- Recibe: URLs declaradas por shell y módulo.
- Procesa: normalización básica, orden estable y deduplicación exacta.
- Entrega: listas de estilos/scripts al contexto.
- No debe: descargar recursos, inspeccionar contenido JS/CSS ni aceptar markup `<script>` libre.
- Dependencias permitidas: `assetBaseUrl` y listas internas.
- Dependencias prohibidas: parámetros HTTP sin validar.

### G. Router/punto de entrada

- Recibe: solicitud HTTP.
- Procesa: precedencia actual de GET/POST y selección de controlador.
- Entrega: control al endpoint adecuado.
- No debe: conocer RenderContext, layout o contenido.
- Dependencias permitidas: controladores.
- Dependencias prohibidas: vistas y adaptador de presentación.

### H. Autorización futura

- Recibe: identidad, roles/permisos y catálogo completo de navegación/acciones.
- Procesa: autorización y filtrado.
- Entrega: módulos, secciones y acciones permitidas.
- No debe: renderizar HTML ni esconder como sustituto de una autorización server-side.
- Dependencias permitidas: servicio RBAC y contexto de usuario.
- Dependencias prohibidas: Render Adapter como fuente de permisos.

## 4. Ciclo de vida de una solicitud

### Flujo textual

1. El cliente solicita una URL existente.
2. `public/index.php` conserva su resolución y precedencia actual.
3. Los POST siguen yendo a sus controladores JSON/XLSX; sólo un GET HTML llega a `DashboardController::index()`.
4. Un bridge normaliza el identificador del módulo contra una whitelist y conserva el fallback `dashboard`.
5. Normaliza la sección según el módulo y sus defaults actuales.
6. Selecciona una vista de contenido desde un mapa interno, nunca desde una ruta HTTP directa.
7. Renderiza/captura el contenido y prepara el contexto.
8. En el futuro, una capa de autorización previa filtra módulos, secciones y acciones.
9. Se construye navegación con URLs actuales y estados activos consistentes.
10. Se resuelven y deduplican assets globales y específicos.
11. El adaptador valida el contexto y captura `app.php`.
12. El controlador emite una única respuesta HTML.
13. Si ocurre un error antes de emitir bytes, se registra de forma segura.
14. Si está habilitado el fallback, el controlador requiere una sola vez `importar.php`; nunca concatena ambas respuestas.

### Diagrama ASCII

```text
HTTP request
     |
     v
public/index.php ------------------------------+
     |                                         |
     | POST/operaciones-patio                  | GET HTML general
     v                                         v
endpoint actual                       DashboardController::index()
                                               |
                                      ¿adapter habilitado?
                                         /           \
                                       no             sí
                                       |              |
                              require importar.php    v
                                               LegacyRenderBridge
                                                      |
                                            normalizar módulo/sección
                                                      |
                                            resolver contenido seguro
                                                      |
                                      [RBAC futuro filtra navegación]
                                                      |
                                            construir RenderContext
                                                      |
                                             RenderAdapter::render()
                                                      |
                                         app/Views/layouts/app.php
                                                      |
                                             HTML string completo
                                                      |
                                             respuesta única 200
                                                      |
                                  error antes de salida? --sí--> log seguro
                                                                  |
                                                      fallback legacy único
```

## 5. Contrato de entrada

### Alternativas

| Alternativa | Ventajas | Desventajas | Decisión |
|---|---|---|---|
| Array asociativo | Encaja con vistas PHP y migración rápida | Errores de claves/tipos, mutabilidad, contrato implícito | Sólo en la frontera del bridge temporal |
| DTO mutable | Tipos y autocompletado | Estados parciales y setters innecesarios | No |
| Contexto inmutable | Invariantes, testabilidad, contrato explícito | Algo más de código inicial | **Sí** |
| Array → contexto | Facilita migración sin contaminar renderer | Requiere factoría temporal | **Combinación recomendada** |

Se recomienda `RenderContext` inmutable con constructor/factoría explícita. El bridge puede aceptar/crear temporalmente un array legacy, pero debe convertirlo inmediatamente al contexto tipado. El adaptador sólo acepta `RenderContext`.

### Campos

| Campo | Tipo | Obligatorio | Default | Validación/normalización |
|---|---|---:|---|---|
| `pageTitle` | `string` | No | `VASCOR OPS` | trim; no vacío; se escapa al emitir |
| `documentLanguage` | `string` | No | `es` | patrón BCP-47 reducido, p. ej. `^[A-Za-z]{2,3}(-[A-Za-z0-9]{2,8})*$` |
| `assetBaseUrl` | `string` | Sí en producción | ninguno | trim final `/`; sólo base local/HTTPS autorizada |
| `activeModule` | `string` | Sí | — | debe existir en catálogo normalizado |
| `activeSection` | `string` | No | `''`/default del módulo | ID seguro y sección conocida |
| `modules` | `array` | Sí | `[]` sólo para error/demo | esquema validado por elemento |
| `moduleNavigation` | `TrustedHtml` conceptual/string interno | No | `''` | sólo salida de vista interna |
| `content` | `TrustedHtml` conceptual/string interno | Sí | — | no vacío salvo estados autorizados |
| `additionalStyles` | `list<string>` | No | `[]` | URL válida, deduplicada, orden estable |
| `additionalScripts` | `list<string>` | No | `[]` | URL válida, deduplicada, orden estable |
| `header` | `array<string,string>` | No | defaults del partial | sólo claves conocidas |
| `footer` | `array<string,string>` | No | defaults del partial | sólo claves conocidas |
| `sidebarLabel` | `string` | No | `Navegación principal` | trim; texto plano |

Esquema mínimo de cada módulo: `id`, `label`, `url`, `icon`, `active`, `sections`. Cada sección: `id`, `label`, `url`, `active`; `disabled` y `badge` se reservan para evolución posterior.

### Reglas del contexto

- Constructor/factoría rechaza tipos incorrectos con `InvalidArgumentException` o una excepción de contexto especializada futura.
- Campos desconocidos en la factoría legacy: rechazar en desarrollo; ignorar con aviso explícito sólo durante una ventana de migración controlada. La API tipada no los admite.
- Campos obligatorios faltantes: excepción; no inventar módulo/contenido.
- Defaults visuales viven en el contexto/layout, no en el controlador.
- Listas se reindexan y deduplican manteniendo la primera aparición.
- El contexto no lee superglobales.
- Usar propiedades `private readonly` disponibles desde PHP 8.1, sin características exclusivas de 8.3; esto funciona en el PHP 8.2 actual y en 8.3.
- Evitar `mixed` salvo en factoría legacy; exponer getters tipados o propiedades readonly bien definidas.

## 6. Contrato de salida

| Alternativa | Compatibilidad actual | Pruebas | Headers/errores | Decisión |
|---|---|---|---|---|
| Impresión directa | Alta | Baja; difícil detectar salida parcial | Mezcla responsabilidades | No |
| HTML `string` | Alta | Excelente | El controlador conserva emisión/headers | **Sí ahora** |
| Objeto `Response` | Media; Core Response es hoy esqueleto | Excelente | Mejor a futuro | Después del router formal |
| Ruta + contexto | Delega demasiado al controlador | Media | Errores tardíos | No |
| `RenderResult` | Flexible | Buena | Añade objeto sin uso actual | Posponer |

El adaptador debe devolver **HTML completo como `string`**. Así puede probarse sin emitir, el fallback puede decidirse antes de la respuesta y el controlador actual puede hacer `echo` sólo después del éxito.

En el futuro, un controlador/router formal envolverá ese string en un `Response` con status y headers. No se crea `RenderResult` hasta necesitar metadatos reales adicionales.

## 7. Diseño mínimo de clases y archivos

### Implementar en la siguiente fase

| Archivo propuesto | Responsabilidad | Dependencias | Riesgo de sobrearquitectura |
|---|---|---|---|
| `app/Core/Rendering/RenderContext.php` | Contexto inmutable, normalización y contrato | Ninguna dependencia de negocio | Bajo |
| `app/Core/Rendering/RenderAdapter.php` | Render seguro del layout a string | `RenderContext`, `RenderException` | Bajo |
| `app/Core/Rendering/Exceptions/RenderException.php` | Envolver fallos de render sin filtrar rutas al usuario | `RuntimeException` | Bajo |
| `tests/unit/Rendering/RenderAdapterTest.php` o runner equivalente | Prueba aislada del contrato/render | Fixtures locales | Bajo |

### Implementar al integrar, no en el núcleo inicial

| Archivo | Propósito | Momento |
|---|---|---|
| `app/Rendering/Legacy/LegacyRenderBridge.php` | Traducir `modulo`, `seccion`, `BASE_URL` y contenido actual | Después de probar el adaptador aislado |
| Vista de contenido paralela | Producir fragmento sin shell | Fase de integración |

### Posponer

- `RenderResult.php`: innecesario mientras sólo se devuelve HTML.
- `AssetCollection.php`: añadir cuando las listas simples ya no cubran dependencias/atributos.
- `NavigationBuilder.php`: añadir con RBAC o segundo proveedor de navegación.
- múltiples adapters por módulo: el renderer es global; los bridges/proveedores varían.
- manifest de assets/módulos: prematuro con dos módulos activos.

Ubicar el núcleo técnico en `app/Core/Rendering`. El bridge legacy no pertenece a Core porque conoce nombres y defaults de VASCOR OPS; debe vivir en un namespace de integración/legacy.

## 8. API pública del adaptador

Firma conceptual recomendada:

```php
public function render(RenderContext $context): string;
```

Características:

- el layout es una dependencia fija inyectada en el constructor o resuelta desde una constante interna validada; no es parámetro HTTP ni se cambia por solicitud;
- valida que layout y partials esperados sean archivos legibles dentro de directorios permitidos;
- convierte el contexto a variables locales explícitas, no usa `extract()` indiscriminado;
- abre buffer, requiere el layout y devuelve el buffer;
- ante `Throwable`, limpia todos los niveles de buffer que abrió y lanza `RenderException` conservando la excepción anterior sólo para logging interno;
- no hace fallback internamente: el controlador debe decidirlo para evitar respuestas dobles;
- no registra directamente en la primera versión; permite que el caller registre la excepción con un logger/callback futuro;
- una misma instancia no mantiene estado mutable por solicitud, por lo que es fácil de probar.

No se recomienda `renderLayout($layout, $context)` porque convertiría la ruta del layout en superficie de ataque y ampliaría una variabilidad que hoy no existe. Tampoco `renderModule()`: el adaptador no selecciona módulos.

## 9. Contenido, confianza y escape

### Deben escaparse al emitir

- `pageTitle`, `documentLanguage`, textos de header/footer y `sidebarLabel`;
- etiquetas, IDs visibles e iconos textuales de módulos/secciones;
- badges textuales;
- URLs usadas en `href`/`src` después de validarlas;
- cualquier dato de negocio interpolado por vistas.

Usar `htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')` para texto/atributos. La validación de URL precede al escape; escapar no convierte una URL insegura en segura.

### HTML confiable que no debe escaparse otra vez

- `$content` capturado de una vista interna autorizada;
- `$moduleNavigation` generada por un builder/vista interna autorizada.

Estos valores no deben aceptar texto crudo del usuario marcado como “confiable”. Conceptualmente son `TrustedHtml`; en la implementación mínima pueden ser strings creados sólo por métodos internos con nombres explícitos. Si el origen se amplía, introducir un value object `TrustedHtml` antes de aceptar más proveedores.

### Validar antes del contexto

- módulos/secciones contra catálogos internos;
- rutas de vistas contra un mapa fijo;
- URLs y esquemas permitidos;
- arrays de navegación y assets;
- iconos: texto/SVG interno conocido, nunca HTML arbitrario;
- nombres de clases/IDs sólo desde valores normalizados.

No aceptar scripts inline, etiquetas `<style>` o HTML de navegación desde GET/POST. Las políticas CSP/nonce pueden incorporarse en una fase de seguridad posterior.

## 10. Diseño de assets

### Alternativas

| Alternativa | Uso recomendado |
|---|---|
| Listas simples de URL | Primera versión: suficiente, clara y compatible con el layout actual |
| Colección tipada | Cuando se necesiten dependencias, atributos, integridad o posición |
| Registro por módulo | Cuando exista catálogo modular real y carga dinámica |
| Manifest | Cuando haya build/versionado/hash de frontend |

### Estrategia incremental

1. El App Shell conserva assets globales fijos: fuente, Design System, `app-shell.css`, `app-shell.js`.
2. `RenderContext` recibe listas adicionales ya resueltas por el bridge/módulo.
3. Normalizar URL, eliminar duplicados exactos y preservar primera aparición.
4. Orden CSS: fuente → Design System → App Shell → CSS base temporal del contenido → CSS del módulo.
5. Orden JS: `app-shell.js` → script funcional del módulo; todos externos y `defer`.
6. `importador.js` sólo se carga en FerroCheck y, antes del corte, debe dejar de controlar reloj/sidebar legacy cuando esté presente el App Shell.
7. CSS/JS de Control de Escáneres sólo se declara donde ya corresponda funcionalmente; no activar scripts inactivos por accidente.
8. No permitir URL `javascript:`, `data:` o protocolo relativo. Assets locales deben iniciar en `assetBaseUrl`; externos sólo desde hosts HTTPS autorizados.
9. No deduplicar por nombre parcial: comparar URL normalizada completa para no unir versiones distintas.

Una `AssetCollection` se justifica cuando se requiera expresar `defer`, `async`, dependencias, SRI, posición head/body o prioridad por asset. Hasta entonces sería sobrearquitectura.

## 11. Navegación

La navegación debe modelarse como datos, no HTML proveniente del request:

```text
module: id, label, url, icon, active, sections[]
section: id, label, url, active, disabled?, badge?
```

Reglas:

- `activeModule` y `activeSection` se normalizan antes de construir la navegación.
- Debe existir como máximo un módulo activo y una sección activa dentro de él.
- URLs se construyen con `BASE_URL` y los query params actuales; no se renombran.
- `disabled` futuro produce semántica accesible (`aria-disabled`) y no una URL operativa.
- badges son texto escapado, no HTML.
- iconos son valores internos conocidos.
- elementos no autorizados se eliminan antes de llegar al contexto; no basta ocultarlos con CSS.

El adaptador no consulta permisos. En la primera integración, `LegacyRenderBridge` puede construir la navegación estática actual. Cuando llegue RBAC, un `NavigationPolicy`/servicio de autorización externo filtrará el catálogo y entregará el resultado al bridge/contexto. `NavigationBuilder` se pospone hasta que existan al menos dos fuentes o reglas de filtrado reales.

## 12. Bridge de compatibilidad legacy

Nombre recomendado: `App\Rendering\Legacy\LegacyRenderBridge`.

Responsabilidades:

- recibir valores explícitos de módulo/sección y `BASE_URL` desde el caller; durante la transición puede existir una factoría `fromCurrentRequest()` fuera del Core, pero no dentro del adaptador;
- reproducir exactamente defaults: módulo `dashboard`, sección FerroCheck `consulta-vin`, sección CE `dashboard`;
- conservar IDs públicos, URLs y alias de secciones (`reportes`/`reporte`);
- seleccionar contenido desde mapas internos;
- capturar las vistas de contenido autorizadas;
- construir navegación/assets y devolver `RenderContext`;
- traducir temporalmente `$vistaActual` y `$contenidoModulo` para Control de Escáneres.

No debe incluir el documento completo `importar.php` como `$content`, porque produciría doble shell. El archivo legacy permanece intacto exclusivamente como fallback.

Deuda creada:

- duplicación temporal de selección/defaults;
- conocimiento de variables legacy;
- posible vista de contenido paralela;
- condiciones de assets por módulo.

Para evitar permanencia:

- marcar clase `@internal` y `@deprecated` desde su creación, con condición explícita de retiro;
- no permitir que módulos nuevos dependan de ella;
- cubrirla con pruebas de equivalencia, no convertirla en API pública;
- registrar una tarea de eliminación cuando todas las secciones produzcan contexto nativo y tras dos ciclos estables de regresión;
- no añadir nuevas reglas legacy después del corte salvo corrección crítica documentada.

## 13. Fallback legacy

### Alternativas evaluadas

| Alternativa | Evaluación |
|---|---|
| Parámetro GET | Inseguro: expone modos internos y complica pruebas |
| Variable local ad hoc | Reversible pero difícil de operar consistentemente |
| Constante/configuración | Simple, auditable y default seguro |
| `try/catch` dentro del adaptador | Incorrecto: mezcla renderer con política de respuesta |
| Fallback explícito en controlador | **Elegido**, combinado con configuración |

Diseño recomendado:

- constante/configuración `APP_SHELL_ENABLED`, default `false` hasta el corte;
- `DashboardController::index()` elige primero el modo;
- en modo nuevo, inicia sin salida previa, construye contexto y renderiza dentro de `try/catch`;
- sólo después del éxito hace `echo $html`;
- ante `RenderException`/`Throwable`, registra un evento seguro y, si la política de despliegue permite fallback, ejecuta una única vez `require importar.php`;
- si ya se enviaron headers/salida o el fallo ocurre después del `echo`, no intentar segundo documento; devolver página de error genérica/controlada según entorno;
- nunca capturar errores de un POST para convertirlos en página legacy.

Para impedir duplicación, el adapter devuelve string y limpia su buffer al fallar. El controlador no abre un buffer que mezcle ambos caminos. El fallback no repite operaciones de datos: sólo se usa en GET HTML antes de renderizar.

## 14. Manejo de errores

| Categoría | Desarrollo | Producción | Log | HTTP/fallback | Mensaje visible |
|---|---|---|---|---|---|
| Contexto inválido | Excepción con campo | Sin detalle | warning/error con campo, sin valor sensible | 500 o fallback | “No fue posible cargar la interfaz.” |
| Layout no encontrado | Ruta interna en excepción | Ocultar ruta | critical con identificador de layout | 500/fallback | Genérico |
| Partial no encontrado | Detalle técnico | Ocultar ruta | error | 500/fallback | Genérico |
| Contenido no renderizable | Excepción anterior | Ocultar datos | error con módulo/sección | 500/fallback | Genérico |
| Asset inválido | Fallo estricto inicialmente | Omitir sólo si se definió como opcional; preferir fallo | warning/error, URL sanitizada | fallback según criticidad | Genérico |
| Navegación inválida | Identificar índice/campo | Ocultar estructura | warning/error | 500/fallback | Genérico |
| Excepción inesperada | Traza en log de desarrollo | Sin traza visible | critical con correlación | 500/fallback si seguro | Genérico |

No incluir rutas absolutas, stack traces, SQL, payloads, variables de entorno ni secretos en la respuesta. Un 404 de módulo/sección sólo debe usarse cuando el router formalice ese contrato; para conservar compatibilidad inicial se mantienen los fallbacks visuales actuales.

## 15. Logging mínimo

- Destino inicial: canal/archivo específico de aplicación, por ejemplo `logs/render_adapter.log`, sujeto a política de `.gitignore` y permisos; no reutilizar el log de Excel.
- Niveles: `warning` para fallback recuperado/entrada inválida no sensible; `error` para render fallido; `critical` si tampoco funciona el fallback.
- Campos: timestamp, event name, módulo/sección normalizados, clase de excepción, código interno, modo (`new/legacy`), resultado del fallback y correlation ID futuro.
- No registrar: HTML completo, POST/FILES, VINs/equipos, cookies, sesión, tokens, credenciales, rutas de archivos visibles al usuario, stack trace en producción compartida ni contenido de vistas.
- Fallo del logger: nunca debe romper render ni generar salida; usar fallback de `error_log()` con mensaje mínimo.
- Correlación: generar/recibir un ID no sensible cuando exista middleware; mientras tanto, ID aleatorio por fallo.
- Rotación/retención: definir con operación antes de producción; no implementar ahora.

## 16. Testabilidad

### Unitarias sin base de datos

- contexto válido completo;
- defaults y normalización;
- rechazo de obligatorios faltantes/campos inválidos;
- escape de texto y atributos mediante fixture de layout;
- preservación de `TrustedHtml` sin doble escape;
- deduplicación y orden de assets;
- único módulo/sección activa;
- layout ausente/no legible;
- excepción durante vista y limpieza del buffer;
- el adapter no imprime directamente;
- fallback del orquestador con renderer falso que falla.

### Integración sin base de datos

- un `doctype`, `.app-shell`, header, sidebar, main, footer, backdrop y toggle;
- cero shell legacy (`.dashboard-shell`, `.topbar`, `.sidebar-backdrop`);
- cada asset una sola vez y en orden;
- IDs únicos;
- contenido fixture FerroCheck y CE;
- módulo/sección activos correctos;
- error seguro sin rutas internas.

### Regresión

- las 114 pruebas GET actuales;
- URLs y query strings actuales;
- HTTP 200 y assets;
- responsive en cinco viewports;
- consola sin errores;
- sidebar, backdrop, Escape y `aria-expanded`;
- despacho POST sin cambios mediante pruebas de routing/mocks, no ejecutando escrituras reales.

No requieren BD: contexto, adapter, assets, navegación, estructura, fixtures y fallback simulado. Las smoke actuales consultan el servidor y algunas páginas podrían usar servicios de lectura; deben ejecutarse con la misma BD de prueba/entorno actual. Requieren control de datos o deben evitarse: importación final (`accion=importar`), registro/entrega/recepción de escáneres y cualquier endpoint POST no confirmado como sólo lectura.

## 17. Seguridad

Controles concretos:

- **XSS:** escape contextual de texto/atributos; HTML confiable sólo desde vistas internas; no aceptar markup en navegación/assets.
- **Path traversal:** layout fijo y mapa de vistas con rutas canónicas; verificar que `realpath()` permanezca dentro del directorio autorizado.
- **Inclusión arbitraria:** ningún nombre de layout, partial o vista proviene directamente de `modulo`/`seccion`; usar whitelist ID→archivo.
- **URLs inseguras:** permitir rutas locales bajo `assetBaseUrl` y hosts HTTPS autorizados; rechazar `javascript:`, `data:`, controles y URLs ambiguas.
- **Scripts inyectados:** `additionalScripts` contiene sólo URLs validadas; no código inline ni strings HTML.
- **Contenido confiable:** origen explícito y documentado; datos del usuario se escapan dentro de la vista antes de formar el fragmento.
- **Navegación manipulada:** IDs contra catálogo; una selección desconocida cae en fallback conocido, no en include dinámico.
- **Errores sensibles:** mensaje genérico en producción; log restringido y sin payloads/secretos.
- **Permisos:** ocultar navegación no sustituye autorización del endpoint. Cada acción futura debe autorizarse server-side antes del caso de uso.
- **Cabeceras:** CSP, `X-Content-Type-Options` y políticas relacionadas pertenecen a una fase HTTP posterior; el adapter no debe emitirlas.

## 18. Rendimiento

- Output buffering de un documento añade una copia de memoria proporcional al HTML; para las páginas actuales el costo esperado es bajo y habilita atomicidad/fallback/testabilidad.
- Evitar buffers anidados innecesarios; cada renderer debe cerrar exactamente los niveles que abrió.
- `require` de layout/partials ocurre una vez por solicitud; no usar `require_once` para vistas que deben renderizarse por solicitud.
- Deduplicar assets evita solicitudes y listeners repetidos.
- Navegación pequeña se construye por solicitud; no necesita caché.
- El fallback sólo se ejecuta en error, nunca debe renderizar preventivamente ambos caminos.
- No cachear HTML con estado activo/usuario hasta definir claves, invalidación y RBAC.
- No introducir manifests, DI container o template engine por rendimiento antes de medir.

Impacto esperado: despreciable frente a acceso a servicios/BD; la prioridad es corrección y rollback.

## 19. Convivencia con RBAC futuro

El adapter no autentica, no autoriza y no consulta permisos. Flujo futuro:

```text
Identidad autenticada
  → AuthorizationService evalúa permisos
  → ModuleCatalog entrega catálogo completo
  → NavigationPolicy filtra módulos/secciones/acciones
  → proveedor de módulo rechaza acciones no autorizadas server-side
  → RenderContext recibe navegación ya filtrada
  → RenderAdapter sólo presenta
```

El punto de filtrado está después de normalizar la ruta solicitada y antes de construir el contexto. Si el usuario solicita un módulo no autorizado, el controlador/política decide 403 o redirección segura; el adapter no decide. Las acciones dentro de `$content` deben filtrarse o autorizarse antes de renderizar, y sus endpoints deben repetir la autorización.

## 20. Decisiones arquitectónicas

| Decisión | Alternativas | Elección | Motivo | Riesgo | Revisar cuando |
|---|---|---|---|---|---|
| Entrada | array, DTO, contexto | Contexto inmutable + factoría legacy temporal | Tipos e invariantes con migración gradual | Boilerplate | El bridge sea retirado |
| Salida | echo, string, Response, RenderResult | HTML string | Atomicidad, test y compatibilidad | Memoria del buffer | Exista router/Response real |
| Assets | arrays, colección, registro, manifest | Listas simples normalizadas | Cubre layout actual | Metadatos limitados | Se necesite SRI/dependencias/build |
| Adapters | uno global o uno por módulo | Un RenderAdapter | Render es transversal | Adapter “god object” si absorbe selección | Aparezca un segundo motor/layout |
| Selección de módulo | adapter o bridge | Bridge/proveedor externo | Evita lógica de negocio en Core | Duplicación temporal | Contextos nativos por módulo |
| Fallback | GET flag, try interno, controller | Config + fallback explícito en controller | Simple y una respuesta | Ocultar fallos si se abusa | Tras estabilidad y observabilidad |
| Ubicación | Controllers, Views, Core | `app/Core/Rendering` | Infraestructura neutral | Core prematuro | Si sólo queda un uso específico |
| HTML confiable | string libre, sanitizar, value object | String de origen interno; `TrustedHtml` después | Mínimo viable | Uso incorrecto futuro | Haya más proveedores |
| Navegación | HTML, builder, arrays | Datos validados; bridge inicialmente | Escape/RBAC futuro | Esquema informal inicial | Llegue RBAC o módulos dinámicos |
| Errores | mostrar excepción, silencioso, tipado | `RenderException` + log + mensaje genérico | Seguridad y diagnóstico | Pérdida de detalle si log falla | Exista observabilidad central |
| Compatibilidad PHP | sólo 8.3 o 8.2+ | Sintaxis 8.2 compatible con 8.3 | Entorno actual es 8.2.12 | No usar mejoras 8.3 | Runtime productivo sea homogéneo |

## 21. Implementación mínima propuesta

La siguiente fase técnica aislada debe limitarse a:

1. `RenderContext`: una clase inmutable con los 13 campos del layout y validación mínima.
2. `RenderException`: una excepción única del subsistema.
3. `RenderAdapter`: constructor con layout fijo y `render(RenderContext): string`.
4. Una prueba aislada/fixture que confirme HTML, escape, contenido confiable, fallo y limpieza de buffer.
5. Actualización documental breve si una decisión cambia.

Debe **posponerse**: bridge conectado a request, modificación de `DashboardController`, vista de contenido, feature flag productiva, JS, AssetCollection, NavigationBuilder, RenderResult, Response formal, RBAC, logging persistente, caché y corte de producción.

Así el núcleo se valida sin tocar rutas ni sistema activo.

## 22. Plan de implementación por fases

| Paso | Archivos futuros | Riesgo | Validación | Rollback | Criterio de aceptación |
|---:|---|---|---|---|---|
| 1. Contexto | `RenderContext.php`, prueba | Bajo | Tipos, defaults, inválidos, PHP lint | Revert de archivos nuevos | Invariantes cubiertas |
| 2. Excepción | `RenderException.php`, prueba | Bajo | Herencia/mensaje interno | Revert | Fallos distinguibles |
| 3. Adapter | `RenderAdapter.php`, fixture/prueba | Medio | HTML string, buffer limpio, layout ausente | Revert | No imprime ni fuga rutas |
| 4. Render aislado | fixture/layout de prueba | Bajo | Shell único y escape | Retirar fixture | Prueba determinista sin BD |
| 5. Bridge legacy | `LegacyRenderBridge.php`, vista paralela | Medio-alto | Equivalencia módulo/sección/DOM | Revert de archivos nuevos | Contextos para todas las GET |
| 6. Estructurales | `smoke.php`/tests nuevas | Bajo | Un shell/assets/IDs | Revert pruebas | Fallan antes y pasan tras corte |
| 7. Flag | config y controller, autorizados aparte | Medio | default legacy; modo nuevo controlado | desactivar flag | Ningún cambio con default |
| 8. Validar | sin cambios o fixes mínimos | Medio | 114+ smoke, manual, consola, responsive | desactivar flag/revert | Matriz completa verde |
| 9. Corte | config/controller | Medio | repetición completa y observación | `git revert` del commit de corte | App Shell activo estable |
| 10. Retirar fallback | bridge/controller/docs | Alto si prematuro | ciclos estables y observabilidad | revert | Legacy sin consumidores |

Cada paso debe ser un commit pequeño cuando cambie código, sin mezclar refactor de módulos. No continuar si una validación del paso falla.

## 23. Rollback operativo

Antes del corte, el default permanece legacy. Durante integración, desactivar la constante/configuración devuelve el flujo a:

```php
require __DIR__ . '/../Views/inventario/importar.php';
```

Después de un commit aislado de corte, preferir:

```bash
git revert <hash-del-commit-de-corte>
```

No usar `git reset --hard`, `git clean` ni restauraciones amplias. Confirmar árbol limpio antes del revert. El rollback no toca rutas, BD, migraciones ni commits previos. El fallback automático debe usarse sólo en GET, antes de emitir salida y sin ejecutar dos veces servicios de negocio.

## 24. Criterios de aceptación del diseño/implementación futura

- API pública única: `render(RenderContext $context): string`.
- Contexto inmutable, tipado y compatible con PHP 8.2/8.3.
- Layout fijo no controlable por HTTP.
- HTML confiable diferenciado de texto escapado.
- Assets validados, ordenados y deduplicados.
- Adapter sin superglobales, sesión, permisos, servicios o BD.
- Buffer completamente limpio ante fallo.
- Fallback explícito produce una sola respuesta.
- Pruebas unitarias sin BD y regresión `114 PASS / 0 FAIL`.
- Un solo shell/header/sidebar/main/footer/backdrop y cero shell legacy en modo nuevo.
- Rutas, parámetros y POST sin cambios.
- Seguridad de includes/URLs/errores comprobada.
- RBAC puede filtrar antes del contexto sin modificar el renderer.
- Rollback documentado y verificable.

## 25. Fuera de alcance

- Implementar o activar el Render Adapter.
- Modificar `DashboardController.php`, `importar.php`, rutas o punto de entrada.
- Crear bridge o vistas de contenido en esta fase documental.
- Modificar JavaScript, CSS, App Shell o módulos actuales.
- Dividir FerroCheck o Control de Escáneres.
- Implementar autenticación/RBAC.
- Cambiar servicios, repositorios, consultas, tablas o datos.
- Ejecutar migraciones o pruebas POST.
- Introducir framework, contenedor DI, motor de templates, manifest o caché.
- Hacer commit, push, merge o continuar a la siguiente fase.

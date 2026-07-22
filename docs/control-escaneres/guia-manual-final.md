# Guía manual final — Control de Escáneres

Base local: `http://localhost/Ferrocheck/public/index.php`. Si Apache usa otro alias, sustituye únicamente ese prefijo.

## Flujo guiado en PC

1. Abre el catálogo: `http://localhost/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=catalogo`.
2. Pulsa **Registrar escáner**. Captura un código de prueba reservado, TAG, marca/modelo y fotografía. Guarda y verifica errores claros ante código/TAG/serie/IMEI/ICCID duplicado.
3. En el catálogo, abre **Expediente** y confirma datos, fotografía, estado, QR e identificadores enmascarados.
4. Abre el QR grande desde **Imprimir QR**. Usa imprimir del navegador. Debajo deben aparecer código y TAG; **Descargar QR** entrega PNG.
5. Regresa al catálogo y elige **Entrega**. Captura persona, empleado, área, supervisor, responsable, turno, checklist, 1–5 estrellas, observaciones, fotos y ambas firmas. Limpia/repite una firma para probar el canvas. Confirma.
6. Repite Entrega sobre el mismo equipo: debe rechazarse por movimiento abierto.
7. Abre Dashboard: `http://localhost/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=dashboard`. El equipo debe figurar entregado y pendiente por regresar.
8. Desde catálogo/expediente pulsa **Recibir**. Verifica que aparecen checklist y fotos iniciales. Captura condición final, fotos y firmas.
9. Declara un deterioro importante. Sin marcar confirmación humana, el guardado debe rechazarse y revertirse. Marca la confirmación y guarda; debe crearse diferencia e incidencia y quedar `pendiente_reparacion`.
10. Abre **Incidencias**: agrega seguimiento, cambia severidad y resuelve o cancela con motivo. Revisa fotos y auditoría en expediente.
11. Abre **Mantenimiento**: envía el equipo con técnico/proveedor, diagnóstico, costo, fecha estimada y fotos. Confirma que Entrega se bloquea. Registra regreso con resultado y estado final.
12. Abre el expediente y valida movimientos, inspecciones, comparaciones, fotos, firmas, incidencias/seguimientos, mantenimientos, historial, auditoría y enlaces PDF.
13. En un movimiento abre **Ver comprobante PDF**. Comprueba QR, folio, datos, checklists, valoraciones, diferencias, fotos y firmas; imprime/descarga desde el visor.
14. Abre Reportes: `http://localhost/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=reporte`. Prueba cada tipo y filtros; descarga PDF y Excel y confirma que no contienen IMEI/ICCID/teléfono.
15. Revisa Dashboard nuevamente: la recepción debe descontar pendientes y sumar recibidos.

## Teléfono y QR

1. Conecta PC y teléfono a la misma red.
2. Obtén la IPv4 LAN de la PC con `ipconfig`; no uses `localhost` en el teléfono.
3. Abre `http://IP_DE_LA_PC/Ferrocheck/public/index.php?modulo=control-escaneres&seccion=catalogo`.
4. Pulsa **Escanear QR**, concede cámara y apunta a la etiqueta. Debe preferir cámara trasera, cerrar el stream y abrir el expediente.
5. Si el navegador bloquea cámara sobre HTTP, prueba la URL HTTPS local autorizada. Si no existe HTTPS, usa el campo manual; no desactives la seguridad del navegador.
6. Deniega permiso y confirma que aparece un mensaje recuperable. Cierra el diálogo y verifica que el indicador de cámara se apaga.

## Comprobaciones visuales

Probar 1440×900, 768×1024 y 390×844: un solo header/sidebar/footer, navegación operable, tablas desplazables dentro de su contenedor, sin scroll horizontal global, botones visibles, canvas táctil y previews removibles. En DevTools: Console sin errores; Network sin 404 ni assets duplicados.

## Rutas directas

- Catálogo: `?modulo=control-escaneres&seccion=catalogo`
- Alta: `?modulo=control-escaneres&seccion=registrar`
- Importar: `?modulo=control-escaneres&seccion=importar-inventario`
- Áreas: `?modulo=control-escaneres&seccion=areas`
- Dashboard: `?modulo=control-escaneres&seccion=dashboard`
- Reportes: `?modulo=control-escaneres&seccion=reporte`
- Expediente: `?modulo=control-escaneres&seccion=expediente&scanner_id=ID`

No usar POST sobre datos reales durante una revisión sólo visual. Los pasos operativos anteriores crean datos y deben ejecutarse únicamente con un escáner de prueba autorizado.

# Referencia final productiva de Consist Rail

## Custodia

Archivo: `Consist Rail del 29 al 30 de Julio de 2026.xlsx`

SHA-256 inicial: `50d3557f9c0c95f5d8555f7b373fa424ef7544d27a5b276ad400b2d692ffa4d6`

El archivo se inspeccionó en modo de sólo lectura y no fue recalculado ni
guardado.

## Estructura comprobada

- `Consist`: 437 VIN, 437 VIN únicos y 55 plataformas.
- `Summary`: 55 filas, una por plataforma, con `UT` total de 437.
- `Version`: oculta.
- 52 plataformas contienen ocho unidades y tres contienen siete.
- `Consist` usa A:N y `Summary` usa A:G.
- Las dos hojas contienen valores cacheados, sin fórmulas.

El archivo conserva dos conexiones históricas aunque ya no contiene las hojas
de entrada. La exportación de VASCOR OPS no las replica: crea un libro nuevo y
estático.

## Regla reconciliada de Summary

1. Contar la frecuencia de cada VIN en el `Consist`.
2. Excluir todas las ocurrencias de cualquier VIN con frecuencia distinta de
   uno.
3. Agrupar las filas restantes por `fdTransportationName1`, conservando el
   orden de primera aparición.
4. Tomar de la primera fila de cada plataforma `Carrier`, `fdTrack`,
   `fdDestinationLocation`, `Cruce` y `Shipper`.
5. Calcular `UT` como el total de filas restantes de esa plataforma.

La regla reproduce exactamente las 55 filas y las 437 unidades reconciliadas
de la referencia.

## Contrato visual comprobado

Las dos tablas oficiales utilizan `TableStyleMedium2`, franjas alternas,
alineación centrada y encabezado negro. La referencia no congela paneles.
Consist conserva los anchos A:N y Summary los anchos A:G. La exportación crea
tablas nuevas sin conexiones históricas.

## Nombre por periodo

El servidor valida dos fechas ISO `Y-m-d` en la zona horaria de la aplicación.
La fecha final no puede preceder a la inicial. El nombre usa meses en español:

- mismo día: `Consist Rail del DD de Mes de YYYY.xlsx`;
- mismo mes: `Consist Rail del DD al DD de Mes de YYYY.xlsx`;
- meses distintos: expresa ambos días y meses;
- años distintos: expresa día, mes y año en ambos extremos.

Los caracteres inválidos para nombres de archivo se reemplazan y el navegador
no suministra el nombre entregable.

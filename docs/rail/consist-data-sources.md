# Consist Rail: fuentes y formato documental

## Evidencia revisada

No existen archivos operativos Vehicle Load Report, Shippers o CNACS, ni plantillas
Excel/PDF de un Consist final dentro del repositorio, `storage/rail/`, adjuntos
disponibles o historial Git. La evidencia actual se limita a la configuración de
identidad y a fixtures sintéticos de pruebas.

## Columnas respaldadas

### Vehicle Load Report

- `fdWholeVIN`
- `fdTransportationName1`
- `fdLoadId`
- `fdTrack`
- `fdDestinationLocation`
- `fdShipperNumberPrefix`

### Shippers

- `fdWholeVIN1`
- `fdPedimentoType`
- `fdPortCode`
- `fdBillNumber`
- `fdBillDate`
- `fdPrintDate`

### CNACS

- `VIN`
- `InvoiceNo`
- `NumRemesa`
- `BrokerId`
- `H/SCODE`
- `BodyModel`
- `Conveyance`

La única equivalencia automática respaldada es la identidad VIN. Los demás
campos se conservan con su encabezado original y se muestran separados por
fuente. No se comparan entre sí.

## Datos generales

El borrador persistente utiliza únicamente campos administrativos:

- folio interno generado por el servidor;
- fecha del Consist;
- descripción;
- observaciones;
- usuario creador;
- cantidad de unidades.

No se asignan automáticamente tren, viaje, origen, destino, ferrocarril, cliente,
fecha de salida o número de lote. Algunos encabezados podrían estar relacionados
con esos conceptos, pero se requiere un archivo real y validación operativa antes
de establecer una regla.

## Documento oficial pendiente

No se localizó formato oficial de Consist, logotipos específicos, firmas,
encabezados, orden de columnas o distribución imprimible. La exportación final a
Excel/PDF queda fuera de esta fase hasta recibir y validar una plantilla real.

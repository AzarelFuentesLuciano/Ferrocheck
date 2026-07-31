# Mapa de columnas de Consist

La tabla efectiva es `Tabla_Consulta_desde_Excel_Files`, A1:O625. Las columnas
P:AR pertenecen a verificaciones y pivotes, no al resultado principal.

| Col. | Salida | Fuente o regla reconstruida | Fallback/observación |
|---|---|---|---|
| A | `fdTransportationName1` | Vehicle Load, conexión SQL | Filtra VIN `Like '3C%'`; ordena por `fdTrack` |
| B | `fdwholevin` | Vehicle Load | Clave principal aparente |
| C | `Route Code` | VLOOKUP B:L de Vehicle Load por VIN, índice 4 | Error de Excel si no existe |
| D | `Carrier` | VLOOKUP B:M de Vehicle Load por VIN, índice 12 | Error de Excel si no existe |
| E | `Bloque` | VLOOKUP de Route Code en `[1]route_codes!A:C`, índice 3 | `REVISAR` si error o vacío |
| F | `fdTrack` | Vehicle Load | Usado para ordenar |
| G | `Shipper` | VLOOKUP del VIN en `Shippers!A:G`, índice 3 | `Pending` si no existe |
| H | `Upfit Cost` | VLOOKUP del VIN en `Shippers!N:AB`, índice 19 | Vacío si no existe |
| I | `fdDestinationLocation` | VLOOKUP B:L de Vehicle Load, índice 11 | Error de Excel si no existe |
| J | `Cruce` | Si Carrier=`KCSM`, `Laredo`; si no, VLOOKUP Route Code en catálogo A:D | `REVISAR` si falla |
| K | `CNACS-Pedimento` | VLOOKUP del VIN en `CNACS!A:D`, índice 4 | Mensaje de VIN faltante o `Pending` |
| L | `fdManufacturerRouteCode2` | Vehicle Load | Campo directo |
| M | `fdLoadID` | Vehicle Load | Campo directo |
| N | `fdCustom1` | Vehicle Load | Campo directo |
| O | `fdCustom2` | Vehicle Load | Campo directo |

## Entradas reales

### Vehicle Load Report

`fdTransportationName1`, `fdwholevin`, `fdDriver`,
`fdManufacturerRouteCode2`, `fdmanufacturerroutecode`,
`fdShipperNumberPrefix`, `fdShipperNumber`, `fdVehiclePriceID`, `fdEndDate`,
`fdLoadID`, `fdTrack`, `fdDestinationLocation`, `fdSCAC`, `fdCustom1`,
`fdCustom2`.

### Shippers

`fdWholeVIN1`, `fdPedimentoType`, `Textbox7`, `Textbox6`, `fdPortCode`,
`Textbox19`, `Textbox3`, `Textbox11`, `fdBasicUnit`, `fdOptions`,
`fdInsurance`, `fdInsurance2`, `fdCargo`, `fdCustoms`, `fdTotalPesos`,
`fdTotalPrice`, `fdTotalPrice1`, `fdVINSerial`, `fdSymbol`,
`fdSpanishDescription`, `fdEnglishDescription`, `fdVON`, `fdDealer`,
`fdUnitDetail`, `fdBillNumber`, `fdExchangeRate`, `fdBillDate`, `Textbox1`,
`Textbox2`, `fdBasicUnit1`, `fdOptions1`, `fdInsurance1`, `fdCargo1`,
`fdCustoms1`, `fdTotalPrice3`, `fdTotalPrice2`, `Textbox55`, `Textbox57`,
`fdTotalPesos1`, `fdPrintDate`.

### CNACS

`Vin`, `InvoiceNo`, `Shipper`, `Pedimento`, `NumRemesa`, `InvoiceDate`,
`Plant`, `CONVEYANCE`, `BrokerId`, `H/SCODE`, `BODYMODEL`, `ENGINECODE`,
`SpanishDescription`, `VON`, `Dealer`, `EXCHANGERATE`, `BaseModelPrice`,
`Freight`, `TotalOptionPrice`, `CustomsExpenses`, `Insurance`.

## Precauciones de equivalencia

- Los índices VLOOKUP son posicionales; deben sustituirse por encabezados
  explícitos.
- Las claves tienen espacios y diferencias de mayúsculas potenciales.
- La hoja Vehicle Load tiene dos espacios iniciales en su nombre.
- `Upfit Cost` aparece vacío en los datos cacheados aun con 624 fórmulas.
- El catálogo enlazado aparece como `[1]route_codes`; el número no es parte del
  nombre lógico.

# VBA extraído estáticamente

Herramientas: `oletools 0.60.2`, `olefile 0.47` y `pcodedmp 1.2.6`.
No se ejecutaron macros.

## Resultado

El proyecto `Consist_Rail` contiene doce módulos. Ocho están vacíos. Tres
procedimientos tienen código operativo:

```vb
Private Sub CommandButton1_Click()
    Range("M14").Select
    Columns("A:A").Select
    Selection.Replace What:=" ", Replacement:="", LookAt:=xlPart, _
        SearchOrder:=xlByRows, MatchCase:=False, SearchFormat:=False, _
        ReplaceFormat:=False
    Range("M14").Select
End Sub
```

```vb
Sub Borrar_Espaacios()
    Range("M14").Select
    Columns("A:A").Select
    Selection.Replace What:=" ", Replacement:="", LookAt:=xlPart, _
        SearchOrder:=xlByRows, MatchCase:=False, SearchFormat:=False, _
        ReplaceFormat:=False
    Range("M14").Select
End Sub
```

```vb
Sub Copiar_VIN()
    ' Omite aquí desplazamientos visuales ScrollColumn sin efecto de datos.
    Range("J2").Select
    Range(Selection, Selection.End(xlDown)).Select
    Selection.Copy
    Selection.End(xlUp).Select
    Selection.End(xlToLeft).Select
    Range("A2").Select
    ActiveSheet.Paste
    Cells.EntireColumn.AutoFit
    Range("C3").Select
End Sub
```

```vb
Sub Borrar_Naranjas()
    Sheets("  Vehicle Load Report").Select
    Cells.ClearContents
    Sheets("MVT").Select
    Cells.ClearContents
    Sheets("Shippers").Select
    Cells.ClearContents
    Sheets("CNACS").Select
End Sub
```

`Borrar_Naranjas` referencia `MVT`, hoja que no existe en el workbook actual.
Es código legado incompleto y fallaría al llegar a esa instrucción.

## Fuente frente a p-code

`pcodedmp` recuperó las mismas operaciones, literales, rangos y procedimientos
que el código fuente para:

- `CommandButton1_Click`;
- `Copiar_VIN`;
- `Borrar_Espaacios`;
- `Borrar_Naranjas`.

No se observó discrepancia indicativa de VBA stomping. `olevba` no puede emitir
su veredicto automático de stomping sobre este contenedor en memoria; la
conclusión se limita a comparación manual fuente/p-code.

No existen llamadas de macro que refresquen conexiones, calculen fórmulas,
ordenen unidades o resuelvan duplicados CNACS.

## ActiveX

- Hoja: `Consist` (`Hoja1`).
- Nombre interno: `CommandButton1`.
- Clase: Microsoft Forms CommandButton.
- Texto visible: `Borrar Espacios`.
- Evento: `CommandButton1_Click`.
- Acción: elimina todos los caracteres espacio de la columna A de la hoja
  activa y devuelve la selección a M14.

Su función afecta el identificador de plataforma, no el VIN ni el catálogo.

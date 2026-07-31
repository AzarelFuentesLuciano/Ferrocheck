# Duplicados CNACS

Se confirmaron 24 VIN duplicados, dos filas por VIN. Los 24 casos son
idénticos en todas las columnas salvo `NumRemesa`. Por ello se clasifican como
**duplicados con diferencias**; representan posiblemente remesas/eventos
distintos, pero esa interpretación operativa necesita confirmación.

| VIN | Filas CNACS | Diferencia |
|---|---|---|
| 3C6LRVAG6TE207507 | 522, 617 | NumRemesa |
| 3C6LRVBG4TE207553 | 459, 552 | NumRemesa |
| 3C6LRVCG9TE210642 | 523, 612 | NumRemesa |
| 3C6LRVDG1TE200427 | 453, 545 | NumRemesa |
| 3C6LRVNG1TE207358 | 525, 614 | NumRemesa |
| 3C6MRVHGXTE205026 | 462, 549 | NumRemesa |
| 3C6MRVJG3TE209770 | 529, 613 | NumRemesa |
| 3C6MRVUG0TE187089 | 458, 546 | NumRemesa |
| 3C6MRVUG4TE191825 | 460, 548 | NumRemesa |
| 3C6MRVUG6TE193902 | 451, 539 | NumRemesa |
| 3C6MRVUG7TE193083 | 457, 543 | NumRemesa |
| 3C6MRVUG7TE193892 | 454, 542 | NumRemesa |
| 3C6SRFFP1T4220164 | 463, 551 | NumRemesa |
| 3C6SRFFP3T4206797 | 526, 616 | NumRemesa |
| 3C6SRFFP6T4205725 | 450, 538 | NumRemesa |
| 3C6SRFFP9T4210319 | 452, 544 | NumRemesa |
| 3C6SRFJP0T4200687 | 527, 610 | NumRemesa |
| 3C6SRFJP0T4215545 | 464, 547 | NumRemesa |
| 3C6SRFJP4T4197566 | 465, 553 | NumRemesa |
| 3C6SRFJP4T4200949 | 455, 541 | NumRemesa |
| 3C6SRFJP5T4214875 | 456, 540 | NumRemesa |
| 3C6SRFJP6T4194684 | 528, 611 | NumRemesa |
| 3C6SRFLP8T4206377 | 461, 550 | NumRemesa |
| 3C6SRFLPXT4203612 | 524, 615 | NumRemesa |

Consist usa `VLOOKUP(VIN,CNACS!A:D,4,0)`: toma la primera fila y sólo recupera
`Pedimento`. Como Pedimento es igual en cada pareja, los duplicados no cambian
el valor de Consist. Summary no consulta VIN ni CNACS directamente. No hay
macro ni consulta que elimine o combine estas filas.

El detalle estructurado, incluidas columnas iguales y posiciones, está en
`golden-master/duplicate-analysis.json`.

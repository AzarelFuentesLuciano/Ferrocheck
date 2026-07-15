\ = "http://localhost/Ferrocheck/public/index.php"

# Test 1: all
\ = @{ accion = "exportar_xlsx"; equipos_filtrados = '[]' }
\ = Invoke-RestMethod -Uri \ -Method Post -Body \ -ResponseHeadersVariable headers1 -OutFile "storage/temp/export_all.xlsx" -PassThru
\ = \["Content-Type"]
\ = (Get-Item "storage/temp/export_all.xlsx").Length
Write-Host "storage/temp/export_all.xlsx: Content-Type = \, size = \ bytes"

# Test 2: filtered
\ = @{ accion = "exportar_xlsx"; equipos_filtrados = '["TTGX985062"]' }
\ = Invoke-RestMethod -Uri \ -Method Post -Body \ -ResponseHeadersVariable headers2 -OutFile "storage/temp/export_filtered.xlsx" -PassThru
\ = \["Content-Type"]
\ = (Get-Item "storage/temp/export_filtered.xlsx").Length
Write-Host "storage/temp/export_filtered.xlsx: Content-Type = \, size = \ bytes"

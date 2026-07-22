# Cierre del backfill organizacional de usuarios

El 22 de julio de 2026 se verificaron cero usuarios activos sin área, cero áreas principales duplicadas y cero relaciones inválidas. Se retiró de `App\Auth\OrganizationalAccess` únicamente el retorno legado que otorgaba todos los módulos a usuarios sin área ni excepciones. Un usuario sin área ahora no hereda módulos; los accesos globales, decisiones individuales, herencia normal y permisos de rol permanecen intactos.

No se aplicó ningún filtro global por `area_organizacional_id` en Catálogo, Entrega, Recepción, Historial ni Reportes. Los escáneres sin clasificación continúan operando.

La pantalla independiente de clasificación de escáneres fue retirada por decisión funcional. El área propietaria se captura al registrar o editar. Los 47 registros heredados continúan operando y muestran “Sin asignar” hasta ser corregidos individualmente desde Editar.

La validación es de solo lectura:

```powershell
php bin/organizational-backfill-validate.php
```

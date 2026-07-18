# Runbook de incidentes — Control de Escáneres

Primero detener operaciones afectadas, registrar hora/requestId sin payloads y avisar a sistemas. No ejecutar comandos destructivos ni corregir datos directamente sin aprobación.

| Situación | Diagnóstico seguro | Acción |
|---|---|---|
| Dashboard no carga | Preflight, logs sanitizados y conectividad | Mantener fallback; escalar a sistemas |
| Esquema ausente | Inspector de sólo lectura | No migrar automáticamente; activar plan aprobado |
| Migración parcial | Congelar módulo, preservar salida y respaldo | DBA evalúa restauración o continuación controlada |
| Entrega no registrada | Revisar mensaje, requestId y estado | No repetir a ciegas; confirmar movimiento abierto |
| Movimiento abierto | Consultar expediente y estado | Supervisor concilia; no cerrar con SQL manual |
| Incidencia no relacionada | Revisar expediente/movimiento | Preservar evidencia y escalar corrección autorizada |
| Estado incorrecto | Detener nuevas operaciones del equipo | Auditar transición y aplicar procedimiento administrativo futuro |
| Sin sesión | Confirmar autenticación | Reingresar; no compartir sesiones |
| CSRF falla | Recargar formulario | No reutilizar tokens ni desactivar protección |
| Base no responde | Verificar servicio/red sin exponer credenciales | Mantener módulo bloqueado y activar DBA |

Registrar error técnico, requestId, correlationId, operación, módulo, resultado, duración y actor ID seguro. Nunca registrar contraseñas, tokens, cookies, PIN, PUK, IMEI/ICCID/teléfono completos, IP innecesaria o payload completo.

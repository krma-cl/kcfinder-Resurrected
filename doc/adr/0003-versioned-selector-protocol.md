# ADR 0003: protocolo versionado del selector moderno

| Campo | Valor |
|---|---|
| Estado | Aceptado |
| Fecha | 2026-07-14 |

## Contexto

Los callbacks históricos entregan únicamente URLs construidas en JavaScript. Las aplicaciones modernas necesitan ruta lógica, URL resuelta, MIME y tamaño comprobados, sin romper integraciones existentes ni confiar en datos calculados por el navegador.

## Decisión

- El modo moderno será opt-in mediante `selector=1` o `selector=v1`; el modo heredado seguirá siendo el predeterminado.
- El servidor reconstruirá la ruta a partir del directorio activo y nombres validados, autorizará `select` y obtendrá los metadatos desde el almacenamiento.
- El sobre incluirá `event` y `version: 1`; entregará `file` para selección simple o `files` para selección múltiple.
- Los callbacks nuevos serán `callBackObject` y `callBackMultipleObjects`.
- El mismo sobre se entregará mediante `window.postMessage()`.
- La misma procedencia estará permitida automáticamente. Otra procedencia deberá coincidir exactamente con `_selectorAllowedOrigins` y con `selectorOrigin`.
- No se aceptará `*` como procedencia configurada o solicitada.
- La selección múltiple requerirá `selectorMultiple=1` o `true`.
- Las miniaturas mantendrán inicialmente el contrato heredado de URL.

## Consecuencias

Las aplicaciones pueden adoptar JSON estructurado sin migrar inmediatamente CKEditor, TinyMCE o callbacks de URL. El endpoint adicional requiere CSRF y no revela rutas físicas en errores. Cada integración entre procedencias debe declarar confianza en ambos extremos y validar `event.origin` al recibir mensajes.

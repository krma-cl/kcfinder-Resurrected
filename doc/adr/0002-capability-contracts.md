# ADR 0002: contratos por capacidades y autorización explícita

| Campo | Valor |
|---|---|
| Estado | Aceptado |
| Fecha | 2026-07-14 |

## Contexto

Una única interfaz de almacenamiento con listado, lectura, escritura, uploads, movimiento y eliminación obligaría a los adaptadores a implementar operaciones que quizá no soportan. Además, autorizar después de consultar el almacenamiento puede revelar la existencia o los metadatos de archivos prohibidos.

## Decisión

- El almacenamiento se expresará mediante contratos pequeños por capacidad. El primero es `FileMetadataProviderInterface`.
- La autorización utilizará `AuthorizationInterface` y será una dependencia obligatoria de cada servicio que exponga una operación.
- Las rutas se convertirán primero a `LogicalPath`; autorización y almacenamiento recibirán exactamente la misma ruta canónica.
- `FileSelectionService` autorizará la operación `select` antes de solicitar metadatos.
- No habrá una implementación predeterminada que permita todo. Las aplicaciones independientes y los adaptadores deberán suministrar una política explícita.
- Nuevas capacidades de listado, lectura, escritura y mutación se agregarán como interfaces separadas cuando exista un caso de uso implementado y probado.

## Consecuencias

Laravel podrá adaptar Gates o callbacks; Symfony podrá adaptar Voters; la distribución independiente podrá usar un callback propio. Una denegación no consultará el almacenamiento ni incluirá la ruta física en el mensaje. La granularidad añade algunas clases pequeñas, pero evita interfaces amplias e implementaciones inseguras por omisión.

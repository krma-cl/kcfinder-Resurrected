# ADR 0001: namespace y autoload del código moderno

| Campo | Valor |
|---|---|
| Estado | Aceptado |
| Fecha | 2026-07-14 |

## Contexto

Las clases heredadas utilizan el namespace `kcfinder` y un autoloader propio basado en nombres de archivo. Renombrarlas ahora produciría un cambio incompatible y mezclaría la modernización de arquitectura con una migración masiva.

El código nuevo necesita autoload estándar para ser consumido mediante Composer por el núcleo independiente y por futuros adaptadores Laravel y Symfony.

## Decisión

- Las clases nuevas utilizarán el namespace raíz `KCFinder\` y vivirán bajo `src/` mediante PSR-4.
- Las clases heredadas conservarán sus nombres y su autoloader actual durante la ruta de compatibilidad.
- El primer valor compartido será `KCFinder\Domain\FileDescriptor`, que representa el contrato versionado de archivo seleccionado.
- Los servicios nuevos dependerán de contratos pequeños, como `UrlResolverInterface`, en vez de leer directamente variables globales o configuración de frameworks.
- El nombre definitivo de Packagist se decidirá por separado; este ADR no cambia todavía `composer.json#name`.

## Consecuencias

La distribución tradicional continúa funcionando sin Composer porque los endpoints existentes no dependen aún de `src/`. Las capacidades nuevas requerirán `vendor/autoload.php` hasta que el empaquetado de releases incorpore un cargador compatible. No se retirará el autoloader heredado antes de una versión mayor y una guía de migración.

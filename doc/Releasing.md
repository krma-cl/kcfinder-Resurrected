# Publicación de versiones

Esta guía está dirigida a mantenedores. La fuente de verdad es un tag SemVer del repositorio; el paquete Composer y el ZIP tradicional deben corresponder al mismo commit.

## Preparación

1. Confirme que `master` está limpio y que la integración continua pasa en PHP 8.2, 8.3, 8.4 y 8.5.
2. Actualice changelog y documentación cuando corresponda.
3. Ejecute `composer validate --strict`, `composer audit --locked` y `composer test`.
4. Construya y verifique localmente el ZIP con `composer package`.

## Tag y GitHub Release

Use un tag como `v4.5.0` o `v4.5.0-rc.1`. Al subirlo, el flujo **Release**:

- valida el formato del tag;
- repite la suite de calidad;
- construye dos veces el ZIP y exige que sean idénticos;
- verifica el manifiesto, la suma externa y la instalación tradicional;
- instala `krma-cl/kcfinder` con Composer desde un proyecto vacío;
- crea el GitHub Release con el ZIP y su archivo `.sha256`.

Los tags con sufijo se publican como prerelease. No se debe reemplazar el contenido de un tag ya publicado; una corrección requiere una nueva versión.

## Packagist

El registro inicial se hace una sola vez:

1. Inicie sesión en Packagist con una cuenta autorizada de la organización.
2. Envíe `https://github.com/krma-cl/kcfinder-Resurrected` como nuevo paquete.
3. Confirme que Packagist detecta `krma-cl/kcfinder` desde `composer.json`.
4. Habilite la actualización automática recomendada por Packagist para el repositorio GitHub.

Después del registro, cada tag válido será descubierto por Packagist. Antes de anunciar una versión, compruebe que aparece con el mismo tag y commit que el GitHub Release. El flujo de release no contiene credenciales de Packagist.

## Recuperación

Si la validación falla, no publique manualmente los artefactos parciales. Corrija la rama, cree un nuevo tag y deje el intento fallido visible para conservar trazabilidad. Un release incorrecto puede marcarse como retirado en Packagist, pero el tag y su historial no deben reescribirse.

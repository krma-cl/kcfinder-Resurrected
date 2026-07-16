# Orientación para trabajar en el núcleo

Este repositorio contiene el núcleo independiente y la distribución tradicional de KCFinder Resurrected. Antes de modificarlo, lee:

- `doc/Architecture.md`
- `doc/PublicSurface.md`
- `doc/ModernSelector.md`
- `doc/OperationObservers.md`
- `doc/Releasing.md`
- la [guía canónica del ecosistema](https://krma-cl.github.io/kcfinder-docs/roadmap/maintainer-guide)

## Línea base

- Paquete: `krma-cl/kcfinder`
- Release estable al crear esta guía: `v4.6.0`
- PHP soportado: 8.2, 8.3, 8.4 y 8.5
- Rama principal: `master`
- En este clon, `fork` apunta a `krma-cl/kcfinder-Resurrected` y `origin` a `DevCrh/kcfinder-Resurrected`. Verifica el remoto antes de empujar.

## Reglas de arquitectura

- No agregues dependencias obligatorias de Laravel, Symfony, Bootstrap, Node.js o Docker.
- Mantén funcional la instalación tradicional sin Composer en el servidor.
- Conserva endpoints, configuración y callbacks heredados; los protocolos modernos deben ser compatibles u optativos.
- Rutas, autorización, MIME, tamaño y URLs se resuelven en el servidor.
- Las integraciones con frameworks usan contratos neutrales del núcleo.
- `OperationObserverInterface` es optativo y su implementación predeterminada no hace nada. Los fallos de observadores se registran sin convertir una operación ya realizada en un falso fallo.
- No mezcles refactorización amplia, interfaz y seguridad en un mismo cambio.
- La auditoría profunda de seguridad ya se realizó; aplica validación proporcional al cambio y no reinicies una modernización general salvo petición expresa.

## Validación

```bash
composer validate --strict
composer test
composer package
```

No ocultes warnings ni deprecations. La CI debe pasar en PHP 8.2–8.5. Para comprobar versiones externas sin actualizar automáticamente, usa `tools/check-upstream-versions.ps1`.

## Flujo

- Usa `krma/<descripcion>` para ramas normales.
- Agrega pruebas de caracterización antes de alterar comportamiento histórico.
- Actualiza documentación, changelog y `composer.json` cuando cambie una superficie pública o una versión.
- Un tag `v*` dispara el release reproducible; no crees ni reemplaces tags hasta que CI y empaquetado estén verificados.
- Si el cambio sólo pertenece a Laravel, Symfony o al tema Bootstrap 5, hazlo en su repositorio, no aquí.

# ADR 0004: identidad y canales de distribución

| Campo | Valor |
|---|---|
| Estado | Aceptado |
| Fecha | 2026-07-14 |

## Contexto

KCFinder debe continuar instalándose como una aplicación PHP independiente, pero el núcleo moderno también necesita una identidad estable para Composer y para futuros adaptadores de frameworks. El nombre histórico sigue siendo el identificador más reconocible del proyecto.

## Decisión

- El paquete Composer se identificará como `krma-cl/kcfinder`.
- El namespace del código moderno seguirá siendo `KCFinder\`; la identidad del proveedor no se incorporará a las clases públicas.
- El repositorio continuará llamándose KCFinder Resurrected y reconocerá explícitamente su origen en `sunhater/kcfinder`.
- Cada tag estable o de prepublicación generará desde el mismo commit dos canales compatibles: el paquete Composer y un ZIP tradicional autosuficiente.
- Los tags utilizarán SemVer con prefijo `v`, por ejemplo `v4.3.0` o `v4.3.0-rc.1`.
- Composer será opcional para la instalación tradicional. El ZIP no incluirá herramientas de desarrollo ni requerirá ejecutar Composer en el servidor.

## Consecuencias

Los consumidores podrán declarar `krma-cl/kcfinder` sin perder el reconocimiento del proyecto original. Packagist será el índice del paquete Composer, mientras que GitHub Releases publicará el ZIP tradicional y su suma SHA-256. Los adaptadores Laravel y Symfony dependerán del paquete del núcleo, pero mantendrán repositorios y ciclos de versión independientes.

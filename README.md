# KCFinder Resurrected

> A maintained, security-focused and production-oriented continuation of KCFinder, preserving backward compatibility and lightweight deployment.

[![CI](https://github.com/krma-cl/kcfinder-Resurrected/actions/workflows/ci.yml/badge.svg)](https://github.com/krma-cl/kcfinder-Resurrected/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/krma-cl/kcfinder)](https://packagist.org/packages/krma-cl/kcfinder)
[![PHP](https://img.shields.io/packagist/dependency-v/krma-cl/kcfinder/php)](https://packagist.org/packages/krma-cl/kcfinder)
[![License](https://img.shields.io/packagist/l/krma-cl/kcfinder)](doc/LICENSE.GPL)

KCFinder Resurrected es la continuación mantenida del administrador de archivos web creado originalmente por Pavel Tzonkov. El proyecto conserva la instalación sencilla y los flujos conocidos de KCFinder, mientras incorpora compatibilidad con PHP moderno, pruebas automatizadas, mejoras de seguridad y contratos preparados para aplicaciones actuales.

No es un parche temporal del repositorio histórico: es una línea de continuidad con identidad, releases y hoja de ruta propias. Reconocemos y preservamos explícitamente el trabajo original de [`sunhater/kcfinder`](https://github.com/sunhater/kcfinder).

## Estado del proyecto

- Versión estable: [`v4.6.0`](https://github.com/krma-cl/kcfinder-Resurrected/releases/tag/v4.6.0).
- Paquete Composer: [`krma-cl/kcfinder`](https://packagist.org/packages/krma-cl/kcfinder).
- Compatibilidad mantenida: PHP 8.2, 8.3, 8.4 y 8.5.
- Distribución tradicional mediante ZIP autosuficiente.
- Matriz continua de sintaxis, PHPUnit, PHPStan y validación de artefactos.
- Selector moderno JSON opt-in, sin retirar el selector heredado.
- Adaptadores oficiales, versionados de forma independiente, para Laravel y Symfony.
- Observador operacional optativo para integrar cargas, ediciones, movimientos, renombrados y eliminaciones sin modificar el navegador clásico.

## Qué es KCFinder

KCFinder es un administrador de archivos web de código abierto que puede integrarse con CKEditor, TinyMCE y aplicaciones personalizadas. Permite navegar carpetas, subir y administrar archivos, crear miniaturas, editar imágenes y seleccionar recursos para incorporarlos en otros sistemas.

## Características

- Navegación de carpetas y operaciones AJAX.
- Carga múltiple mediante selector, arrastrar y soltar y fuentes externas.
- Miniaturas, redimensionado, rotación EXIF y marcas de agua.
- Edición de imágenes mediante Filerobot y recorte rápido con Jcrop.
- Descarga de archivos y carpetas como ZIP.
- Copia, movimiento, renombrado y eliminación según permisos configurados.
- Selección simple o múltiple para integraciones personalizadas.
- Respuesta JSON versionada con nombre, ruta, URL, MIME y tamaño.
- Sesiones y protección CSRF.
- Reconocimiento MIME mediante Fileinfo.
- Soporte para múltiples idiomas y temas.
- Tema Bootstrap 5 desacoplado y opcional.
- Interfaz responsiva con panel móvil de carpetas y controles accesibles por teclado.

## Interfaz responsiva

En pantallas menores a 768 píxeles, el árbol de carpetas se transforma en un panel lateral accesible desde el botón **Carpetas**. El panel se cierra al elegir una carpeta, tocar el fondo o presionar `Escape`; además, conserva el foco dentro del panel mientras está abierto y lo devuelve al botón al cerrarse.

El comportamiento forma parte del núcleo y funciona con el tema clásico. Los temas opcionales pueden adaptar su presentación sin sustituir el controlador responsivo ni introducir una dependencia obligatoria en Bootstrap.

## Instalación

### ZIP tradicional

Descargue el ZIP y su suma SHA-256 desde [GitHub Releases](https://github.com/krma-cl/kcfinder-Resurrected/releases). Este canal no requiere Composer, Node.js ni Docker en el servidor.

Las instrucciones completas se encuentran en [doc/Distribution.md](doc/Distribution.md).

### Composer

```bash
composer require krma-cl/kcfinder
```

Composer instala el paquete en `vendor/krma-cl/kcfinder` y expone el namespace moderno `KCFinder\` mediante PSR-4. No publique el directorio `vendor` completo como raíz web; para la interfaz independiente utilice el ZIP tradicional o una publicación controlada de recursos.

## Componentes oficiales

El núcleo, los adaptadores de frameworks y el tema visual se mantienen como proyectos independientes. Todos son opcionales y tienen su propio ciclo de versiones; instalar uno de ellos no agrega dependencias al núcleo para quienes utilizan KCFinder de manera tradicional.

### Adaptador para Laravel

[`krma-cl/kcfinder-laravel`](https://github.com/krma-cl/kcfinder-laravel) integra KCFinder con Laravel 12 y 13, incluyendo Laravel Storage, autorización mediante Gates, publicación de configuración y eventos del selector moderno.

```bash
composer require krma-cl/kcfinder-laravel:^1.2
```

El paquete y sus versiones estables están disponibles en [Packagist](https://packagist.org/packages/krma-cl/kcfinder-laravel). Consulte el README del adaptador para publicar su configuración y conectarlo al disco y las reglas de autorización de la aplicación.

### Bundle para Symfony

[`krma-cl/kcfinder-symfony-bundle`](https://github.com/krma-cl/kcfinder-symfony-bundle) integra KCFinder con Symfony 7.4 y 8.x mediante servicios, eventos, autorización y almacenamiento Flysystem 3.

```bash
composer require krma-cl/kcfinder-symfony-bundle:^1.0
```

El bundle y sus versiones estables están disponibles en [Packagist](https://packagist.org/packages/krma-cl/kcfinder-symfony-bundle). Su README contiene la configuración del bundle, los servicios y el adaptador de almacenamiento.

### Tema Bootstrap 5

[`kcfinder-bootstrap5-theme`](https://github.com/krma-cl/kcfinder-bootstrap5-theme) ofrece una interfaz opcional basada en Bootstrap 5 y Bootstrap Icons, sin CDN ni dependencias de ejecución adicionales.

1. Descargue el ZIP desde los [releases del tema](https://github.com/krma-cl/kcfinder-bootstrap5-theme/releases).
2. Copie su directorio `dist/bootstrap5` como `themes/bootstrap5` dentro de KCFinder.
3. Configure el tema:

```php
'theme' => 'bootstrap5',
```

Después de instalarlo o actualizarlo, elimine `cache/theme_bootstrap5.css` y `cache/theme_bootstrap5.js` si existen para que KCFinder regenere los recursos combinados. El tema clásico continúa incluido y disponible como alternativa.

## Selector moderno

El selector estructurado puede entregar un objeto versionado como el siguiente:

```json
{
  "name": "DO-20130614.pdf",
  "path": "/01-actos/diario-oficial/2013/DO-20130614.pdf",
  "url": "/storage/transparencia/01-actos/diario-oficial/2013/DO-20130614.pdf",
  "mime": "application/pdf",
  "size": 184320
}
```

La activación es opt-in y mantiene la compatibilidad con callbacks heredados. Los callbacks estructurados y el uso seguro de `postMessage` están documentados en [doc/ModernSelector.md](doc/ModernSelector.md).

## Observador de operaciones

La versión 4.6 agrega un contrato neutral y optativo que notifica operaciones exitosas del navegador clásico. La implementación predeterminada no realiza ninguna acción, por lo que las instalaciones existentes conservan exactamente su comportamiento.

Los adaptadores pueden observar cargas, ediciones, movimientos, renombrados, eliminaciones y creación de carpetas sin parchear `browser.php`. Consulte [doc/OperationObservers.md](doc/OperationObservers.md) para configurar el contrato y conocer su política tolerante a fallos.

## Requisitos

- PHP 8.2 o superior dentro de la matriz mantenida.
- Apache 2.4 como servidor probado oficialmente; otros servidores pueden funcionar con configuración equivalente.
- Fileinfo e Intl.
- GD, Imagick o GraphicsMagick para imágenes y miniaturas.
- mbstring recomendada para nombres Unicode.
- EXIF para orientación automática.
- ZIP para descargas agrupadas.

## Seguridad

KCFinder administra uploads y operaciones de sistema de archivos, por lo que debe ejecutarse detrás de autenticación y autorización adecuadas. Revise [SECURITY.md](SECURITY.md) antes de habilitarlo públicamente y utilice el canal privado de GitHub para informar vulnerabilidades.

La configuración segura, CSRF, validación de tipos, rutas y sesiones cuentan con pruebas automatizadas. No se ocultan warnings ni deprecations para conseguir que la suite pase.

## Desarrollo

```bash
composer install
composer test
```

`composer test` valida sintaxis, ejecuta PHPUnit y analiza el código moderno con PHPStan. El pipeline repite estas verificaciones en PHP 8.2, 8.3, 8.4 y 8.5.

La [revisión periódica de versiones](docs/upstream-version-check.md) permite comprobar nuevas versiones estables de PHP, Laravel, Symfony, Bootstrap y las demás tecnologías principales sin actualizar dependencias automáticamente.

Para construir y verificar localmente los dos formatos de distribución:

```bash
composer package
php tools/verify-composer-install.php
```

## Arquitectura y hoja de ruta

La dirección del proyecto está documentada en [doc/Architecture.md](doc/Architecture.md). La hoja de ruta conserva un núcleo independiente y contempla:

1. Compatibilidad y pruebas de caracterización.
2. Seguridad y configuración de producción.
3. Servicios y contratos desacoplados.
4. Selector JSON moderno.
5. Composer, Packagist y releases reproducibles.
6. Interfaz responsiva y accesible.
7. Adaptadores oficiales para Laravel y Symfony, publicados en repositorios independientes.

La superficie HTTP y los comportamientos heredados protegidos por pruebas se describen en [doc/PublicSurface.md](doc/PublicSurface.md). Las decisiones arquitectónicas relevantes se registran en [`doc/adr`](doc/adr).

## Linaje y agradecimientos

- KCFinder original, creado por [Pavel Tzonkov](https://github.com/sunhater).
- Continuación previa de [DevCrh/KCFinder Resurrected](https://github.com/DevCrh/kcfinder-Resurrected).
- Editor de imágenes [Filerobot Image Editor](https://scaleflex.github.io/filerobot-image-editor/).
- Todas las personas que han contribuido, probado y reportado problemas durante la vida del proyecto.

KCFinder Resurrected mantiene su linaje visible porque la continuidad del software libre se construye sobre el trabajo anterior, no borrándolo.

## Mantenimiento y comunidad

KCFinder Resurrected es mantenido por [KRMA](https://krma.cl) junto con su comunidad de usuarios y colaboradores. KRMA aporta desarrollo, coordinación e infraestructura para asegurar la continuidad del proyecto.

## Licencias

El proyecto continúa disponible bajo:

- GNU General Public License, versión 3 o posterior.
- GNU Lesser General Public License, versión 3 o posterior.

Consulte las licencias completas [GPL](doc/LICENSE.GPL) y [LGPL](doc/LICENSE.LGPL).

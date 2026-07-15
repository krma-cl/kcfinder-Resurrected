# Instalación y distribución

KCFinder mantiene dos canales oficiales desde el mismo código fuente. Composer es opcional: una instalación tradicional no necesita Composer, Node.js ni Docker en producción.

## ZIP tradicional

1. Descargue el ZIP y el archivo `.sha256` de la sección **Releases** del repositorio.
2. Compruebe la suma SHA-256 antes de descomprimirlo.
3. Extraiga la carpeta en una ubicación servida por PHP.
4. Configure KCFinder según su aplicación y mantenga la carpeta de uploads fuera del control de versiones.
5. Confirme que `upload/.htaccess` se conserva al copiar los archivos en Apache. En Nginx u otro servidor, replique sus restricciones en la configuración del servidor.

El ZIP incorpora `VERSION` y `MANIFEST.sha256`, excluye pruebas y herramientas de desarrollo e incluye el cargador necesario para las clases modernas. Es el canal recomendado para usar la interfaz independiente.

## Composer

Una vez publicado el primer tag e indexado el repositorio en Packagist:

```bash
composer require krma-cl/kcfinder
```

Composer instalará el paquete en `vendor/krma-cl/kcfinder` y expondrá el namespace `KCFinder\` mediante PSR-4. Este canal está orientado al uso programático del núcleo y a los futuros adaptadores oficiales para Laravel y Symfony.

No publique todo el directorio `vendor` como raíz web. Si necesita hoy la interfaz independiente, utilice el ZIP tradicional o copie de forma controlada sólo el artefacto web a una ubicación pública. Los adaptadores de frameworks definirán rutas y publicación de assets sin exponer dependencias privadas.

## Requisitos

- PHP 8.2, 8.3, 8.4 o 8.5.
- Fileinfo e Intl.
- mbstring, recomendada para ordenar nombres Unicode correctamente.
- GD, Imagick o GraphicsMagick para imágenes y miniaturas.
- EXIF para corregir automáticamente la orientación de imágenes.
- ZIP para descargar conjuntos de archivos y para construir artefactos.

Consulte [SECURITY.md](../SECURITY.md) antes de habilitar una instalación pública.

## Verificación para mantenedores

```bash
composer validate --strict
composer test
composer package
php tools/verify-composer-install.php
```

`composer package` crea un artefacto de desarrollo en `dist/`. Los releases oficiales se construyen automáticamente desde tags válidos y vuelven a ejecutar todas las verificaciones.

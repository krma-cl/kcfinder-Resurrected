# Superficie pública y compatibilidad heredada

| Campo | Valor |
|---|---|
| Estado | Línea base de caracterización con operaciones de archivos |
| Fecha | 2026-07-14 |
| Rama inicial | `krma/phase1-baseline` |

## Propósito

Este inventario registra los puntos de entrada, acciones y contratos observables de KCFinder Resurrected antes de reorganizar el núcleo. No declara que todos los comportamientos actuales sean el diseño final; identifica aquello que debe preservarse, migrarse explícitamente o deprecarse con una alternativa documentada.

La arquitectura objetivo se encuentra en [Architecture.md](Architecture.md).

## Puntos de entrada HTTP

| Punto de entrada | Método habitual | Responsabilidad | Compatibilidad |
|---|---|---|---|
| `browse.php` | GET y POST | Interfaz, navegación y operaciones AJAX seleccionadas mediante `act` | Público heredado |
| `upload.php` | POST multipart | Upload directo y callbacks de editores | Público heredado |
| `js_localize.php` | GET | Traducciones JavaScript mediante `lng` | Público heredado |
| `themes/<theme>/css.php` | GET | CSS combinado y cacheado del tema | Tema público |
| `themes/<theme>/js.php` | GET | JavaScript combinado y cacheado del tema | Tema público |
| `index.php` | GET | Ejemplo de integración | Ejemplo, no API |

`core/bootstrap.php` es el bootstrap común de `browse.php` y `upload.php`. Carga la integración solicitada mediante `cms`, registra el autoloader y define la validación CSRF heredada.

Una aplicación anfitriona autenticada que ya completó su propio bootstrap puede
proporcionar opciones confiables y limitadas a la petición mediante
`$GLOBALS['KCFINDER_RUNTIME_CONFIG']`. Estas opciones se combinan después de
`conf/config.local.php`, incluidas opciones privadas como
`_operationObserver`, sin editar archivos dentro de `vendor`:

```php
$GLOBALS['KCFINDER_RUNTIME_CONFIG'] = array(
    'disabled' => false,
    'uploadDir' => '/srv/application/storage/files',
    'uploadURL' => '/protected/files',
    '_operationObserver' => $container->get(
        \KCFinder\Contract\OperationObserverInterface::class
    ),
);
```

Sólo código PHP confiable del servidor debe poblar este arreglo; nunca debe
construirse directamente desde parámetros HTTP. Las instalaciones tradicionales
y `conf/config.local.php` conservan su comportamiento.

Los temas instalados como paquetes Composer separados pueden registrarse sin
copiarse dentro del paquete del núcleo:

```php
$GLOBALS['KCFINDER_RUNTIME_CONFIG']['_themeRoots'] = array(
    'bootstrap5' => '/application/vendor/krma-cl/kcfinder-bootstrap5-theme/dist/bootstrap5',
);
```

Las URLs conservan el contrato `themes/<nombre>/...`; la aplicación anfitriona
debe publicar o servir esa misma distribución bajo su ruta controlada.

## Acciones de `browse.php`

El parámetro GET `act` selecciona un método `act_<acción>` de `kcfinder\browser`. Una acción desconocida vuelve actualmente a `browser`.

| Acción | Solicitud principal | Mutabilidad | CSRF actual | Respuesta principal |
|---|---|---:|---:|---|
| `browser` | GET | No | No | HTML |
| `init` | GET | No | No | JSON con árbol, archivos y permisos |
| `select` | POST | No | Sí | Sobre JSON v1 con metadatos verificados |
| `thumb` | GET | Puede crear caché | No | Imagen |
| `expand` | POST | No | Sí | JSON con carpetas |
| `search` | POST | No | Sí | JSON con árbol reducido de carpetas coincidentes |
| `chDir` | POST | Sesión | Sí | JSON con archivos y permisos |
| `newDir` | POST | Sí | Sí | JSON vacío o error |
| `crop` | POST | Sí | Sí | JSON |
| `editimage` | POST | Sí | Sí | JSON |
| `renameDir` | POST | Sí | Sí | JSON con nombre |
| `deleteDir` | POST | Sí | Sí | JSON vacío o error |
| `upload` | POST/GET | Sí | Sí, delegado a uploader | Texto/HTML/JSON según integración |
| `dragUrl` | POST | Sí y acceso remoto | Sí | JSON vacío o error |
| `download` | POST | No | Sí | Archivo binario |
| `rename` | POST | Sí | Sí | JSON con nombre |
| `delete` | POST | Sí | Sí | JSON vacío o error |
| `cp_cbd` | POST | Sí | Sí | JSON |
| `mv_cbd` | POST | Sí | Sí | JSON |
| `rm_cbd` | POST | Sí | Sí | JSON |
| `downloadDir` | POST | Crea ZIP temporal | Sí | ZIP |
| `downloadSelected` | POST | Crea ZIP temporal | Sí | ZIP |
| `downloadClipboard` | POST | Crea ZIP temporal | Sí | ZIP |

La ausencia de CSRF en una acción GET no implica que deba mantenerse si la acción produce efectos persistentes. El caso `thumb` requiere una decisión explícita porque puede generar una miniatura en caché.

## Parámetros de integración observados

| Parámetro | Uso actual |
|---|---|
| `cms` | Selecciona un archivo bajo `integration/` cuando el nombre es válido |
| `type` | Selecciona uno de los tipos configurados |
| `theme` | Selecciona temporalmente un directorio de tema válido |
| `lang`, `langCode`, `lng`, `language`, `lang_code` | Seleccionan idioma cuando existe su archivo |
| `CKEditorFuncNum` | Activa callback CKEditor |
| `opener` | Selecciona integraciones heredadas como TinyMCE |
| `field` | Campo de destino utilizado por TinyMCE 4 |
| `format=json` | Solicita la respuesta JSON heredada de upload |
| `selector`, `selectorMultiple`, `selectorOrigin` | Activa el selector moderno, selección múltiple y origen de entrega validado |
| `dir`, `file`, `files` | Rutas lógicas y nombres utilizados por acciones de archivos |

Los nombres anteriores forman parte de la compatibilidad de entrada. Su aceptación no exime la validación por tipo, formato, autorización y confinamiento de ruta.

## Formato JSON heredado de upload

Una carga exitosa con `format=json` devuelve actualmente:

```json
{
  "uploaded": 1,
  "url": "http://example.test/upload/files/document.pdf",
  "fileName": "document.pdf"
}
```

Un error devuelve:

```json
{
  "uploaded": 0,
  "error": {
    "message": "Descripción del error"
  }
}
```

Este contrato no será reemplazado silenciosamente. El futuro objeto del selector (`name`, `path`, `url`, `mime`, `size`) se agregará como API versionada y convivirá inicialmente con esta respuesta.

El selector moderno ya convive con este formato y se documenta en [ModernSelector.md](ModernSelector.md). No modifica `format=json` ni los callbacks heredados cuando no se solicita explícitamente.

## Configuración y sesión

La configuración se obtiene de `conf/config.php`, puede ser complementada por `conf/config.local.php` y permite que determinados valores sean sobrescritos mediante `$_SESSION['KCFINDER']`.

Superficies que deben conservar una ruta de migración:

- `disabled`, `uploadURL`, `uploadDir`, `theme` y `lang`.
- `search`, que habilita y acota la búsqueda optativa por nombres.
- `types` y opciones específicas por tipo.
- `imageDriversPriority`, dimensiones, calidad y miniaturas.
- `allowExts` y `allowMimeTypes`.
- Permisos `access` de archivos y carpetas.
- Cookies, `_sessionVar`, `_sessionCsrf` y restricciones de dominio.
- Directorio de thumbnails, permisos y política de ZIP.

El núcleo futuro deberá normalizar estos valores en objetos internos sin obligar inicialmente a cambiar la configuración tradicional.

Desde 4.8, un token CSRF emitido durante la primera petición se refleja también en el entorno de esa misma ejecución. Las solicitudes posteriores continúan exigiendo coincidencia entre sesión, cookie y parámetro enviado.

## Extensiones y capacidades

La línea base diferencia requisitos de ejecución y capacidades:

- Se necesita al menos un controlador de imágenes compatible: GD, Imagick o Gmagick.
- Intl es utilizado por el reemplazo de `strftime()` cargado por el navegador.
- Fileinfo respalda la comprobación MIME de uploads.
- ZIP habilita descargas comprimidas.
- EXIF permite corregir orientación de imágenes cuando corresponda.
- JSON y sesiones son parte del funcionamiento HTTP básico.

La decisión definitiva sobre extensiones obligatorias de Composer se registrará antes de publicar el paquete. La distribución de desarrollo seguirá probando GD, Fileinfo, ZIP, EXIF, mbstring e Intl.

## Integraciones existentes

Los archivos bajo `integration/` modifican sesión y configuración antes de construir el navegador. Incluyen integraciones predeterminadas y adaptadores heredados de CMS. Se consideran puntos de compatibilidad, pero no serán el mecanismo recomendado para los futuros paquetes de Laravel y Symfony.

Los adaptadores modernos deberán utilizar contratos públicos de autorización, almacenamiento y resolución de URLs, sin duplicar reglas de seguridad del núcleo.

## Reglas para cambios posteriores

1. Agregar una prueba antes de cambiar una superficie inventariada.
2. No reutilizar un campo heredado con una semántica distinta.
3. Versionar nuevos contratos JSON.
4. Mantener temporalmente un adaptador cuando se reemplace una API.
5. Documentar deprecaciones y versión prevista de retiro.
6. No considerar una acción segura únicamente porque su botón no esté visible.

## Cobertura automatizada de operaciones de archivos

La línea base ejecuta las operaciones reales sobre un directorio temporal aislado. No escribe en la carpeta `upload` del desarrollador ni sustituye el almacenamiento local del producto por una implementación simulada.

| Contrato protegido | Evidencia automatizada |
|---|---|
| Navegación de carpetas | Orden estable, exclusión de directorios ocultos y detección de subcarpetas |
| Listado de archivos | Nombre, tamaño, dimensiones, identificación de imagen y estado de miniatura |
| Confinamiento de rutas | Rechazo de tipo incorrecto, segmentos `..`, rutas ocultas y rutas fuera del directorio asignado |
| Normalización heredada | Nombres de archivo y directorio con caracteres no ASCII |
| Upload | Validación MIME/extensión, normalización del nombre y sufijo sin sobrescritura |
| Miniaturas | Creación real mediante GD y dimensiones resultantes |
| Eliminación | Borrado del archivo o árbol solicitado junto con su miniatura correspondiente |

Estas pruebas son de caracterización del núcleo. Aún se mantienen como trabajo separado las pruebas HTTP de extremo a extremo para descarga binaria, descarga ZIP, callbacks de editores, sesión completa en Apache y operaciones AJAX desde la interfaz. Esos casos deberán conservar la validación CSRF y comprobar tanto cabeceras como cuerpo de respuesta.

La configuración de PHPUnit trata warnings, riesgos y deprecations como fallos. La matriz de desarrollo ejecuta sintaxis, PHPUnit y análisis estático en PHP 8.2, 8.3, 8.4 y 8.5 sin ocultar diagnósticos.

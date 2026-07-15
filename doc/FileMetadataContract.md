# Contrato de metadatos de archivo

## Estado

Esta es la primera capacidad de la fase 3. Implementa el objeto de archivo y la lectura local de metadatos, pero todavía no cambia `browse.php`, `upload.php`, `format=json` ni los callbacks de editores.

Las clases nuevas se cargan mediante Composer:

```php
require __DIR__ . '/vendor/autoload.php';

use KCFinder\Infrastructure\LocalFileMetadataReader;
use KCFinder\Infrastructure\PrefixUrlResolver;

$reader = new LocalFileMetadataReader(
    '/srv/kcfinder-files',
    new PrefixUrlResolver('/storage/transparencia')
);

$file = $reader->describe('/01-actos/diario-oficial/2013/DO-20130614.pdf');

echo json_encode($file, JSON_THROW_ON_ERROR);
```

El resultado contiene únicamente:

```json
{
  "name": "DO-20130614.pdf",
  "path": "/01-actos/diario-oficial/2013/DO-20130614.pdf",
  "url": "/storage/transparencia/01-actos/diario-oficial/2013/DO-20130614.pdf",
  "mime": "application/pdf",
  "size": 184320
}
```

## Garantías actuales

- `path` es una ruta lógica con `/`; no contiene la ruta física del servidor.
- El lector rechaza segmentos vacíos, `.`, `..`, barras inversas y bytes nulos.
- La ruta resuelta debe corresponder a un archivo legible dentro de la raíz configurada.
- `mime` se obtiene en el servidor mediante Fileinfo y usa `application/octet-stream` sólo cuando Fileinfo no entrega un valor.
- `size` se obtiene desde el archivo real y nunca puede ser negativo.
- El resolvedor incluido codifica cada segmento y conserva autoridades completas, incluidos dominios, IPv6 y puertos.
- Un `UrlResolverInterface` alternativo podrá generar URLs públicas, temporales o firmadas sin cambiar el objeto de dominio.

Los errores y nombres de las clases de infraestructura podrán refinarse antes de publicar la API estable. La semántica de los cinco campos no se modificará silenciosamente.

# Búsqueda optativa de carpetas y archivos

KCFinder puede buscar por nombre en el tipo de archivos actualmente abierto y mostrar un árbol reducido con:

- carpetas cuyo nombre contiene la consulta;
- carpetas que contienen directamente archivos cuyo nombre contiene la consulta;
- carpetas antecesoras necesarias para conservar la jerarquía.

La ventana de archivos conserva la carpeta activa: si su nombre coincide, muestra todos sus archivos; en caso contrario, muestra únicamente los archivos cuyos nombres coinciden. Al navegar por el árbol filtrado se mantiene el mismo criterio, sin mezclar archivos de directorios distintos.

La búsqueda no inspecciona el contenido de PDF, documentos Office, imágenes ni archivos comprimidos.

## Activación

La capacidad está desactivada de forma predeterminada. Habilítela desde `conf/config.local.php`:

```php
$_LOCALS['search'] = array(
    'enabled' => true,
    'minChars' => 2,
    'maxResults' => 100,
    'maxEntries' => 25000,
    'timeoutMs' => 1500,
    'debounceMs' => 350,
    'scope' => 'global',
);
```

| Opción | Predeterminado | Propósito |
|---|---:|---|
| `enabled` | `false` | Muestra el campo y habilita la acción AJAX. |
| `minChars` | `2` | Longitud mínima antes de buscar. |
| `maxResults` | `100` | Máximo de carpetas coincidentes devueltas. |
| `maxEntries` | `25000` | Máximo de entradas examinadas por solicitud. |
| `timeoutMs` | `1500` | Tiempo máximo aproximado dedicado al recorrido. |
| `debounceMs` | `350` | Pausa después de escribir antes de consultar. |
| `scope` | `global` | Usa `global` para todo el tipo o `current` para la carpeta activa y sus descendientes. |

Los valores son normalizados dentro de límites seguros. `Enter` ejecuta inmediatamente, `Escape` limpia la búsqueda y el botón de cierre restaura el árbol normal. El estado muestra por separado carpetas y archivos, y explica si el resultado fue truncado por cantidad de coincidencias, entradas examinadas o tiempo.

Cada carpeta coincidente conserva su ruta completa como ayuda contextual. El árbol reducido mantiene además los antecesores necesarios para distinguir nombres repetidos.

## Alcance y rendimiento

Cada consulta recorre el almacenamiento local bajo la raíz autorizada del tipo activo. Se omiten elementos ocultos, enlaces simbólicos y rutas no legibles. La respuesta nunca contiene rutas físicas.

Para bibliotecas locales pequeñas o medianas, los límites predeterminados evitan un costo significativo. En árboles muy grandes, almacenamiento de red o integraciones remotas, reduzca `maxEntries`, exija más caracteres o mantenga la función desactivada hasta disponer de un índice específico de la aplicación.

La búsqueda es de sólo lectura, utiliza `POST`, exige el token CSRF vigente y no modifica los protocolos de selección ni las operaciones heredadas.

# Selector moderno versionado

## Compatibilidad

El selector moderno es opcional. Si no se agrega `selector=1` o `selector=v1` a `browse.php`, KCFinder conserva los callbacks heredados y las integraciones de CKEditor y TinyMCE sin cambios.

Los metadatos modernos se obtienen y verifican en el servidor. El navegador no decide la ruta lógica, MIME, tamaño ni URL final.

## Activación

Selección simple en la misma procedencia:

```text
browse.php?selector=1
```

Selección múltiple:

```text
browse.php?selector=1&selectorMultiple=1
```

También se acepta `selectorMultiple=true`. La selección múltiple no queda habilitada sólo por abrir el selector moderno: debe solicitarse explícitamente.

## Callback estructurado

En una ventana emergente o `iframe` de la misma procedencia:

```html
<script>
window.KCFinder = {
    callBackObject(file) {
        console.log(file.name, file.path, file.url, file.mime, file.size);
    },
    callBackMultipleObjects(files) {
        console.log(files);
    }
};
</script>
```

El adaptador jQuery también puede construir la integración:

```javascript
$('#filemanager').kcfinder({
    url: '/kcfinder/browse.php',
    selector: true,
    selectorMultiple: true,
    callbackObject: function (file) {
        console.log(file);
    },
    callbackMultipleObjects: function (files) {
        console.log(files);
    }
});
```

## Mensajes

Una selección simple entrega:

```json
{
  "event": "kcfinder:file-selected",
  "version": 1,
  "file": {
    "name": "DO-20130614.pdf",
    "path": "/01-actos/diario-oficial/2013/DO-20130614.pdf",
    "url": "/storage/transparencia/01-actos/diario-oficial/2013/DO-20130614.pdf",
    "mime": "application/pdf",
    "size": 184320
  }
}
```

Una selección múltiple usa el evento `kcfinder:files-selected` y la propiedad `files`, que conserva el orden seleccionado.

Los errores usan `kcfinder:selection-error`, versión `1`, y un objeto `error` con `code` y `message`. Las respuestas públicas no incluyen rutas físicas ni trazas.

## Integración entre procedencias

La misma procedencia siempre está permitida. Para entregar mensajes a otra procedencia, agregue su origen exacto en `conf/config.local.php`:

```php
'_selectorAllowedOrigins' => array(
    'https://app.example.cl',
    'https://admin.example.cl:8443',
),
```

Abra luego el selector indicando uno de esos valores:

```text
browse.php?selector=1&selectorOrigin=https%3A%2F%2Fapp.example.cl
```

KCFinder valida esquema, host y puerto, y usa ese valor como `targetOrigin`. No acepta `*`, credenciales, rutas, consultas ni fragmentos.

La aplicación receptora también debe validar el origen y el contrato:

```javascript
window.addEventListener('message', function (event) {
    if (event.origin !== 'https://files.example.cl')
        return;

    var message = event.data;
    if (!message || message.version !== 1)
        return;

    if (message.event === 'kcfinder:file-selected')
        useSelectedFile(message.file);
});
```

No configure un origen sólo porque aparece en la solicitud. La lista del servidor debe contener únicamente aplicaciones confiables.

## Alcance inicial

- La selección normal admite objeto simple o múltiple.
- La selección de miniaturas conserva por ahora el callback heredado de URL; no se presenta una miniatura generada como si fuera el archivo original.
- Los adaptadores futuros de Laravel y Symfony podrán reemplazar autorización, metadatos y resolución de URLs mediante los contratos del núcleo.

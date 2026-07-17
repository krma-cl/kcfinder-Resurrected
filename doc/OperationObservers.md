# Observadores de operaciones

KCFinder 4.6 permite conectar auditoría, catálogos y adaptadores de frameworks con las operaciones del navegador clásico sin modificar sus controladores. Desde 4.8 también cubre copias y operaciones de carpetas. La capacidad es optativa y el observador predeterminado no realiza ninguna acción.

## Contrato

Un observador implementa `KCFinder\Contract\OperationObserverInterface`:

```php
use KCFinder\Contract\OperationObserverInterface;
use KCFinder\Domain\OperationContext;

final class ApplicationObserver implements OperationObserverInterface
{
    public function before(OperationContext $operation): mixed
    {
        // Captura el estado necesario antes de mover, renombrar o eliminar.
        return null;
    }

    public function succeeded(OperationContext $operation, mixed $previousState = null): void
    {
        // Sincroniza auditoría, catálogo o eventos después del éxito real.
    }
}
```

Registre la instancia en `conf/config.local.php`:

```php
$_LOCALS['_operationObserver'] = $applicationObserver;
```

El archivo de configuración local es código PHP y puede obtener la instancia desde el contenedor de la aplicación anfitriona. No almacene credenciales en el repositorio.

## Operaciones notificadas

| Operación | Recurso | Momento |
|---|---|---|
| `upload` | archivo | después de guardar y generar la miniatura |
| `edit` | archivo | después de guardar el resultado de edición o recorte |
| `copy` | archivo | antes y después de copiar, con ruta de origen y destino |
| `move` | archivo | antes y después de cambiar la ruta |
| `rename` | archivo | antes y después de cambiar el nombre |
| `delete` | archivo | antes y después de eliminar |
| `create_directory` | carpeta | después de crearla |
| `rename` | carpeta | antes y después de cambiar el nombre |
| `delete` | carpeta | después de eliminar sus hijos y la carpeta correspondiente |

Las operaciones masivas notifican cada archivo que efectivamente fue modificado. La eliminación recursiva emite primero los archivos y carpetas hijas y termina con la carpeta solicitada. Los elementos rechazados o fallidos no generan una notificación de éxito.

`OperationContext` contiene:

- `operation`: operación estable;
- `resource`: `file` o `directory`;
- `path`: ruta lógica original o actual;
- `targetPath`: destino para copiar, mover o renombrar;
- `resultingPath()`: ruta final de la operación.

Las rutas son absolutas dentro del tipo KCFinder seleccionado y nunca exponen la ruta física del servidor.

## Política de errores

Los callbacks se ejecutan fuera de la mutación principal. Si un observador o listener lanza una excepción:

- KCFinder registra el fallo mediante `error_log`;
- la operación de archivo ya completada conserva su respuesta exitosa;
- no se intenta una reversión parcial insegura;
- el integrador puede reintentar su sincronización desde su propia cola o auditoría.

Esta política evita informar que un archivo no fue cargado cuando el fallo real ocurrió en una tarea secundaria.

## Laravel

El paquete `krma-cl/kcfinder-laravel:^1.2` incluye un puente que implementa este contrato, toma snapshots autorizados y emite los eventos nativos del adaptador. Consulte la guía del adaptador para registrar la instancia desde el contenedor Laravel.

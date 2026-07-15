# Seguridad

## Reporte privado de vulnerabilidades

No publiques detalles explotables en conversaciones, commits ni incidencias públicas. Utiliza [Private vulnerability reporting](https://github.com/krma-cl/kcfinder-Resurrected/security/advisories/new) para enviar el impacto, la versión o commit afectado, los pasos mínimos de reproducción y cualquier mitigación conocida.

El código heredado de otros repositorios debe reportarse a sus respectivos mantenedores cuando la vulnerabilidad no esté presente en KCFinder Resurrected.

## Versiones mantenidas

La rama `master` representa el desarrollo mantenido. Para producción se debe utilizar una versión publicada o un commit fijado y comprobado; no se garantiza soporte de seguridad para copias antiguas del proyecto original ni para forks sin los cambios de este repositorio.

## Configuración mínima de producción

- Mantén `disabled` activado hasta que la aplicación anfitriona haya autenticado y autorizado al usuario. El ejemplo `integration/default.php` falla cerrado y no constituye un sistema de autenticación listo para producción.
- Define listas explícitas y coherentes en `allowExts` y `allowMimeTypes`. Evita `*`, incluso si se agregan exclusiones.
- Conserva `_sessionCsrf` activado y entrega el mismo token impredecible mediante sesión, cookie y solicitud. La aplicación anfitriona debe configurar sus cookies de sesión con HTTPS, `Secure`, `HttpOnly` y una política `SameSite` compatible con su forma de integración.
- Limita `_allowDomains` a orígenes conocidos cuando `_denyExtDomains` esté activo. No uses este control como reemplazo de autenticación o autorización.
- Aloja uploads fuera de directorios donde el servidor pueda ejecutar PHP u otros scripts. Si deben ser públicos, configura el servidor para servirlos como archivos estáticos y bloquear ejecución.
- Otorga al proceso web sólo permisos de lectura y escritura necesarios sobre uploads y miniaturas. No ejecutes el contenedor ni PHP como administrador.
- Mantén Fileinfo y un controlador de imágenes disponible, limita `_dropUploadMaxFilesize` y `_maxImagePixels`, y revisa esos valores según los recursos del servidor.
- Ejecuta `composer audit --locked` y `composer test` antes de desplegar una actualización.

Los controles de interfaz no son controles de seguridad. Toda operación debe seguir validando autorización, CSRF, tipo de archivo y confinamiento de ruta en el servidor.

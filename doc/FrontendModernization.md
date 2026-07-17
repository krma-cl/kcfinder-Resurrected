# Modernización futura del frontend

Este documento registra trabajo planificado para una versión posterior. No cambia las dependencias JavaScript de KCFinder 4.7 ni constituye una promesa de fecha.

## Línea base

KCFinder 4.7 conserva jQuery 3.7.1 y jQuery UI 1.13.2 para reducir el riesgo de regresiones en el navegador clásico, los temas y las integraciones heredadas.

## jQuery 4 y jQuery UI 1.14

jQuery 4 retiró APIs que el código actual todavía utiliza, entre ellas `$.isArray()`, `$.isFunction()` y `$.trim()`. La migración se realizará en etapas:

1. reemplazar las APIs retiradas manteniendo temporalmente jQuery 3.7.1;
2. actualizar jQuery UI a una versión compatible con jQuery 4;
3. probar el build completo de jQuery 4 con jQuery Migrate sólo durante desarrollo;
4. corregir todos los diagnósticos y retirar Migrate antes de publicar;
5. validar navegación, uploads, miniaturas, recorte, diálogos, selector, temas e integraciones.

KCFinder necesita AJAX y efectos, por lo que no utilizará el build `slim`.

Referencias:

- [jQuery 4.0.0](https://blog.jquery.com/2026/01/17/jquery-4-0-0/)
- [Guía de actualización a jQuery 4](https://jquery.com/upgrade-guide/4.0/)
- [jQuery UI 1.14.2](https://blog.jqueryui.com/2026/01/jquery-ui-1-14-2-released/)

## Solicitudes XHR síncronas

El navegador todavía contiene operaciones AJAX síncronas heredadas. Los navegadores actuales las ejecutan, pero advierten que bloquean el hilo principal.

No se corregirán cambiando simplemente `async: false` a `true`: varios flujos dependen del orden de inicialización y eso introduciría condiciones de carrera. Cada operación deberá migrarse a callbacks o Promises, con estados de carga y pruebas de regresión.

Esta advertencia no es un fallo de seguridad ni bloquea KCFinder 4.7, pero su eliminación forma parte de la misma futura línea de modernización.

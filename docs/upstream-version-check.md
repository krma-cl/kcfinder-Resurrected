# Revision periodica de versiones

KCFinder mantiene un verificador informativo para detectar nuevas versiones estables de las tecnologias principales del ecosistema sin modificar dependencias ni archivos de bloqueo.

El control incluye:

- PHP y la linea configurada en Docker;
- PHPUnit y PHPStan;
- Laravel, Symfony y Flysystem;
- Bootstrap, Bootstrap Icons y Sass;
- jQuery incluido en el nucleo.

El script consulta PHP.net, Packagist y npm, y compara sus resultados con las restricciones o versiones declaradas en los cuatro repositorios oficiales ubicados bajo un mismo directorio de trabajo.

## Ejecucion manual

Desde el repositorio principal:

```powershell
pwsh -NoProfile -File tools/check-upstream-versions.ps1
```

Para conservar una copia del informe:

```powershell
pwsh -NoProfile -File tools/check-upstream-versions.ps1 `
  -OutputPath "$env:TEMP\kcfinder-upstream-versions.md"
```

Si los repositorios no son hermanos dentro de un mismo directorio, indique la carpeta que los contiene:

```powershell
pwsh -NoProfile -File tools/check-upstream-versions.ps1 `
  -WorkspaceRoot 'C:\ruta\a\los\repositorios'
```

## Criterio del informe

- `AL DIA` identifica una version fijada vigente o una linea flotante, como PHP 8.5, que ya recibe el ultimo parche al reconstruir.
- `CUBIERTO` indica que la restriccion declarada ya admite la familia estable mas reciente.
- `ACTUALIZAR` informa una version mas nueva dentro de la misma version mayor.
- `REVISAR` identifica una nueva version mayor o una linea fuera de la politica declarada.
- `ERROR` indica que una fuente no pudo consultarse y debe volver a comprobarse.

Una alerta no autoriza una actualizacion automatica. Antes de cambiar restricciones o recursos versionados deben revisarse los changelogs, la compatibilidad de PHP, las pruebas de cada repositorio y los artefactos de distribucion.

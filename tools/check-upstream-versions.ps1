[CmdletBinding()]
param(
    [string] $WorkspaceRoot = (Split-Path (Split-Path $PSScriptRoot -Parent) -Parent),
    [string] $OutputPath = ''
)

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$headers = @{
    'Accept' = 'application/json'
    'User-Agent' = 'KCFinder-Upstream-Version-Checker/1.0'
}

$repositories = @{
    Core = Join-Path $WorkspaceRoot 'kcfinder-Resurrected'
    Laravel = Join-Path $WorkspaceRoot 'kcfinder-laravel'
    Symfony = Join-Path $WorkspaceRoot 'kcfinder-symfony-bundle'
    Theme = Join-Path $WorkspaceRoot 'kcfinder-bootstrap5-theme'
}

function Read-JsonFile {
    param([Parameter(Mandatory)][string] $Path)

    if (-not (Test-Path -LiteralPath $Path -PathType Leaf)) {
        throw "No se encontro el archivo requerido: $Path"
    }

    return Get-Content -LiteralPath $Path -Raw | ConvertFrom-Json
}

function ConvertTo-Version {
    param([Parameter(Mandatory)][string] $Value)

    if ($Value -notmatch '(?<version>\d+(?:\.\d+){0,3})') {
        throw "No se pudo interpretar la version: $Value"
    }

    $parts = $Matches.version.Split('.')
    while ($parts.Count -lt 4) {
        $parts += '0'
    }

    return [version]($parts[0..3] -join '.')
}

function Get-LatestPackagistVersion {
    param([Parameter(Mandatory)][string] $Package)

    $uri = "https://repo.packagist.org/p2/$Package.json"
    $response = Invoke-RestMethod -Uri $uri -Headers $headers
    $property = $response.packages.PSObject.Properties[$Package]

    if ($null -eq $property) {
        throw "Packagist no devolvio el paquete $Package"
    }

    $release = $property.Value |
        Where-Object { $_.version -notmatch '(?i)(dev|alpha|beta|rc)' } |
        Sort-Object { ConvertTo-Version $_.version_normalized } -Descending |
        Select-Object -First 1

    if ($null -eq $release) {
        throw "Packagist no devolvio una version estable para $Package"
    }

    return ([string]$release.version).TrimStart('v')
}

function Get-LatestNpmVersion {
    param([Parameter(Mandatory)][string] $Package)

    $response = Invoke-RestMethod -Uri "https://registry.npmjs.org/$Package/latest" -Headers $headers
    if (-not $response.version) {
        throw "npm no devolvio una version estable para $Package"
    }

    return [string]$response.version
}

function Get-LatestPhpVersion {
    $response = Invoke-RestMethod -Uri 'https://www.php.net/releases/index.php?json=1&version=8&max=50' -Headers $headers
    $versions = $response.PSObject.Properties.Name |
        Where-Object { $_ -match '^\d+\.\d+\.\d+$' } |
        Sort-Object { ConvertTo-Version $_ } -Descending

    if (-not $versions) {
        throw 'PHP.net no devolvio una version estable de PHP 8'
    }

    return [string]$versions[0]
}

function Get-ConstraintStatus {
    param(
        [Parameter(Mandatory)][string] $Constraint,
        [Parameter(Mandatory)][string] $Latest
    )

    $declaredMajors = [regex]::Matches($Constraint, '\d+(?:\.\d+){0,3}') |
        ForEach-Object { (ConvertTo-Version $_.Value).Major } |
        Sort-Object -Unique
    $latestVersion = ConvertTo-Version $Latest

    if ($declaredMajors -contains $latestVersion.Major) {
        return 'CUBIERTO'
    }

    return 'REVISAR'
}

function Get-PinnedStatus {
    param(
        [Parameter(Mandatory)][string] $Current,
        [Parameter(Mandatory)][string] $Latest
    )

    $currentVersion = ConvertTo-Version $Current
    $latestVersion = ConvertTo-Version $Latest

    if ($latestVersion -le $currentVersion) {
        return 'AL DIA'
    }

    if ($latestVersion.Major -eq $currentVersion.Major) {
        return 'ACTUALIZAR'
    }

    return 'REVISAR'
}

function Get-LineStatus {
    param(
        [Parameter(Mandatory)][string] $Current,
        [Parameter(Mandatory)][string] $Latest
    )

    $currentVersion = ConvertTo-Version $Current
    $latestVersion = ConvertTo-Version $Latest

    if ($currentVersion.Major -eq $latestVersion.Major -and
        $currentVersion.Minor -eq $latestVersion.Minor) {
        return 'AL DIA'
    }

    return 'REVISAR'
}

function Add-VersionCheck {
    param(
        [Parameter(Mandatory)][string] $Technology,
        [Parameter(Mandatory)][string] $Current,
        [Parameter(Mandatory)][ValidateSet('Constraint', 'Pinned', 'Line')][string] $Policy,
        [Parameter(Mandatory)][scriptblock] $LatestVersion,
        [Parameter(Mandatory)][string] $Source
    )

    try {
        $latest = [string](& $LatestVersion)
        $status = switch ($Policy) {
            'Constraint' { Get-ConstraintStatus -Constraint $Current -Latest $latest }
            'Pinned' { Get-PinnedStatus -Current $Current -Latest $latest }
            'Line' { Get-LineStatus -Current $Current -Latest $latest }
        }

        $script:results.Add([pscustomobject]@{
            Technology = $Technology
            Current = $Current
            Latest = $latest
            Status = $status
            Source = $Source
        })
    } catch {
        $script:results.Add([pscustomobject]@{
            Technology = $Technology
            Current = $Current
            Latest = 'No disponible'
            Status = 'ERROR'
            Source = $Source
        })
        $script:errors.Add("${Technology}: $($_.Exception.Message)")
    }
}

$coreComposer = Read-JsonFile (Join-Path $repositories.Core 'composer.json')
$laravelComposer = Read-JsonFile (Join-Path $repositories.Laravel 'composer.json')
$symfonyComposer = Read-JsonFile (Join-Path $repositories.Symfony 'composer.json')
$themePackage = Read-JsonFile (Join-Path $repositories.Theme 'package.json')

$dockerfile = Get-Content -LiteralPath (Join-Path $repositories.Core 'Dockerfile') -Raw
if ($dockerfile -notmatch '(?m)^ARG PHP_VERSION=(?<version>\d+\.\d+)\s*$') {
    throw 'No se pudo determinar PHP_VERSION desde Dockerfile'
}
$phpLine = $Matches.version

$jqueryHeader = Get-Content -LiteralPath (Join-Path $repositories.Core 'js/000._jquery.js') -TotalCount 1
if ($jqueryHeader -notmatch 'jQuery v(?<version>\d+\.\d+\.\d+)') {
    throw 'No se pudo determinar la version local de jQuery'
}
$jqueryVersion = $Matches.version

$results = [System.Collections.Generic.List[object]]::new()
$errors = [System.Collections.Generic.List[string]]::new()

Add-VersionCheck 'PHP' $phpLine 'Line' { Get-LatestPhpVersion } 'https://www.php.net/releases/'
Add-VersionCheck 'PHPUnit' $coreComposer.'require-dev'.'phpunit/phpunit' 'Constraint' { Get-LatestPackagistVersion 'phpunit/phpunit' } 'https://packagist.org/packages/phpunit/phpunit'
Add-VersionCheck 'PHPStan' $coreComposer.'require-dev'.'phpstan/phpstan' 'Constraint' { Get-LatestPackagistVersion 'phpstan/phpstan' } 'https://packagist.org/packages/phpstan/phpstan'
Add-VersionCheck 'Laravel' $laravelComposer.require.'illuminate/support' 'Constraint' { Get-LatestPackagistVersion 'laravel/framework' } 'https://packagist.org/packages/laravel/framework'
Add-VersionCheck 'Symfony' $symfonyComposer.require.'symfony/http-kernel' 'Constraint' { Get-LatestPackagistVersion 'symfony/framework-bundle' } 'https://packagist.org/packages/symfony/framework-bundle'
Add-VersionCheck 'Flysystem' $symfonyComposer.require.'league/flysystem' 'Constraint' { Get-LatestPackagistVersion 'league/flysystem' } 'https://packagist.org/packages/league/flysystem'
Add-VersionCheck 'Bootstrap' $themePackage.devDependencies.bootstrap 'Pinned' { Get-LatestNpmVersion 'bootstrap' } 'https://www.npmjs.com/package/bootstrap'
Add-VersionCheck 'Bootstrap Icons' $themePackage.devDependencies.'bootstrap-icons' 'Pinned' { Get-LatestNpmVersion 'bootstrap-icons' } 'https://www.npmjs.com/package/bootstrap-icons'
Add-VersionCheck 'Sass' $themePackage.devDependencies.sass 'Pinned' { Get-LatestNpmVersion 'sass' } 'https://www.npmjs.com/package/sass'
Add-VersionCheck 'jQuery' $jqueryVersion 'Pinned' { Get-LatestNpmVersion 'jquery' } 'https://www.npmjs.com/package/jquery'

$lines = [System.Collections.Generic.List[string]]::new()
$lines.Add('# Informe de versiones del ecosistema KCFinder')
$lines.Add('')
$lines.Add("Generado: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss K')")
$lines.Add('')
$lines.Add('| Tecnologia | Version o politica actual | Ultima estable | Estado | Fuente |')
$lines.Add('|---|---:|---:|---|---|')

foreach ($result in $results) {
    $current = ([string]$result.Current).Replace('|', '\|')
    $lines.Add("| $($result.Technology) | $current | $($result.Latest) | **$($result.Status)** | [consultar]($($result.Source)) |")
}

$lines.Add('')
$lines.Add('## Interpretacion')
$lines.Add('')
$lines.Add('- **AL DIA:** la version fijada o la linea flotante ya corresponde a la ultima estable.')
$lines.Add('- **CUBIERTO:** la restriccion declarada admite la familia de la ultima version estable.')
$lines.Add('- **ACTUALIZAR:** existe una version estable mas nueva dentro de la misma version mayor; conviene revisar changelog y pruebas.')
$lines.Add('- **REVISAR:** existe una nueva version mayor o una nueva linea no declarada; requiere una decision de compatibilidad.')
$lines.Add('- **ERROR:** no fue posible consultar una fuente; no debe interpretarse como que el componente este actualizado.')
$lines.Add('')
$lines.Add('El informe es deliberadamente informativo: no modifica dependencias, locks, tags ni archivos del proyecto.')

if ($errors.Count -gt 0) {
    $lines.Add('')
    $lines.Add('## Errores de consulta')
    $lines.Add('')
    foreach ($message in $errors) {
        $lines.Add("- $message")
    }
}

$report = $lines -join [Environment]::NewLine
$report

if ($OutputPath) {
    $absoluteOutputPath = $ExecutionContext.SessionState.Path.GetUnresolvedProviderPathFromPSPath($OutputPath)
    $outputDirectory = Split-Path $absoluteOutputPath -Parent
    if ($outputDirectory -and -not (Test-Path -LiteralPath $outputDirectory)) {
        New-Item -ItemType Directory -Path $outputDirectory | Out-Null
    }
    Set-Content -LiteralPath $absoluteOutputPath -Value $report -Encoding utf8
}

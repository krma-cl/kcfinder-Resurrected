<?php

declare(strict_types=1);

$archivePath = $argv[1] ?? '';
if (!is_string($archivePath) || $archivePath === '' || !is_file($archivePath)) {
    fwrite(STDERR, "Usage: php tools/verify-composer-archive.php path/to/archive.zip\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($archivePath) !== true) {
    throw new RuntimeException('Unable to open the Composer archive.');
}

$names = array();
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = $zip->getNameIndex($index);
    if (!is_string($name) || $name === '' || str_contains($name, '\\') || str_starts_with($name, '/')) {
        throw new RuntimeException('The Composer archive contains an invalid path.');
    }
    foreach (explode('/', rtrim($name, '/')) as $segment) {
        if ($segment === '..') {
            throw new RuntimeException('The Composer archive contains a traversal path.');
        }
    }
    $names[] = $name;
}

foreach (array('composer.json', 'browse.php', 'core/autoload.php', 'src/Application/SelectorEnvelope.php') as $required) {
    if (!in_array($required, $names, true)) {
        throw new RuntimeException('The Composer archive is missing ' . $required);
    }
}

$composerContents = $zip->getFromName('composer.json');
$composer = is_string($composerContents) ? json_decode($composerContents, true) : null;
if (!is_array($composer) || ($composer['name'] ?? null) !== 'krma-cl/kcfinder') {
    throw new RuntimeException('The Composer archive has an unexpected package identity.');
}

$forbiddenPrefixes = array('.github/', '.phpunit.cache/', 'dist/', 'docker/', 'test/', 'tests/', 'tools/', 'upload/', 'vendor/');
$forbiddenFiles = array(
    '.dockerignore',
    '.env.example',
    '.gitattributes',
    '.gitignore',
    'Dockerfile',
    'compose.yaml',
    'composer.lock',
    'phpstan.neon',
    'phpunit.xml.dist',
);
foreach ($names as $name) {
    foreach ($forbiddenPrefixes as $prefix) {
        if (str_starts_with($name, $prefix)) {
            throw new RuntimeException('The Composer archive contains development or local content: ' . $name);
        }
    }
    if (in_array($name, $forbiddenFiles, true) || preg_match('#^cache/.*\.(?:css|js)$#', $name)) {
        throw new RuntimeException('The Composer archive contains development or generated content: ' . $name);
    }
}

$zip->close();
fwrite(STDOUT, 'Composer archive verified: ' . $archivePath . PHP_EOL);

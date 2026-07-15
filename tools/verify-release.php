<?php

declare(strict_types=1);

$archivePath = $argv[1] ?? '';
if (!is_string($archivePath) || $archivePath === '' || !is_file($archivePath)) {
    fwrite(STDERR, "Usage: php tools/verify-release.php path/to/release.zip\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($archivePath) !== true) {
    throw new RuntimeException('Unable to open the release archive.');
}

$names = array();
for ($index = 0; $index < $zip->numFiles; $index++) {
    $name = $zip->getNameIndex($index);
    if (!is_string($name) || $name === '' || str_contains($name, '\\') || str_starts_with($name, '/')) {
        throw new RuntimeException('The release contains an invalid path.');
    }
    foreach (explode('/', rtrim($name, '/')) as $segment) {
        if ($segment === '..') {
            throw new RuntimeException('The release contains a traversal path.');
        }
    }
    $names[] = $name;
}

$roots = array_values(array_unique(array_map(
    static fn (string $name): string => explode('/', $name, 2)[0],
    $names
)));
if (count($roots) !== 1 || !preg_match('/^kcfinder-resurrected-[0-9A-Za-z.-]+$/', $roots[0])) {
    throw new RuntimeException('The release must contain exactly one versioned root directory.');
}
$root = $roots[0];

foreach (array('VERSION', 'MANIFEST.sha256', 'README.md', 'SECURITY.md', 'browse.php', 'composer.json', 'core/autoload.php', 'upload/.htaccess') as $required) {
    if (!in_array($root . '/' . $required, $names, true)) {
        throw new RuntimeException('Missing required release file: ' . $required);
    }
}

$version = $zip->getFromName($root . '/VERSION');
if (!is_string($version) || $root !== 'kcfinder-resurrected-' . trim($version)) {
    throw new RuntimeException('The VERSION file does not match the archive root.');
}

$composerContents = $zip->getFromName($root . '/composer.json');
$composer = is_string($composerContents) ? json_decode($composerContents, true) : null;
if (!is_array($composer) || ($composer['name'] ?? null) !== 'krma-cl/kcfinder') {
    throw new RuntimeException('The release does not identify the expected Composer package.');
}

foreach ($names as $name) {
    $relative = substr($name, strlen($root) + 1);
    if (preg_match('#^(?:\.github|docker|test|tests|tools|vendor)(?:/|$)#', $relative)) {
        throw new RuntimeException('Development-only content was included: ' . $relative);
    }
    if (in_array($relative, array(
        '.dockerignore',
        '.env.example',
        '.gitattributes',
        '.gitignore',
        'Dockerfile',
        'compose.yaml',
        'composer.lock',
        'phpstan.neon',
        'phpunit.xml.dist',
    ), true)) {
        throw new RuntimeException('Development-only file was included: ' . $relative);
    }
}

$manifest = $zip->getFromName($root . '/MANIFEST.sha256');
if (!is_string($manifest)) {
    throw new RuntimeException('Unable to read the release manifest.');
}

$manifestFiles = array();
foreach (array_filter(explode("\n", $manifest)) as $line) {
    if (!preg_match('/^([a-f0-9]{64})  (.+)$/', $line, $matches)) {
        throw new RuntimeException('The release manifest is malformed.');
    }
    $contents = $zip->getFromName($root . '/' . $matches[2]);
    if (!is_string($contents) || !hash_equals($matches[1], hash('sha256', $contents))) {
        throw new RuntimeException('Checksum mismatch for ' . $matches[2]);
    }
    $manifestFiles[] = $root . '/' . $matches[2];
}

$archivedFiles = array_values(array_filter(
    $names,
    static fn (string $name): bool => !str_ends_with($name, '/') && $name !== $root . '/MANIFEST.sha256'
));
sort($archivedFiles, SORT_STRING);
sort($manifestFiles, SORT_STRING);
if ($archivedFiles !== $manifestFiles) {
    throw new RuntimeException('The release manifest does not describe every archived file.');
}

$temporary = sys_get_temp_dir() . '/kcfinder-release-' . bin2hex(random_bytes(8));
if (!mkdir($temporary, 0700, true) || !$zip->extractTo($temporary)) {
    throw new RuntimeException('Unable to extract the release for verification.');
}
$zip->close();

try {
    $extractedRoot = $temporary . '/' . $root;
    $verification = <<<'PHP'
chdir($argv[1]);
require 'core/autoload.php';
exit(class_exists('KCFinder\\Application\\SelectorEnvelope') ? 0 : 1);
PHP;
    $process = proc_open(
        array(PHP_BINARY, '-r', $verification, $extractedRoot),
        array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to verify the traditional autoloader.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Traditional autoloader verification failed: ' . trim((string) $stdout . (string) $stderr));
    }
} finally {
    releaseRemoveTree($temporary);
}

$checksumPath = $archivePath . '.sha256';
if (is_file($checksumPath)) {
    $expected = trim((string) file_get_contents($checksumPath));
    $actual = hash_file('sha256', $archivePath) . '  ' . basename($archivePath);
    if (!hash_equals($actual, $expected)) {
        throw new RuntimeException('The external release checksum is invalid.');
    }
}

fwrite(STDOUT, 'Release archive verified: ' . $archivePath . PHP_EOL);

function releaseRemoveTree(string $directory): void
{
    if (!is_dir($directory)) {
        return;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($iterator as $item) {
        if ($item->isDir() && !$item->isLink()) {
            rmdir($item->getPathname());
        } else {
            unlink($item->getPathname());
        }
    }
    rmdir($directory);
}

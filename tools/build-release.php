<?php

declare(strict_types=1);

if (!class_exists(ZipArchive::class)) {
    fwrite(STDERR, "The ZIP extension is required to build a release.\n");
    exit(1);
}

$options = getopt('', array('version:', 'output-dir::', 'source-date-epoch::'));
$version = isset($options['version']) && is_string($options['version']) ? $options['version'] : '';
if (!preg_match('/^\d+\.\d+\.\d+(?:-[0-9A-Za-z][0-9A-Za-z.-]*)?$/', $version)) {
    fwrite(STDERR, "Usage: php tools/build-release.php --version=X.Y.Z[-suffix] [--output-dir=dist]\n");
    exit(1);
}

$root = dirname(__DIR__);
$outputOption = isset($options['output-dir']) && is_string($options['output-dir'])
    ? $options['output-dir']
    : 'dist';
$outputDirectory = preg_match('#^(?:[A-Za-z]:[\\/]|/)#', $outputOption)
    ? $outputOption
    : $root . '/' . trim(str_replace('\\', '/', $outputOption), '/');

if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0775, true) && !is_dir($outputDirectory)) {
    fwrite(STDERR, "Unable to create the output directory.\n");
    exit(1);
}

$epochOption = $options['source-date-epoch'] ?? getenv('SOURCE_DATE_EPOCH');
$epoch = is_string($epochOption) && ctype_digit($epochOption) ? (int) $epochOption : releaseGitTimestamp($root);
$epoch = max($epoch, 315532800);
$archiveRoot = 'kcfinder-resurrected-' . $version;
$archivePath = $outputDirectory . '/' . $archiveRoot . '.zip';
$checksumPath = $archivePath . '.sha256';

$files = array();
foreach (releaseTrackedFiles($root) as $relativePath) {
    if (releaseExcluded($relativePath)) {
        continue;
    }

    $absolutePath = $root . '/' . $relativePath;
    if (is_file($absolutePath)) {
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new RuntimeException('Unable to read ' . $relativePath);
        }
        $files[$relativePath] = $contents;
    }
}

$uploadProtection = file_get_contents($root . '/conf/upload.htaccess');
if ($uploadProtection === false) {
    throw new RuntimeException('Unable to read conf/upload.htaccess.');
}
$files['upload/.htaccess'] = $uploadProtection;
$files['VERSION'] = $version . "\n";
ksort($files, SORT_STRING);

$manifestLines = array();
foreach ($files as $relativePath => $contents) {
    $manifestLines[] = hash('sha256', $contents) . '  ' . $relativePath;
}
$manifest = implode("\n", $manifestLines) . "\n";

if (is_file($archivePath) && !unlink($archivePath)) {
    throw new RuntimeException('Unable to replace the existing release archive.');
}

$zip = new ZipArchive();
if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('Unable to create the release archive.');
}

try {
    releaseAddDirectory($zip, $archiveRoot . '/', $epoch);
    releaseAddDirectory($zip, $archiveRoot . '/upload/', $epoch);

    foreach ($files as $relativePath => $contents) {
        releaseAddFile($zip, $archiveRoot . '/' . $relativePath, $contents, $epoch);
    }
    releaseAddFile($zip, $archiveRoot . '/MANIFEST.sha256', $manifest, $epoch);
    $zip->setArchiveComment('KCFinder Resurrected ' . $version);
} finally {
    $zip->close();
}

$archiveHash = hash_file('sha256', $archivePath);
if (!is_string($archiveHash) || file_put_contents(
    $checksumPath,
    $archiveHash . '  ' . basename($archivePath) . "\n"
) === false) {
    throw new RuntimeException('Unable to write the release checksum.');
}

fwrite(STDOUT, $archivePath . PHP_EOL . $checksumPath . PHP_EOL);

/** @return list<string> */
function releaseTrackedFiles(string $root): array
{
    $process = proc_open(
        array('git', '-C', $root, 'ls-files', '-z'),
        array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to inspect tracked release files.');
    }

    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !is_string($stdout)) {
        throw new RuntimeException('Unable to inspect tracked release files: ' . trim((string) $stderr));
    }

    return array_values(array_filter(explode("\0", $stdout), static fn (string $file): bool => $file !== ''));
}

function releaseGitTimestamp(string $root): int
{
    $process = proc_open(
        array('git', '-C', $root, 'log', '-1', '--format=%ct'),
        array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')),
        $pipes
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to determine the source timestamp.');
    }

    $stdout = trim((string) stream_get_contents($pipes[1]));
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0 || !ctype_digit($stdout)) {
        throw new RuntimeException('Unable to determine the source timestamp: ' . trim((string) $stderr));
    }

    return (int) $stdout;
}

function releaseExcluded(string $path): bool
{
    $path = str_replace('\\', '/', $path);
    $prefixes = array('.github/', 'docker/', 'test/', 'tests/', 'tools/');
    foreach ($prefixes as $prefix) {
        if (str_starts_with($path, $prefix)) {
            return true;
        }
    }

    return in_array($path, array(
        '.dockerignore',
        '.env.example',
        '.gitattributes',
        '.gitignore',
        'Dockerfile',
        'compose.yaml',
        'composer.lock',
        'phpstan.neon',
        'phpunit.xml.dist',
    ), true);
}

function releaseAddDirectory(ZipArchive $zip, string $name, int $epoch): void
{
    $zip->addEmptyDir($name);
    $zip->setMtimeName($name, $epoch);
    $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 040755 << 16);
}

function releaseAddFile(ZipArchive $zip, string $name, string $contents, int $epoch): void
{
    if (!$zip->addFromString($name, $contents)) {
        throw new RuntimeException('Unable to add ' . $name . ' to the release archive.');
    }
    $zip->setMtimeName($name, $epoch);
    $zip->setExternalAttributesName($name, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    $zip->setCompressionName($name, ZipArchive::CM_DEFLATE, 9);
}

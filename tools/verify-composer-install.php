<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$temporary = sys_get_temp_dir() . '/kcfinder-composer-' . bin2hex(random_bytes(8));
if (!mkdir($temporary, 0700, true) && !is_dir($temporary)) {
    throw new RuntimeException('Unable to create the Composer verification directory.');
}

$definition = array(
    'name' => 'krma-cl/kcfinder-install-verification',
    'repositories' => array(array(
        'type' => 'path',
        'url' => str_replace('\\', '/', $root),
        'options' => array(
            'symlink' => false,
            'versions' => array('krma-cl/kcfinder' => '4.8.1-dev'),
        ),
    )),
    'require' => array('krma-cl/kcfinder' => '4.8.1-dev'),
    'config' => array('allow-plugins' => new stdClass()),
);

try {
    if (file_put_contents(
        $temporary . '/composer.json',
        json_encode($definition, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"
    ) === false) {
        throw new RuntimeException('Unable to write the Composer verification project.');
    }

    $composer = getenv('COMPOSER_BINARY');
    $command = is_string($composer) && $composer !== ''
        ? array(PHP_BINARY, $composer)
        : array('composer');
    array_push($command, 'install', '--working-dir=' . $temporary, '--no-interaction', '--no-progress', '--prefer-dist');

    $process = proc_open($command, array(1 => array('pipe', 'w'), 2 => array('pipe', 'w')), $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start Composer.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Composer installation failed: ' . trim((string) $stdout . (string) $stderr));
    }

    require $temporary . '/vendor/autoload.php';
    if (!class_exists(KCFinder\Application\SelectorEnvelope::class)) {
        throw new RuntimeException('The installed PSR-4 autoloader is not functional.');
    }
    foreach (array('browse.php', 'core/bootstrap.php', 'themes/default/css.php') as $runtimeFile) {
        if (!is_file($temporary . '/vendor/krma-cl/kcfinder/' . $runtimeFile)) {
            throw new RuntimeException('The installed package is missing ' . $runtimeFile);
        }
    }

    fwrite(STDOUT, "Composer package installation verified.\n");
} finally {
    composerRemoveTree($temporary);
}

function composerRemoveTree(string $directory): void
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

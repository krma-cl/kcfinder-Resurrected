<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$excluded = array('.git', 'cache', 'upload', 'vendor');
$failures = array();
$checked = 0;

$iterator = new RecursiveIteratorIterator(
    new RecursiveCallbackFilterIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        static function (SplFileInfo $file) use ($excluded): bool {
            return !$file->isDir() || !in_array($file->getFilename(), $excluded, true);
        }
    )
);

foreach ($iterator as $file) {
    if (!$file instanceof SplFileInfo || !$file->isFile() || strtolower($file->getExtension()) !== 'php') {
        continue;
    }

    $checked++;
    $command = escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1';
    exec($command, $output, $exitCode);

    if ($exitCode !== 0) {
        $failures[] = implode(PHP_EOL, $output);
    }

    $output = array();
}

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

fwrite(STDOUT, 'PHP syntax OK (' . $checked . ' files).' . PHP_EOL);

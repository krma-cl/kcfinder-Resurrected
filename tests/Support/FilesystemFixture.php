<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/core/bootstrap.php';
require_once dirname(__DIR__, 2) . '/lib/strftime.php';

final class CharacterizationBrowser extends kcfinder\browser
{
    public function __construct(string $uploadDirectory)
    {
        $this->config = array(
            'access' => array(
                'files' => array(
                    'upload' => true,
                    'delete' => true,
                    'copy' => true,
                    'move' => true,
                    'rename' => true,
                ),
                'dirs' => array(
                    'create' => true,
                    'delete' => true,
                    'rename' => true,
                ),
            ),
            'allowExts' => 'txt pdf png jpg jpeg',
            'allowMimeTypes' => array(
                'application/pdf',
                'image/jpeg',
                'image/png',
                'text/plain',
            ),
            'denyExtensionRename' => true,
            'dirnameChangeChars' => array(),
            'dirPerms' => 0755,
            'filePerms' => 0644,
            'filenameChangeChars' => array(),
            'jpegQuality' => 90,
            'maxImageHeight' => 0,
            'maxImageWidth' => 0,
            'theme' => 'default',
            'thumbHeight' => 100,
            'thumbsDir' => '.thumbs',
            'thumbWidth' => 100,
            'types' => array('files' => ''),
            'uploadDir' => $uploadDirectory,
            'uploadURL' => '/storage',
            'watermark' => array('file' => ''),
            '_appendUniqueSuffixOnOverwrite' => true,
            '_dropUploadMaxFilesize' => 10 * 1024 * 1024,
            '_maxImagePixels' => 25_000_000,
            '_normalizeFilenames' => true,
            'disabled' => false,
        );

        $this->imageDriver = 'gd';
        $this->types = $this->config['types'];
        $this->type = 'files';
        $this->typeDir = $uploadDirectory . '/files';
        $this->typeURL = '/storage/files';
        $this->thumbsDir = $uploadDirectory . '/.thumbs';
        $this->thumbsTypeDir = $this->thumbsDir . '/files';
        $this->dateTimeSmall = '%Y-%m-%d %H:%M';
        $this->charset = 'UTF-8';
        $this->labels = array();
        $this->session = array('dir' => 'files');
        $this->selector = array(
            'enabled' => true,
            'multiple' => true,
            'targetOrigin' => 'http://localhost:8080',
            'error' => null,
        );
    }

    public function checkFixtureInputDirectory(string $directory, bool $includeType = true, bool $existing = true): string|false
    {
        return $this->checkInputDir($directory, $includeType, $existing);
    }

    public function checkFixturePath(string $path): bool
    {
        return $this->checkFilePath($path);
    }

    public function normalizeFixtureFilename(string $filename): string
    {
        return $this->normalizeFilename($filename);
    }

    public function normalizeFixtureDirectoryName(string $directory): string
    {
        return $this->normalizeDirname($directory);
    }

    public function validateFixtureUpload(array $file): string|bool
    {
        return parent::checkUploadedFile($file, false);
    }

    public function moveFixtureUpload(array $file, string $directory): string
    {
        return $this->moveUploadFile($file, $directory);
    }

    public function createFixtureThumbnail(string $file, bool $overwrite = true): bool
    {
        return $this->makeThumb($file, $overwrite);
    }

    public function listFixtureFiles(string $directory): array
    {
        return $this->invokePrivateBrowserMethod('getFiles', array($directory));
    }

    public function listFixtureDirectories(string $directory): array
    {
        return $this->invokePrivateBrowserMethod('getDirs', array($directory));
    }

    public function deleteFixtureFile(): bool
    {
        return $this->act_delete();
    }

    public function deleteFixtureDirectory(): bool
    {
        return $this->act_deleteDir();
    }

    public function selectFixtureFiles(array $request, bool $enabled = true, bool $multiple = true): array
    {
        $this->selector['enabled'] = $enabled;
        $this->selector['multiple'] = $multiple;
        $_POST = $request;

        return json_decode($this->act_select(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function checkUploadedFile($aFile = array(), $Check_isuploaded = true)
    {
        return parent::checkUploadedFile($aFile, false);
    }

    private function invokePrivateBrowserMethod(string $method, array $arguments): mixed
    {
        $reflection = new ReflectionMethod(kcfinder\browser::class, $method);

        return $reflection->invokeArgs($this, $arguments);
    }
}

final class FilesystemFixture
{
    private string $root;
    private CharacterizationBrowser $browser;

    public function __construct()
    {
        $this->root = sys_get_temp_dir() . '/kcfinder-characterization-' . bin2hex(random_bytes(8));

        if (!mkdir($this->typeDirectory(), 0755, true) && !is_dir($this->typeDirectory())) {
            throw new RuntimeException('Unable to create the fixture upload directory.');
        }

        if (!mkdir($this->thumbnailTypeDirectory(), 0755, true) && !is_dir($this->thumbnailTypeDirectory())) {
            throw new RuntimeException('Unable to create the fixture thumbnail directory.');
        }

        $this->browser = new CharacterizationBrowser($this->uploadDirectory());
    }

    public function browser(): CharacterizationBrowser
    {
        return $this->browser;
    }

    public function root(): string
    {
        return $this->root;
    }

    public function uploadDirectory(): string
    {
        return $this->root . '/upload';
    }

    public function typeDirectory(): string
    {
        return $this->uploadDirectory() . '/files';
    }

    public function thumbnailTypeDirectory(): string
    {
        return $this->uploadDirectory() . '/.thumbs/files';
    }

    public function createTypeDirectory(string $relativePath): string
    {
        $path = $this->typeDirectory() . '/' . trim($relativePath, '/');
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create fixture directory: ' . $relativePath);
        }

        return $path;
    }

    public function createThumbnailDirectory(string $relativePath): string
    {
        $path = $this->thumbnailTypeDirectory() . '/' . trim($relativePath, '/');
        if (!is_dir($path) && !mkdir($path, 0755, true) && !is_dir($path)) {
            throw new RuntimeException('Unable to create fixture thumbnail directory: ' . $relativePath);
        }

        return $path;
    }

    public function writeTypeFile(string $relativePath, string $contents): string
    {
        $path = $this->typeDirectory() . '/' . trim($relativePath, '/');
        $this->ensureParentDirectory($path);
        file_put_contents($path, $contents);

        return $path;
    }

    public function writeThumbnailFile(string $relativePath, string $contents = 'thumbnail'): string
    {
        $path = $this->thumbnailTypeDirectory() . '/' . trim($relativePath, '/');
        $this->ensureParentDirectory($path);
        file_put_contents($path, $contents);

        return $path;
    }

    public function writeStagedFile(string $contents): string
    {
        $directory = $this->root . '/staging';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create the staging directory.');
        }

        $path = $directory . '/' . bin2hex(random_bytes(8)) . '.tmp';
        file_put_contents($path, $contents);

        return $path;
    }

    public function createPng(string $relativePath, int $width, int $height): string
    {
        $path = $this->typeDirectory() . '/' . trim($relativePath, '/');
        $this->ensureParentDirectory($path);

        $image = imagecreatetruecolor($width, $height);
        if (!$image instanceof GdImage) {
            throw new RuntimeException('Unable to create fixture image.');
        }

        $background = imagecolorallocate($image, 41, 128, 185);
        imagefilledrectangle($image, 0, 0, $width, $height, $background);
        imagepng($image, $path);

        return $path;
    }

    public function destroy(): void
    {
        if (!is_dir($this->root)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir() && !$item->isLink()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($this->root);
    }

    private function ensureParentDirectory(string $path): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create fixture parent directory.');
        }
    }
}

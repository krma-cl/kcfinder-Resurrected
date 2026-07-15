<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RuntimeEnvironmentTest extends TestCase
{
    public function testSupportedPhpVersionIsInUse(): void
    {
        self::assertGreaterThanOrEqual(80200, PHP_VERSION_ID, 'KCFinder requires PHP 8.2 or newer.');
    }

    public function testRequiredPhpExtensionsAreAvailable(): void
    {
        foreach (array('exif', 'fileinfo', 'gd', 'intl', 'mbstring', 'zip') as $extension) {
            self::assertTrue(extension_loaded($extension), 'Missing PHP extension: ' . $extension);
        }
    }

    public function testGdCanCreateAndResizeAnImage(): void
    {
        $source = imagecreatetruecolor(20, 20);
        $thumbnail = imagecreatetruecolor(10, 10);

        self::assertInstanceOf(GdImage::class, $source);
        self::assertInstanceOf(GdImage::class, $thumbnail);
        self::assertTrue(imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, 10, 10, 20, 20));
    }

    public function testFileinfoAndZipCanProcessTemporaryFiles(): void
    {
        $directory = sys_get_temp_dir() . '/kcfinder-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($directory, 0700));

        $textFile = $directory . '/sample.txt';
        $zipFile = $directory . '/sample.zip';
        file_put_contents($textFile, 'KCFinder');

        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            self::assertSame('text/plain', $finfo->file($textFile));

            $zip = new ZipArchive();
            self::assertTrue($zip->open($zipFile, ZipArchive::CREATE));
            self::assertTrue($zip->addFile($textFile, 'sample.txt'));
            self::assertTrue($zip->close());
            self::assertFileExists($zipFile);
        } finally {
            @unlink($zipFile);
            @unlink($textFile);
            @rmdir($directory);
        }
    }

    public function testPhpSessionCanPersistData(): void
    {
        self::assertTrue(session_start());
        $sessionId = session_id();
        $_SESSION['kcfinder_test'] = 'ok';
        session_write_close();

        session_id($sessionId);
        self::assertTrue(session_start());
        self::assertSame('ok', $_SESSION['kcfinder_test']);
        session_destroy();
    }
}

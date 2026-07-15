<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class FilesystemNavigationTest extends TestCase
{
    private FilesystemFixture $fixture;

    protected function setUp(): void
    {
        $this->fixture = new FilesystemFixture();
    }

    protected function tearDown(): void
    {
        $this->fixture->destroy();
    }

    public function testDirectoryListingHidesDotDirectoriesAndReportsChildren(): void
    {
        $this->fixture->createTypeDirectory('documents/annual');
        $this->fixture->createTypeDirectory('empty');
        $this->fixture->createTypeDirectory('.hidden');

        $directories = $this->fixture->browser()->listFixtureDirectories($this->fixture->typeDirectory());
        $byName = array_column($directories, null, 'name');

        self::assertSame(array('documents', 'empty'), array_keys($byName));
        self::assertTrue($byName['documents']['hasDirs']);
        self::assertFalse($byName['empty']['hasDirs']);
        self::assertTrue($byName['documents']['readable']);
        self::assertTrue($byName['documents']['writable']);
    }

    public function testFileListingReturnsMetadataAndCreatesLargeImageThumbnail(): void
    {
        $text = $this->fixture->writeTypeFile('documents/report.txt', 'KCFinder characterization');
        $image = $this->fixture->createPng('documents/preview.png', 200, 120);

        $files = $this->fixture->browser()->listFixtureFiles('files/documents');
        $byName = array_column($files, null, 'name');

        self::assertSame(array('preview.png', 'report.txt'), array_keys($byName));
        self::assertSame(filesize($text), $byName['report.txt']['size']);
        self::assertFalse($byName['report.txt']['isImage']);
        self::assertNull($byName['report.txt']['width']);
        self::assertSame(200, $byName['preview.png']['width']);
        self::assertSame(120, $byName['preview.png']['height']);
        self::assertTrue($byName['preview.png']['isImage']);
        self::assertTrue($byName['preview.png']['thumb']);

        $thumbnail = $this->fixture->thumbnailTypeDirectory() . '/documents/preview.png';
        self::assertFileExists($thumbnail);
        self::assertSame(array(100, 60), array_slice(getimagesize($thumbnail), 0, 2));
        self::assertFileExists($image);
    }
}

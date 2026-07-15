<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class UploadAndDeletionTest extends TestCase
{
    private FilesystemFixture $fixture;

    protected function setUp(): void
    {
        $this->fixture = new FilesystemFixture();
        $_POST = array();
        $_SESSION = array('kcCsrf' => 'fixture-token');
        $_COOKIE = array('kcCsrf' => 'fixture-token');
    }

    protected function tearDown(): void
    {
        $_POST = array();
        $_SESSION = array();
        $_COOKIE = array();
        $this->fixture->destroy();
    }

    public function testValidatedUploadIsNormalizedAndDoesNotOverwriteExistingFile(): void
    {
        $directory = $this->fixture->createTypeDirectory('documents');
        $browser = $this->fixture->browser();

        $first = $this->fixture->writeStagedFile('first document');
        $firstResult = $browser->moveFixtureUpload($this->upload('Quarterly Report.TXT', $first), $directory);

        $second = $this->fixture->writeStagedFile('second document');
        $secondResult = $browser->moveFixtureUpload($this->upload('Quarterly Report.TXT', $second), $directory);

        self::assertSame('/quarterly-report.txt', $firstResult);
        self::assertSame('/quarterly-report(1).txt', $secondResult);
        self::assertSame('first document', file_get_contents($directory . '/quarterly-report.txt'));
        self::assertSame('second document', file_get_contents($directory . '/quarterly-report(1).txt'));
    }

    public function testDangerousUploadExtensionIsRejectedAndTemporaryFileIsRemoved(): void
    {
        $temporary = $this->fixture->writeStagedFile('<?php echo "unsafe";');

        $result = $this->fixture->browser()->validateFixtureUpload($this->upload('payload.php', $temporary));

        self::assertSame('Denied file extension.', $result);
        self::assertFileDoesNotExist($temporary);
    }

    public function testMalformedMultiFileMetadataIsRejectedWithoutWarnings(): void
    {
        $result = $this->fixture->browser()->validateFixtureUpload(array(
            'name' => array('report.txt'),
            'tmp_name' => 'not-an-array',
            'error' => array(UPLOAD_ERR_OK),
        ));

        self::assertSame('Invalid file upload.', $result);
    }

    public function testValidMultiFileMetadataKeepsLegacyValidationBehavior(): void
    {
        $first = $this->fixture->writeStagedFile('first document');
        $second = $this->fixture->writeStagedFile('second document');

        $result = $this->fixture->browser()->validateFixtureUpload(array(
            'name' => array('first.txt', 'second.txt'),
            'tmp_name' => array($first, $second),
            'error' => array(UPLOAD_ERR_OK, UPLOAD_ERR_OK),
        ));

        self::assertTrue($result);
        self::assertFileExists($first);
        self::assertFileExists($second);
    }

    public function testDeleteRemovesFileAndItsGeneratedThumbnail(): void
    {
        $file = $this->fixture->writeTypeFile('documents/delete.txt', 'delete me');
        $thumbnail = $this->fixture->writeThumbnailFile('documents/delete.txt');
        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'file' => 'delete.txt',
        );

        self::assertTrue($this->fixture->browser()->deleteFixtureFile());
        self::assertFileDoesNotExist($file);
        self::assertFileDoesNotExist($thumbnail);
    }

    public function testDeleteDirectoryRemovesSourceAndThumbnailTrees(): void
    {
        $directory = $this->fixture->createTypeDirectory('documents/obsolete');
        $this->fixture->writeTypeFile('documents/obsolete/nested.txt', 'delete me');
        $thumbnailDirectory = $this->fixture->createThumbnailDirectory('documents/obsolete');
        $this->fixture->writeThumbnailFile('documents/obsolete/nested.txt');
        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents/obsolete',
        );

        self::assertTrue($this->fixture->browser()->deleteFixtureDirectory());
        self::assertDirectoryDoesNotExist($directory);
        self::assertDirectoryDoesNotExist($thumbnailDirectory);
    }

    private function upload(string $name, string $temporaryPath): array
    {
        return array(
            'name' => $name,
            'tmp_name' => $temporaryPath,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temporaryPath),
        );
    }
}

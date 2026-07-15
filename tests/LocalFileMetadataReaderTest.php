<?php

declare(strict_types=1);

use KCFinder\Infrastructure\LocalFileMetadataReader;
use KCFinder\Infrastructure\PrefixUrlResolver;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class LocalFileMetadataReaderTest extends TestCase
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

    public function testReaderBuildsServerVerifiedMetadataWithoutExposingThePhysicalPath(): void
    {
        $contents = 'KCFinder metadata contract';
        $this->fixture->writeTypeFile('01-actos/informe público.txt', $contents);
        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage/transparencia')
        );

        $descriptor = $reader->metadata('/01-actos/informe público.txt');

        self::assertSame('informe público.txt', $descriptor->name);
        self::assertSame('/01-actos/informe público.txt', $descriptor->path);
        self::assertSame('/storage/transparencia/01-actos/informe%20p%C3%BAblico.txt', $descriptor->url);
        self::assertSame('text/plain', $descriptor->mime);
        self::assertSame(strlen($contents), $descriptor->size);
        self::assertStringNotContainsString($this->fixture->root(), json_encode($descriptor, JSON_THROW_ON_ERROR));
    }

    public function testDescribeRemainsACompatibilityAliasForMetadata(): void
    {
        $this->fixture->writeTypeFile('documents/report.txt', 'report');
        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage')
        );

        self::assertEquals(
            $reader->metadata('/documents/report.txt'),
            $reader->describe('/documents/report.txt')
        );
    }

    public function testReaderRejectsTraversalBeforeResolvingTheFilesystemPath(): void
    {
        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage')
        );

        $this->expectException(InvalidArgumentException::class);
        $reader->describe('../outside.txt');
    }

    public function testReaderRejectsParentSegmentsEvenWhenTheTargetExists(): void
    {
        file_put_contents($this->fixture->root() . '/outside.txt', 'outside');
        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage')
        );

        $this->expectException(InvalidArgumentException::class);
        $reader->describe('../../outside.txt');
    }

    public function testReaderRejectsASymbolicLinkThatEscapesTheStorageRoot(): void
    {
        $outside = $this->fixture->root() . '/outside.txt';
        file_put_contents($outside, 'outside');
        $link = $this->fixture->typeDirectory() . '/outside-link.txt';
        if (!function_exists('symlink') || !@symlink($outside, $link)) {
            self::markTestSkipped('Symbolic links are not available in this environment.');
        }

        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage')
        );

        $this->expectException(RuntimeException::class);
        $reader->describe('/outside-link.txt');
    }

    public function testReaderRejectsMissingFilesWithoutLeakingTheirPhysicalPath(): void
    {
        $reader = new LocalFileMetadataReader(
            $this->fixture->typeDirectory(),
            new PrefixUrlResolver('/storage')
        );

        try {
            $reader->describe('/documents/missing.txt');
            self::fail('A missing file must not produce metadata.');
        } catch (RuntimeException $exception) {
            self::assertSame('The requested file does not exist.', $exception->getMessage());
            self::assertStringNotContainsString($this->fixture->root(), $exception->getMessage());
        }
    }
}

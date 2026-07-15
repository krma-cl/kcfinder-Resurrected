<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class PathContractTest extends TestCase
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

    public function testInputDirectoriesRemainConfinedToTheSelectedType(): void
    {
        $this->fixture->createTypeDirectory('documents/annual');
        $browser = $this->fixture->browser();

        self::assertSame('documents/annual', $browser->checkFixtureInputDirectory('files/documents/annual'));
        self::assertSame('documents/annual', $browser->checkFixtureInputDirectory('documents/annual', false));
        self::assertFalse($browser->checkFixtureInputDirectory('images/documents'));
        self::assertFalse($browser->checkFixtureInputDirectory('files/../outside'));
        self::assertFalse($browser->checkFixtureInputDirectory('../files/documents'));
        self::assertFalse($browser->checkFixtureInputDirectory('files/.hidden'));
        self::assertFalse($browser->checkFixtureInputDirectory('files/missing'));
    }

    public function testResolvedPathsCannotEscapeTheTypeDirectory(): void
    {
        $inside = $this->fixture->writeTypeFile('documents/report.txt', 'inside');
        $outside = $this->fixture->root() . '/outside.txt';
        file_put_contents($outside, 'outside');

        self::assertTrue($this->fixture->browser()->checkFixturePath($inside));
        self::assertFalse($this->fixture->browser()->checkFixturePath($outside));
        self::assertFalse($this->fixture->browser()->checkFixturePath($this->fixture->typeDirectory() . '/../outside.txt'));
    }

    public function testLegacyFilenameNormalizationRemainsStable(): void
    {
        $browser = $this->fixture->browser();

        self::assertSame('informe-publico-2026.pdf', $browser->normalizeFixtureFilename('Informe Público 2026.PDF'));
        self::assertSame('documentacion-2026', $browser->normalizeFixtureDirectoryName('Documentación 2026'));
    }
}

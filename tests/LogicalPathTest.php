<?php

declare(strict_types=1);

use KCFinder\Domain\LogicalPath;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LogicalPathTest extends TestCase
{
    public function testRelativeInputIsConvertedToOneCanonicalLogicalPath(): void
    {
        $path = LogicalPath::fromString('documents/annual/report.pdf');

        self::assertSame('/documents/annual/report.pdf', $path->value());
        self::assertSame('/documents/annual/report.pdf', (string) $path);
    }

    public static function invalidPathProvider(): array
    {
        return array(
            'empty' => array(''),
            'root only' => array('/'),
            'empty segment' => array('/documents//report.pdf'),
            'current segment' => array('/documents/./report.pdf'),
            'parent segment' => array('/documents/../report.pdf'),
            'backslash' => array('documents\\report.pdf'),
            'null byte' => array("documents/report.pdf\0.txt"),
        );
    }

    #[DataProvider('invalidPathProvider')]
    public function testAmbiguousOrUnsafePathsAreRejected(string $path): void
    {
        $this->expectException(InvalidArgumentException::class);
        LogicalPath::fromString($path);
    }
}

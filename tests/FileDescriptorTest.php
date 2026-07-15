<?php

declare(strict_types=1);

use KCFinder\Domain\FileDescriptor;
use PHPUnit\Framework\TestCase;

final class FileDescriptorTest extends TestCase
{
    public function testDescriptorSerializesTheVersionOneFieldsExactly(): void
    {
        $descriptor = new FileDescriptor(
            'DO-20130614.pdf',
            '/01-actos/diario-oficial/2013/DO-20130614.pdf',
            '/storage/transparencia/01-actos/diario-oficial/2013/DO-20130614.pdf',
            'application/pdf',
            184320
        );

        self::assertSame(array(
            'name' => 'DO-20130614.pdf',
            'path' => '/01-actos/diario-oficial/2013/DO-20130614.pdf',
            'url' => '/storage/transparencia/01-actos/diario-oficial/2013/DO-20130614.pdf',
            'mime' => 'application/pdf',
            'size' => 184320,
        ), $descriptor->toArray());

        self::assertSame($descriptor->toArray(), json_decode((string) json_encode($descriptor), true));
    }

    public function testDescriptorRejectsANameThatDoesNotMatchItsPath(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileDescriptor(
            'other.pdf',
            '/documents/report.pdf',
            '/storage/documents/report.pdf',
            'application/pdf',
            10
        );
    }

    public function testDescriptorRejectsTraversalSegments(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new FileDescriptor(
            'secret.txt',
            '/documents/../secret.txt',
            '/storage/secret.txt',
            'text/plain',
            10
        );
    }
}

<?php

declare(strict_types=1);

use KCFinder\Application\SelectorEnvelope;
use KCFinder\Domain\FileDescriptor;
use PHPUnit\Framework\TestCase;

final class SelectorEnvelopeTest extends TestCase
{
    public function testSingleEnvelopeUsesTheVersionOneContract(): void
    {
        $descriptor = $this->descriptor('report.pdf', 123);
        $envelope = SelectorEnvelope::single($descriptor);

        self::assertSame(array(
            'event' => 'kcfinder:file-selected',
            'version' => 1,
            'file' => $descriptor->toArray(),
        ), $envelope->toArray());
        self::assertSame($envelope->toArray(), json_decode((string) json_encode($envelope), true));
    }

    public function testMultipleEnvelopePreservesSelectionOrder(): void
    {
        $first = $this->descriptor('first.pdf', 1);
        $second = $this->descriptor('second.pdf', 2);

        self::assertSame(array(
            'event' => 'kcfinder:files-selected',
            'version' => 1,
            'files' => array($first->toArray(), $second->toArray()),
        ), SelectorEnvelope::multiple(array($first, $second))->toArray());
    }

    public function testMultipleEnvelopeRejectsAnEmptySelection(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SelectorEnvelope::multiple(array());
    }

    private function descriptor(string $name, int $size): FileDescriptor
    {
        return new FileDescriptor(
            $name,
            '/documents/' . $name,
            '/storage/files/documents/' . $name,
            'application/pdf',
            $size
        );
    }
}

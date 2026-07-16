<?php

declare(strict_types=1);

use KCFinder\Domain\OperationContext;
use PHPUnit\Framework\TestCase;

final class OperationContextTest extends TestCase
{
    public function testItNormalizesAndSerializesRelocatingOperations(): void
    {
        $operation = new OperationContext('move', 'documents/old.pdf', '/archive/new.pdf');

        self::assertSame('/archive/new.pdf', $operation->resultingPath());
        self::assertSame(array(
            'operation' => 'move',
            'resource' => 'file',
            'path' => '/documents/old.pdf',
            'targetPath' => '/archive/new.pdf',
        ), $operation->toArray());
    }

    public function testOnlyMoveAndRenameAcceptATargetPath(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new OperationContext('delete', '/documents/file.pdf', '/archive/file.pdf');
    }
}

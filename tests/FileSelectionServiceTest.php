<?php

declare(strict_types=1);

use KCFinder\Application\FileSelectionService;
use KCFinder\Contract\AuthorizationInterface;
use KCFinder\Contract\FileMetadataProviderInterface;
use KCFinder\Exception\AuthorizationDenied;
use KCFinder\Infrastructure\CallbackAuthorization;
use KCFinder\Infrastructure\LocalFileMetadataReader;
use KCFinder\Infrastructure\PrefixUrlResolver;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class FileSelectionServiceTest extends TestCase
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

    public function testSelectionAuthorizesTheCanonicalPathBeforeReturningMetadata(): void
    {
        $this->fixture->writeTypeFile('documents/report.txt', 'public report');
        $decisions = array();
        $authorization = new CallbackAuthorization(
            static function (string $operation, string $path) use (&$decisions): bool {
                $decisions[] = array($operation, $path);
                return true;
            }
        );
        $service = new FileSelectionService(
            new LocalFileMetadataReader(
                $this->fixture->typeDirectory(),
                new PrefixUrlResolver('/storage')
            ),
            $authorization
        );

        $descriptor = $service->select('documents/report.txt');

        self::assertSame(array(array('select', '/documents/report.txt')), $decisions);
        self::assertSame('/documents/report.txt', $descriptor->path);
        self::assertSame('/storage/documents/report.txt', $descriptor->url);
    }

    public function testDeniedSelectionDoesNotConsultStorage(): void
    {
        $metadata = $this->createMock(FileMetadataProviderInterface::class);
        $metadata->expects(self::never())->method('metadata');
        $service = new FileSelectionService(
            $metadata,
            new CallbackAuthorization(static fn (string $operation, string $path): bool => false)
        );

        $this->expectException(AuthorizationDenied::class);
        $this->expectExceptionMessage('The requested file operation is not authorized.');
        $service->select('/private/payroll.pdf');
    }

    public function testInvalidPathIsRejectedBeforeAuthorizationAndStorage(): void
    {
        $metadata = $this->createMock(FileMetadataProviderInterface::class);
        $metadata->expects(self::never())->method('metadata');
        $authorization = $this->createMock(AuthorizationInterface::class);
        $authorization->expects(self::never())->method('can');
        $service = new FileSelectionService($metadata, $authorization);

        $this->expectException(InvalidArgumentException::class);
        $service->select('/documents/../private.txt');
    }
}

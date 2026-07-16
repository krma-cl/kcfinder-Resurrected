<?php

declare(strict_types=1);

use KCFinder\Contract\OperationObserverInterface;
use KCFinder\Domain\OperationContext;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/Support/FilesystemFixture.php';

final class RecordingOperationObserver implements OperationObserverInterface
{
    /** @var array<int, OperationContext> */
    public array $before = array();

    /** @var array<int, array{operation: OperationContext, state: mixed}> */
    public array $succeeded = array();

    public function before(OperationContext $operation): mixed
    {
        $this->before[] = $operation;
        return 'snapshot:' . $operation->path;
    }

    public function succeeded(OperationContext $operation, mixed $previousState = null): void
    {
        $this->succeeded[] = array('operation' => $operation, 'state' => $previousState);
    }
}

final class FailingOperationObserver implements OperationObserverInterface
{
    public function before(OperationContext $operation): mixed
    {
        throw new RuntimeException('before listener failed');
    }

    public function succeeded(OperationContext $operation, mixed $previousState = null): void
    {
        throw new RuntimeException('success listener failed');
    }
}

final class OperationObserverTest extends TestCase
{
    private FilesystemFixture $fixture;
    private RecordingOperationObserver $observer;

    protected function setUp(): void
    {
        $this->fixture = new FilesystemFixture();
        $this->observer = new RecordingOperationObserver();
        $this->fixture->browser()->observeFixtureOperations($this->observer);
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

    public function testUploadAndDirectoryCreationAreObservedAfterSuccess(): void
    {
        $directory = $this->fixture->createTypeDirectory('documents');
        $temporary = $this->fixture->writeStagedFile('observed');

        self::assertSame('/report.txt', $this->fixture->browser()->moveFixtureUpload(array(
            'name' => 'report.txt',
            'tmp_name' => $temporary,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($temporary),
        ), $directory));

        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'newDir' => 'archive',
        );
        self::assertTrue($this->fixture->browser()->createObservedDirectory());

        self::assertSame('upload', $this->observer->succeeded[0]['operation']->operation);
        self::assertSame('/documents/report.txt', $this->observer->succeeded[0]['operation']->path);
        self::assertSame('create_directory', $this->observer->succeeded[1]['operation']->operation);
        self::assertSame(OperationContext::RESOURCE_DIRECTORY, $this->observer->succeeded[1]['operation']->resource);
        self::assertSame('/documents/archive', $this->observer->succeeded[1]['operation']->path);
        self::assertSame(array(), $this->observer->before);
    }

    public function testRenameAndDeleteCarryStateCapturedBeforeMutation(): void
    {
        $this->fixture->writeTypeFile('documents/old.txt', 'content');
        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'file' => 'old.txt',
            'newName' => 'new.txt',
        );
        self::assertTrue($this->fixture->browser()->renameObservedFile());

        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'file' => 'new.txt',
        );
        self::assertTrue($this->fixture->browser()->deleteFixtureFile());

        self::assertSame('/documents/old.txt', $this->observer->before[0]->path);
        self::assertSame('/documents/new.txt', $this->observer->before[0]->targetPath);
        self::assertSame('snapshot:/documents/old.txt', $this->observer->succeeded[0]['state']);
        self::assertSame('delete', $this->observer->before[1]->operation);
        self::assertSame('snapshot:/documents/new.txt', $this->observer->succeeded[1]['state']);
    }

    public function testBulkMoveAndDeleteEmitOneSuccessfulObservationPerFile(): void
    {
        $this->fixture->createTypeDirectory('source');
        $this->fixture->createTypeDirectory('target');
        $this->fixture->writeTypeFile('source/one.txt', 'one');
        $this->fixture->writeTypeFile('source/two.txt', 'two');

        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'target',
            'files' => array('files/source/one.txt', 'files/source/two.txt'),
        );
        self::assertTrue($this->fixture->browser()->moveObservedFiles());

        $_POST = array(
            'csrf_token' => 'fixture-token',
            'files' => array('files/target/one.txt', 'files/target/two.txt'),
        );
        self::assertTrue($this->fixture->browser()->deleteObservedFiles());

        self::assertSame(array('move', 'move', 'delete', 'delete'), array_map(
            static fn (array $entry): string => $entry['operation']->operation,
            $this->observer->succeeded
        ));
        self::assertSame('/source/one.txt', $this->observer->succeeded[0]['operation']->path);
        self::assertSame('/target/one.txt', $this->observer->succeeded[0]['operation']->targetPath);
    }

    public function testObserverFailuresDoNotTurnACompletedMutationIntoAnError(): void
    {
        $log = $this->fixture->root() . '/observer-errors.log';
        $previousLog = ini_get('error_log');
        ini_set('error_log', $log);
        $this->fixture->browser()->observeFixtureOperations(new FailingOperationObserver());
        $file = $this->fixture->writeTypeFile('documents/delete.txt', 'content');
        $_POST = array(
            'csrf_token' => 'fixture-token',
            'dir' => 'documents',
            'file' => 'delete.txt',
        );

        try {
            self::assertTrue($this->fixture->browser()->deleteFixtureFile());
        } finally {
            ini_set('error_log', is_string($previousLog) ? $previousLog : '');
        }

        self::assertFileDoesNotExist($file);
        self::assertStringContainsString('before listener failed', (string) file_get_contents($log));
        self::assertStringContainsString('success listener failed', (string) file_get_contents($log));
    }
}

<?php

namespace Symfony\Lsp\Tests\Server;

use Amp\ByteStream\ClosedException;
use Amp\Process\Process;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Path;

use function Amp\async;
use function Amp\ByteStream\buffer;
use function Amp\Future\await;

final class ServerExecutableTest extends TestCase
{
    public function testReportsUnhandledServerFailuresToStandardError(): void
    {
        $environment = getenv();
        $root = \dirname(__DIR__, 2);
        $process = Process::start(
            [Path::join($root, 'bin/symfony-lsp')],
            workingDirectory: $root,
            environment: [...$environment, 'SYMFONY_LSP_TREE_SITTER' => \PHP_BINARY],
            options: ['bypass_shell' => true],
        );
        $futures = [
            'stdout' => async(static fn (): string => buffer($process->getStdout())),
            'stderr' => async(static fn (): string => buffer($process->getStderr())),
            'exitCode' => async(static fn (): int => $process->join()),
        ];

        $process->getStdin()->write("Broken\r\n\r\n");
        $process->getStdin()->end();
        /** @var array{stdout: string, stderr: string, exitCode: int} $result */
        $result = await($futures);

        self::assertSame(1, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertMatchesRegularExpression(
            '{^Symfony Language Tools failed: .+ at (?:src|vendor)/.+:\d+: .+\n$}',
            $result['stderr'],
        );
        self::assertStringContainsString('A JSON-RPC message header is malformed.', $result['stderr']);
    }

    public function testReportsFatalErrorsToStandardError(): void
    {
        $environment = getenv();
        $root = \dirname(__DIR__, 2);
        $process = Process::start(
            [\PHP_BINARY, '-d', 'memory_limit=24M', Path::join($root, 'bin/symfony-lsp')],
            workingDirectory: $root,
            environment: [...$environment, 'SYMFONY_LSP_TREE_SITTER' => \PHP_BINARY],
            options: ['bypass_shell' => true],
        );
        $futures = [
            'stdout' => async(static fn (): string => buffer($process->getStdout())),
            'stderr' => async(static fn (): string => buffer($process->getStderr())),
            'exitCode' => async(static fn (): int => $process->join()),
        ];

        $body = json_encode([
            'jsonrpc' => '2.0',
            'method' => 'initialized',
            'params' => ['junk' => array_fill(0, 6_000_000, 1)],
        ], \JSON_THROW_ON_ERROR);
        try {
            $process->getStdin()->write('Content-Length: '.\strlen($body)."\r\n\r\n".$body);
            $process->getStdin()->end();
        } catch (ClosedException) {
            // The server may die of memory exhaustion before consuming all of stdin
        }
        /** @var array{stdout: string, stderr: string, exitCode: int} $result */
        $result = await($futures);

        self::assertSame(255, $result['exitCode']);
        self::assertSame('', $result['stdout']);
        self::assertStringContainsString('Allowed memory size', $result['stderr']);
    }
}

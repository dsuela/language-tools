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
            environment: $environment,
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
            [Path::join($root, 'bin/symfony-lsp')],
            workingDirectory: $root,
            environment: [...$environment, 'SYMFONY_LSP_MEMORY_LIMIT' => '24M'],
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

    public function testServesTheProtocolOverASocket(): void
    {
        $root = \dirname(__DIR__, 2);
        $listener = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        self::assertIsNotBool($listener, (string) $errorMessage);
        $address = (string) stream_socket_get_name($listener, false);
        $port = (int) substr($address, (int) strrpos($address, ':') + 1);

        $process = Process::start(
            [Path::join($root, 'bin/symfony-lsp'), '--socket='.$port],
            workingDirectory: $root,
            environment: getenv(),
            options: ['bypass_shell' => true],
        );
        $stderr = async(static fn (): string => buffer($process->getStderr()));
        $connection = stream_socket_accept($listener, 10);
        self::assertIsNotBool($connection);
        stream_set_timeout($connection, 10);

        $initialize = $this->request($connection, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
            'processId' => null,
            'rootUri' => null,
            'capabilities' => new \stdClass(),
            'initializationOptions' => ['workspaceTrust' => false],
        ]]);
        $shutdown = $this->request($connection, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'shutdown', 'params' => []]);
        $this->send($connection, ['jsonrpc' => '2.0', 'method' => 'exit', 'params' => []]);
        fclose($connection);
        fclose($listener);

        $stderrOutput = $stderr->await();
        self::assertIsString($stderrOutput);
        self::assertSame(0, $process->join(), $stderrOutput);
        self::assertSame(1, $initialize['id'] ?? null);
        $result = $initialize['result'] ?? null;
        self::assertIsArray($result);
        self::assertArrayHasKey('capabilities', $result);
        self::assertSame(2, $shutdown['id'] ?? null);
        self::assertArrayHasKey('result', $shutdown);
    }

    /**
     * @param resource             $connection
     * @param array<string, mixed> $message
     */
    private function send($connection, array $message): void
    {
        $json = json_encode($message, \JSON_THROW_ON_ERROR);
        fwrite($connection, 'Content-Length: '.\strlen($json)."\r\n\r\n".$json);
    }

    /**
     * @param resource             $connection
     * @param array<string, mixed> $message
     *
     * @return array<mixed>
     */
    private function request($connection, array $message): array
    {
        $this->send($connection, $message);
        $length = null;
        while (false !== ($line = fgets($connection))) {
            if ("\r\n" === $line) {
                break;
            }
            if (1 === preg_match('/^Content-Length: (\d+)\r\n$/i', $line, $match)) {
                $length = (int) $match[1];
            }
        }
        self::assertIsInt($length, 'The server sent an invalid response header.');
        $json = '';
        while (($missing = $length - \strlen($json)) > 0 && !feof($connection)) {
            $chunk = fread($connection, $missing);
            self::assertIsString($chunk);
            $json .= $chunk;
        }

        $decoded = json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }
}

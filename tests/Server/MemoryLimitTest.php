<?php

namespace Symfony\Lsp\Tests\Server;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Lsp\Server\MemoryLimit;

final class MemoryLimitTest extends TestCase
{
    #[DataProvider('provideResolvedLimits')]
    public function testResolvesTheLimitToApply(string $current, string|false $override, ?string $expected): void
    {
        self::assertSame($expected, (new MemoryLimit())->resolve($current, $override));
    }

    /** @return iterable<string, array{string, string|false, ?string}> */
    public static function provideResolvedLimits(): iterable
    {
        yield 'raises the bundled default' => ['128M', false, '2G'];
        yield 'raises a limit given in bytes' => ['134217728', false, '2G'];
        yield 'raises a broken empty limit' => ['', false, '2G'];
        yield 'keeps an unlimited limit' => ['-1', false, null];
        yield 'keeps a higher limit' => ['4G', false, null];
        yield 'keeps a higher limit given in megabytes' => ['3072M', false, null];
        yield 'keeps an equal limit' => ['2G', false, null];
        yield 'applies an override verbatim' => ['128M', '512M', '512M'];
        yield 'allows an override to lower the limit' => ['4G', '256M', '256M'];
        yield 'allows an unlimited override' => ['128M', '-1', '-1'];
        yield 'ignores an invalid override' => ['128M', '2GB', '2G'];
        yield 'ignores an empty override' => ['4G', '', null];
    }

    public function testRespectsACustomDefault(): void
    {
        $memoryLimit = new MemoryLimit('512M');

        self::assertSame('512M', $memoryLimit->resolve('128M', false));
        self::assertNull($memoryLimit->resolve('1G', false));
    }

    #[DataProvider('provideValidities')]
    public function testValidatesShorthandNotation(string $limit, bool $expected): void
    {
        self::assertSame($expected, (new MemoryLimit())->isValid($limit));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function provideValidities(): iterable
    {
        yield 'megabytes' => ['512M', true];
        yield 'lowercase gigabytes' => ['4g', true];
        yield 'kilobytes' => ['65536k', true];
        yield 'plain bytes' => ['1073741824', true];
        yield 'unlimited' => ['-1', true];
        yield 'unit with trailing byte suffix' => ['2GB', false];
        yield 'decimal value' => ['1.5G', false];
        yield 'empty value' => ['', false];
        yield 'words' => ['unlimited', false];
    }
}

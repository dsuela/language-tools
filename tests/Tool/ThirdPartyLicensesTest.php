<?php

namespace Symfony\Lsp\Tests\Tool;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

final class ThirdPartyLicensesTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';

    public function testDistributesProductionComposerLicenses(): void
    {
        /** @var array{packages: list<array{name: string}>} $lock */
        $lock = json_decode((string) file_get_contents(self::ROOT.'/composer.lock'), true, flags: \JSON_THROW_ON_ERROR);
        $packages = $lock['packages'];

        $expected = ['composer/LICENSE'];
        self::assertSame(
            $this->normalizedContents(self::ROOT.'/vendor/composer/LICENSE'),
            $this->normalizedContents(self::ROOT.'/THIRD_PARTY_LICENSES/php/composer/LICENSE'),
        );
        foreach ($packages as $package) {
            $relativePath = $package['name'].'/LICENSE';
            $expected[] = $relativePath;
            self::assertSame(
                $this->normalizedContents($this->packageLicense(self::ROOT.'/vendor/'.$package['name'])),
                $this->normalizedContents(self::ROOT.'/THIRD_PARTY_LICENSES/php/'.$relativePath),
                $package['name'].' has an outdated distributed license.',
            );
        }

        sort($expected);
        self::assertSame($expected, $this->licenseFiles('php'));
    }

    public function testDistributesProductionNpmLicenses(): void
    {
        /** @var array{packages: array<string, array{dev?: bool}>} $lock */
        $lock = json_decode((string) file_get_contents(self::ROOT.'/editor/vscode/package-lock.json'), true, flags: \JSON_THROW_ON_ERROR);
        $packages = $lock['packages'];

        $expected = [];
        foreach ($packages as $path => $package) {
            if (!str_starts_with($path, 'node_modules/') || ($package['dev'] ?? false)) {
                continue;
            }
            $expected[] = substr($path, \strlen('node_modules/')).'/LICENSE';
        }

        sort($expected);
        self::assertSame($expected, $this->licenseFiles('vscode'));
    }

    public function testDistributesNativeDependencyLicenses(): void
    {
        $expected = [
            'runtime/lib_libiconv_0.txt',
            'runtime/lib_zlib_0.txt',
            'runtime/phpmicro-LICENSE',
            'runtime/src_php-src_0.txt',
            'tree-sitter/tree-sitter-LICENSE',
            'tree-sitter/tree-sitter-twig-LICENSE',
            'tree-sitter/tree-sitter-yaml-LICENSE',
            'tree-sitter/unicode-LICENSE',
        ];
        self::assertSame($expected, [...$this->licenseFiles('runtime', false), ...$this->licenseFiles('tree-sitter', false)]);
        foreach ($expected as $path) {
            self::assertGreaterThan(100, (int) filesize(self::ROOT.'/THIRD_PARTY_LICENSES/'.$path));
        }
    }

    public function testReleasePackagesContainThirdPartyNotices(): void
    {
        $workflow = (string) file_get_contents(self::ROOT.'/.github/workflows/release.yaml');
        $notices = (string) file_get_contents(self::ROOT.'/THIRD_PARTY_NOTICES.md');

        self::assertStringContainsString('static-php-cli/minimal/php-8.4.20-micro-', $workflow);
        self::assertStringContainsString('| PHP 8.4.20 | PHP License 3.01 |', $notices);
        self::assertStringContainsString('static-php-cli/windows/spc-min/php-8.4.20-micro-win.zip', $workflow);
        self::assertSame(4, substr_count($workflow, 'spc_checksum:'));
        self::assertSame(4, substr_count($workflow, 'micro_checksum:'));
        self::assertStringContainsString("throw 'Invalid static-php-cli checksum.'", $workflow);
        self::assertStringContainsString("throw 'Invalid PHP micro-runtime checksum.'", $workflow);
        self::assertGreaterThanOrEqual(2, substr_count($workflow, 'THIRD_PARTY_NOTICES.md'));
        self::assertGreaterThanOrEqual(4, substr_count($workflow, 'THIRD_PARTY_LICENSES'));
    }

    /** @return list<string> */
    private function licenseFiles(string $directory, bool $relativeToDirectory = true): array
    {
        $root = self::ROOT.'/THIRD_PARTY_LICENSES/'.$directory;
        $files = [];
        foreach ((new Finder())->files()->in($root) as $file) {
            $files[] = ($relativeToDirectory ? '' : $directory.'/').$file->getRelativePathname();
        }
        sort($files);

        return $files;
    }

    private function normalizedContents(string $path): string
    {
        $contents = (string) file_get_contents($path);

        return rtrim(preg_replace('/[ \t]+$/m', '', $contents) ?? '')."\n";
    }

    private function packageLicense(string $directory): string
    {
        foreach (['LICENSE', 'LICENSE.txt', 'LICENSE.md'] as $name) {
            if (is_file($path = $directory.'/'.$name)) {
                return $path;
            }
        }

        self::fail('No license found for '.$directory);
    }
}

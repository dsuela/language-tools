<?php

namespace Symfony\Lsp\Tests\Project;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Lsp\Project\GitignoreMatcher;

final class GitignoreMatcherTest extends TestCase
{
    private string $temporaryDirectory;

    protected function setUp(): void
    {
        $this->temporaryDirectory = sys_get_temp_dir().'/symfony-lsp-'.bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->temporaryDirectory);
    }

    public function testMatchesRootGitignorePatterns(): void
    {
        file_put_contents($this->temporaryDirectory.'/.gitignore', "*.log\ntmp/\n");
        mkdir($this->temporaryDirectory.'/tmp/phpstan', 0777, true);
        $matcher = new GitignoreMatcher();

        self::assertTrue($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/debug.log'));
        self::assertTrue($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/tmp/phpstan/cache.php'));
        self::assertFalse($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/src/Controller.php'));
    }

    public function testMatchesGitignoreFilesAboveTheProjectRoot(): void
    {
        mkdir($this->temporaryDirectory.'/.git');
        mkdir($this->temporaryDirectory.'/app/tmp', 0777, true);
        file_put_contents($this->temporaryDirectory.'/.gitignore', "app/tmp/\n");
        $matcher = new GitignoreMatcher();

        self::assertTrue($matcher->isIgnored($this->temporaryDirectory.'/app', $this->temporaryDirectory.'/app/tmp/cache.php'));
        self::assertFalse($matcher->isIgnored($this->temporaryDirectory.'/app', $this->temporaryDirectory.'/app/src/Controller.php'));
    }

    public function testRefreshesResultsWhenAGitignoreFileChanges(): void
    {
        mkdir($this->temporaryDirectory.'/tmp');
        file_put_contents($this->temporaryDirectory.'/.gitignore', "tmp/\n");
        $matcher = new GitignoreMatcher();

        self::assertTrue($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/tmp/cache.php'));

        file_put_contents($this->temporaryDirectory.'/.gitignore', "*.log\n");

        self::assertFalse($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/tmp/cache.php'));
        self::assertTrue($matcher->isIgnored($this->temporaryDirectory, $this->temporaryDirectory.'/tmp/app.log'));
    }
}

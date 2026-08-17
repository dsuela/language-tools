<?php

namespace Symfony\Lsp\Tests\Tool;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

final class WorkflowComposerScriptsTest extends TestCase
{
    private const ROOT = __DIR__.'/../..';
    private const BUILT_IN_COMMANDS = ['install', 'update'];

    public function testWorkflowsOnlyInvokeDefinedComposerScripts(): void
    {
        $composer = json_decode((string) file_get_contents(self::ROOT.'/composer.json'), true, flags: \JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        self::assertIsArray($composer['scripts']);
        $defined = [...array_keys($composer['scripts']), ...self::BUILT_IN_COMMANDS];

        $found = false;
        foreach ((new Finder())->files()->in(self::ROOT.'/.github/workflows')->name('*.yaml') as $workflow) {
            preg_match_all('/composer ([a-zA-Z0-9:_-]+)/', $workflow->getContents(), $matches);
            foreach ($matches[1] as $script) {
                $found = true;
                self::assertContains($script, $defined, \sprintf('%s invokes undefined composer script "%s".', $workflow->getRelativePathname(), $script));
            }
        }
        self::assertTrue($found);
    }
}

<?php

namespace Symfony\Lsp\Tools;

final class ReleaseMetadataUpdater
{
    public function prepare(string $root, string $currentVersion, string $version, string $date): void
    {
        $updates = [];
        $changelogPath = $root.'/CHANGELOG.md';
        $changelog = $this->read($changelogPath);
        if (1 !== substr_count($changelog, '## Unreleased')) {
            throw new \RuntimeException('CHANGELOG.md must contain exactly one Unreleased section.');
        }
        if (!preg_match('/\A# Changelog\n\n## Unreleased\n\n(?<entries>.+?)\n\n## /s', $changelog, $matches)) {
            throw new \RuntimeException('The Unreleased changelog section must contain at least one entry.');
        }
        if ('' === trim($matches['entries'])) {
            throw new \RuntimeException('The Unreleased changelog section must contain at least one entry.');
        }

        $changelog = preg_replace('/^## Unreleased$/m', \sprintf('## %s (%s)', $version, $date), $changelog, 1, $count);
        if (null === $changelog || 1 !== $count) {
            throw new \RuntimeException('Unable to update CHANGELOG.md.');
        }
        $updates[$changelogPath] = $changelog;

        $documentationPath = $root.'/docs/index.rst';
        $documentation = $this->read($documentationPath);
        if (!str_contains($documentation, $currentVersion)) {
            throw new \RuntimeException(\sprintf('docs/index.rst contains no %s installation example.', $currentVersion));
        }
        $updates[$documentationPath] = str_replace($currentVersion, $version, $documentation);

        foreach ($updates as $path => $contents) {
            $this->write($path, $contents);
        }
    }

    public function startNextDevelopment(string $root): bool
    {
        $path = $root.'/CHANGELOG.md';
        $contents = $this->read($path);
        if (str_contains($contents, '## Unreleased')) {
            return false;
        }
        if (!str_starts_with($contents, "# Changelog\n\n")) {
            throw new \RuntimeException('CHANGELOG.md has an unexpected heading.');
        }

        $this->write($path, "# Changelog\n\n## Unreleased\n\n".substr($contents, \strlen("# Changelog\n\n")));

        return true;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        if (false === $contents) {
            throw new \RuntimeException(\sprintf('Unable to read %s.', $path));
        }

        return $contents;
    }

    private function write(string $path, string $contents): void
    {
        if (false === file_put_contents($path, $contents)) {
            throw new \RuntimeException(\sprintf('Unable to write %s.', $path));
        }
    }
}

<?php

namespace Symfony\Lsp\Server;

final class MemoryLimit
{
    public function __construct(
        private readonly string $default = '2G',
    ) {
    }

    public function isValid(string $limit): bool
    {
        return 1 === preg_match('/^-?\d+[kKmMgG]?$/D', $limit);
    }

    /**
     * Returns the memory limit to apply, or null to keep the current one.
     */
    public function resolve(string $current, string|false $override): ?string
    {
        if (\is_string($override) && $this->isValid($override)) {
            return $override;
        }

        $currentBytes = $this->bytes($current);
        if ($currentBytes < 0 || $currentBytes >= $this->bytes($this->default)) {
            return null;
        }

        return $this->default;
    }

    private function bytes(string $limit): int
    {
        $value = (int) $limit;

        return match (strtolower(substr($limit, -1))) {
            'k' => $value * 1024,
            'm' => $value * 1024 ** 2,
            'g' => $value * 1024 ** 3,
            default => $value,
        };
    }
}

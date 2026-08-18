<?php

namespace Symfony\Lsp\Index;

/**
 * @phpstan-import-type SourceIndexMetadata from SourceIndexStoreInterface
 */
interface SourceIndexWriterInterface
{
    /**
     * @param SourceIndexMetadata   $metadata
     * @param array<string, string> $payloads
     */
    public function add(string $relativePath, array $metadata, array $payloads): void;

    public function commit(): void;

    public function abort(): void;
}

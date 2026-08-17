<?php

// static-php-cli patch script (spc build -P): bakes the Tree-sitter extension
// into the bundled PHP runtime by injecting it into the php-src tree.

use SPC\store\FileSystem;

if ('before-php-buildconf' !== patch_point()) {
    return;
}

$source = dirname(__DIR__).'/ext/tree_sitter';
if (!is_dir($source)) {
    throw patch_point_interrupt(1, 'The Tree-sitter extension sources are missing at '.$source);
}

$target = SOURCE_PATH.'/php-src/ext/symfony_lsp_tree_sitter';
FileSystem::removeDir($target);
FileSystem::copyDir($source, $target);
// PHP_ARG_ENABLE would let --disable-all veto the extension, so the injected
// copy registers it unconditionally and statically instead.
file_put_contents($target.'/config.m4', <<<'M4'
    PHP_NEW_EXTENSION([symfony_lsp_tree_sitter], [
        symfony_lsp_tree_sitter.c
        vendor/tree-sitter/lib/lib.c
        vendor/twig/src/parser.c
        vendor/yaml/src/parser.c
        vendor/yaml/src/scanner.c
      ], [no],, [-std=c11])

    M4);
logger()->info('Injected the Symfony Language Tools Tree-sitter extension into php-src.');

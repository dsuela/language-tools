<?php

// static-php-cli patch script (spc build -P): bakes the Tree-sitter extension
// into the bundled PHP runtime by injecting it into the php-src tree.

use SPC\store\FileSystem;

// after-php-extract is the only pre-buildconf patch point that fires on
// Windows as well as on Unix.
if ('after-php-extract' !== patch_point()) {
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
// copies register it unconditionally and statically instead.
file_put_contents($target.'/config.m4', <<<'M4'
    PHP_NEW_EXTENSION([symfony_lsp_tree_sitter], [
        symfony_lsp_tree_sitter.c
        vendor/tree-sitter/lib/lib.c
        vendor/twig/src/parser.c
        vendor/yaml/src/parser.c
        vendor/yaml/src/scanner.c
      ], [no],, [-std=c11 -I@ext_srcdir@/vendor/tree-sitter/lib])

    M4);
file_put_contents($target.'/config.w32', <<<'W32'
    // Static extension cflags leak into the global CFLAGS on Windows, so no /std flag.
    EXTENSION("symfony_lsp_tree_sitter", "symfony_lsp_tree_sitter.c", false);
    ADD_SOURCES(configure_module_dirname + "/vendor/tree-sitter/lib", "lib.c", "symfony_lsp_tree_sitter");
    ADD_SOURCES(configure_module_dirname + "/vendor/twig/src", "parser.c", "symfony_lsp_tree_sitter");
    ADD_SOURCES(configure_module_dirname + "/vendor/yaml/src", "parser.c scanner.c", "symfony_lsp_tree_sitter");

    W32);
logger()->info('Injected the Symfony Language Tools Tree-sitter extension into php-src.');

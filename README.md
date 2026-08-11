# Symfony LSP

Symfony LSP adds Symfony-aware completion, hover, navigation, references,
diagnostics, code actions, rename support, and code lenses while working
alongside a general PHP language server.

It understands routing, dependency injection, Twig, translations, environment
variables, bundle configuration, Messenger, events, Security, forms,
validation, serializer metadata, AssetMapper, Stimulus, Live Components, and
Doctrine metadata.

## Installation

### Visual Studio Code

Install the self-contained Symfony Language Tools extension from the
[Visual Studio Marketplace](https://marketplace.visualstudio.com/items?itemName=symfony.language-tools):

```console
code --install-extension symfony.language-tools
```

Add `--pre-release` to install a version with a prerelease suffix. See the
[Visual Studio Code guide](docs/editors/vscode.rst) for configuration and
troubleshooting.

### Neovim

Neovim 0.12 or later can install the first-party plugin and matching server
with `vim.pack`:

```lua
vim.pack.add({ 'https://github.com/symfony/language-tools' })
require('symfony_lsp').setup()
```

Neovim 0.11.3 or later can install the plugin with lazy.nvim:

```lua
{
    'symfony/language-tools',
    config = function()
        require('symfony_lsp').setup()
    end,
}
```

See the [Neovim guide](docs/editors/neovim.rst) for workspace trust, index
commands, statuslines, custom settings and troubleshooting.

### Standalone Server

Download the archive for your platform from
[GitHub Releases](https://github.com/symfony/language-tools/releases). Extract
it and keep the `symfony-lsp` language server and
`symfony-lsp-tree-sitter` sidecar in the same directory. Verify the server
before configuring your Language Server Protocol client:

```console
./symfony-lsp --version
```

See the [standalone installation guide](docs/index.rst#installing-a-release)
for supported platforms, checksum verification and source installation.

## Requirements

Symfony LSP supports FrameworkBundle branches listed in Symfony's
[`supported_versions`](https://symfony.com/releases.json) release metadata. The
application needs PHP and Composer so the project bridge can inspect its
compiled Symfony metadata.

## Documentation

Start with the [Symfony LSP documentation](docs/index.rst) for supported
integrations, installation, editor configuration, architecture, testing, and
release procedures.

## Development

A source checkout requires PHP 8.4 or later, Composer 2, Node.js, npm, Neovim
0.11.3 or later, StyLua and a C build toolchain:

```console
composer install
composer tree-sitter:build
composer test
composer phpstan
composer cs-check
stylua --check lsp lua editor/neovim/tests
./tools/test-neovim
```

## License

Symfony LSP is available under the [MIT License](LICENSE). Distributions also
include the applicable [third-party notices](THIRD_PARTY_NOTICES.md) and license
texts.

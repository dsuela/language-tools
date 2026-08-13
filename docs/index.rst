Symfony LSP Documentation
=========================

Symfony LSP brings Symfony-specific features to your editor: completion,
hover, navigation, references, rename and diagnostics for routes, services,
templates, translations and more. It implements the Language Server Protocol,
so its Symfony features are independent of any particular editor, and it runs
alongside a general PHP language server instead of replacing it.

Setting Up Your Editor
----------------------

Start with the page for your editor. Each page covers installation,
configuration and troubleshooting, and all editors expose the same Symfony
language features:

* :doc:`VS Code </editors/vscode>`: install the Symfony Language Tools
  extension from the Marketplace. It bundles the language server, so no
  separate download is needed;
* :doc:`Neovim </editors/neovim>`: install the server with Mason or from a
  standalone release, then enable it through ``nvim-lspconfig``.

.. toctree::
    :hidden:

    editors/vscode
    editors/neovim

Any other editor with a Language Server Protocol client can run the
standalone server: see :ref:`installing-a-release` below and configure your
client to start ``symfony-lsp``.

Features
--------

Symfony LSP understands routing, dependency injection, Twig templates,
translations, environment variables, bundle configuration, Messenger, events,
security, form and validation metadata, AssetMapper, Stimulus and Doctrine.
Each integration page documents its supported declarations, references and
Language Server Protocol capabilities:

.. toctree::
    :maxdepth: 2

    features/index

Requirements
------------

The language server supports the maintained Symfony versions listed in
Symfony's `release metadata`_. Your application must have its Composer
dependencies installed and provide a PHP command compatible with its Symfony
version.

.. _installing-a-release:

Installing a Standalone Release
-------------------------------

The VS Code extension bundles the language server, so this section only
applies to other editors. Download the archive for your platform from the
GitHub release:

* ``linux-x64`` or ``linux-arm64``;
* ``macos-x64`` or ``macos-arm64``;
* ``windows-x64``.

Extract the archive and keep ``symfony-lsp`` and
``symfony-lsp-tree-sitter`` in the same directory. On Windows, both files have
an ``.exe`` suffix.

The release also contains ``SHA256SUMS``. Verify the archive checksum before
running it.

Verify the Unix executable before configuring an editor:

.. code-block:: terminal

    $ ./symfony-lsp --version

The macOS binaries aren't signed or notarized. If macOS quarantines an archive
downloaded from the release, remove the quarantine attribute
from the extracted directory after verifying where the archive came from:

.. code-block:: terminal

    $ xattr -dr com.apple.quarantine /path/to/symfony-lsp-v0.8.5-macos-arm64

Run ``./symfony-lsp`` without arguments to start the Language Server Protocol
connection over standard input and standard output.

Upgrading
~~~~~~~~~

Download the new archive for the same platform, stop the editor client and
replace both executables together. Verify the installed version, then restart
or reload the editor:

.. code-block:: terminal

    $ ./symfony-lsp --version

The first workspace initialization after an upgrade rebuilds the project
index.

Installing the Server from Source
---------------------------------

Source installations require PHP 8.4.1 or later and Composer 2. Clone this
repository outside the Symfony application that you want to edit. Install the
server dependencies and build the bundled Twig and YAML parser extension:

.. code-block:: terminal

    $ composer install
    $ composer tree-sitter:build

The development executable is ``bin/symfony-lsp``. It automatically loads the
locally built parser extension on Unix systems. Verify that it starts:

.. code-block:: terminal

    $ ./bin/symfony-lsp --version

.. _`release metadata`: https://symfony.com/releases.json

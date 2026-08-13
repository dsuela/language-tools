Symfony LSP Documentation
=========================

Symfony LSP adds Symfony-specific editor features without replacing a general
PHP language server. It implements the Language Server Protocol, so its Symfony
features are independent of any particular editor.

Requirements
------------

The standalone language server supports maintained Symfony versions listed in
Symfony's `release metadata`_. The application must have its Composer
dependencies installed and provide a PHP command compatible with its Symfony
version.

.. _installing-a-release:

Installing a Release
--------------------

Download the archive for your platform from the GitHub release:

* ``linux-x64`` or ``linux-arm64``;
* ``macos-x64`` or ``macos-arm64``;
* ``windows-x64``.

Extract the archive and keep ``symfony-lsp`` and
``symfony-lsp-tree-sitter`` in the same directory. On Windows, both files have
an ``.exe`` suffix.

Verify the Unix executable before configuring an editor:

.. code-block:: terminal

    $ ./symfony-lsp --version

The macOS binaries aren't signed or notarized. If macOS quarantines an archive
downloaded from the release, remove the quarantine attribute
from the extracted directory after verifying where the archive came from:

.. code-block:: terminal

    $ xattr -dr com.apple.quarantine /path/to/symfony-lsp-v0.8.5-macos-arm64

The release also contains ``SHA256SUMS``. Verify the archive checksum before
running it.

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

Run ``./bin/symfony-lsp`` without arguments to start the Language Server
Protocol connection over standard input and standard output.

Upgrading
---------

Download the new archive for the same platform, stop the editor client and
replace both executables together. Verify the installed version, then restart
or reload the editor:

.. code-block:: terminal

    $ ./symfony-lsp --version

The first workspace initialization after an upgrade rebuilds the project
index.

.. _`release metadata`: https://symfony.com/releases.json

Features
--------

Each Symfony integration documents its supported declarations, references and
Language Server Protocol capabilities:

.. toctree::
    :maxdepth: 2

    features/index

Editor Integrations
-------------------

Editor pages cover installation, configuration and troubleshooting. All
editors expose the same Symfony language features.

.. toctree::
    :maxdepth: 1

    editors/vscode
    editors/neovim

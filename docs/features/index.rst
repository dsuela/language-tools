Symfony Integrations
====================

Symfony Language Tools combines information from project files with metadata
from the selected Symfony environment. Features based on project files remain
available without running the application. Features that depend on the compiled
container or another runtime service require runtime indexing.

Supported Integrations
----------------------

.. list-table::
    :header-rows: 1

    * - Integration
      - Completion
      - Hover
      - Definition
      - References
      - Rename
      - Diagnostics
    * - :doc:`Routing </features/routing>`
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
    * - :doc:`Dependency injection </features/dependency-injection>`
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
    * - :doc:`Twig template names </features/templates>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Translations </features/translations>`
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
      - Yes
    * - :doc:`Environment variables </features/environment>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Bundle configuration </features/configuration>`
      - Yes
      - Yes
      - No
      - No
      - No
      - Yes
    * - :doc:`Messenger </features/messenger>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Events </features/events>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Security </features/security>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Forms, validation and serializer metadata </features/metadata>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`AssetMapper and importmaps </features/assets>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Stimulus and Live Components </features/stimulus>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - Yes
    * - :doc:`Doctrine entities and repositories </features/doctrine>`
      - Yes
      - Yes
      - Yes
      - Yes
      - No
      - No

Messenger message and handler classes, event and listener classes, Stimulus
controllers and Doctrine entity and repository classes also provide code lenses
for navigating between related declarations.

Workspace Trust
---------------

Runtime indexing boots ``App\\Kernel`` and executes application code. It is
available only in debug mode and for workspaces that you trust. Do not enable it
for a project whose code you would not run from the command line.

Without runtime indexing, Symfony Language Tools continues to provide features
derived from project files. Suggestions that depend on the effective router,
compiled container or another runtime service may be incomplete. Diagnostics are
suppressed when the server cannot prove that the available metadata is complete.

Unsaved Files and Refreshes
---------------------------

Navigation, references, rename and diagnostics derived from project files
reflect unsaved changes. Runtime metadata is refreshed after relevant files are
saved. If a refresh fails, the last valid metadata remains available and the
editor reports that the runtime index is stale.

Use the editor's index commands to refresh project data, inspect the current
status or switch the selected Symfony environment. Language Server Protocol
clients can invoke the corresponding commands directly:

* ``symfony.refreshIndex``;
* ``symfony.indexStatus``;
* ``symfony.switchEnvironment``.

Privacy
-------

Symfony Language Tools uses names, types, relationships and other structural
metadata to provide editor features. Parameter values, environment values,
credentials and application objects are never included in indexes, logs, hover
output, diagnostics or protocol traces. Protocol tracing is disabled by default
and redacts values when enabled.

Current Limitations
-------------------

The current version has these general limitations:

* only ``App\\Kernel`` is discovered;
* one Symfony environment is active at a time for each application root;
* references and rename cover only statically recognized values.

See each integration page for its supported contexts and specific limitations.

Troubleshooting
---------------

If a runtime-backed feature returns no results, verify that:

* the workspace root contains ``composer.json``;
* ``composer.json`` requires ``symfony/framework-bundle``;
* ``vendor/autoload.php`` exists;
* ``App\\Kernel`` boots in the configured environment;
* the configured PHP command is compatible with the application;
* runtime indexing is enabled and the workspace is trusted.

.. toctree::
    :hidden:

    routing
    dependency-injection
    templates
    translations
    environment
    configuration
    messenger
    events
    security
    metadata
    assets
    stimulus
    doctrine

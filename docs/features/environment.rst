Environment Variables
=====================

Symfony LSP recognizes environment variable declarations from ``.env`` files
and references from ``%env(...)%`` expressions. It uses names, locations,
processor chains and default availability without storing or returning values.

Completion
----------

Completion is available inside ``%env(...)%`` expressions in PHP, YAML and
Twig files. It suggests environment variable names and processors installed in
the selected Symfony environment.

For example, completion after ``json:`` suggests known variable names:

.. code-block:: yaml

    # config/services.yaml
    parameters:
        app.payload: '%env(json:APP_PAYLOAD)%'

Hover and Navigation
--------------------

Hover shows the variable name, declaration files, processor chain, expected
type and whether a declaration has a default. Go to Definition navigates to the
matching declaration in a ``.env`` file. Find All References includes
recognized expressions in PHP, YAML, Twig and other ``.env`` files.

Diagnostics
-----------

Diagnostics report unknown processors, malformed processor chains and
processor result types that are incompatible with a statically known bundle
configuration type. A missing declaration isn't an error because the variable
can be supplied by the shell, a deployment platform or a secrets provider.

Privacy
-------

Environment values aren't included in indexes, hover output, logs or Language
Server Protocol responses.

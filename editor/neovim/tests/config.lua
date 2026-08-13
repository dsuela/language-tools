local repo = vim.fn.getcwd()
vim.opt.runtimepath:prepend(repo .. '/editor/neovim')

local config = vim.lsp.config.symfony_lsp
assert(vim.deep_equal({ 'symfony-lsp' }, config.cmd))
assert(vim.deep_equal({ 'composer.json', '.git' }, config.root_markers))
assert(config.workspace_required)
assert(config.capabilities.workspace.didChangeWatchedFiles.dynamicRegistration)
assert(type(config.commands['editor.action.showReferences']) == 'function')

print('Neovim configuration tests passed')
vim.cmd.qall({ bang = true })

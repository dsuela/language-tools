import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const extensionRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const repositoryRoot = path.resolve(extensionRoot, '..', '..');
const licenses = 'THIRD_PARTY_LICENSES';
const notices = 'THIRD_PARTY_NOTICES.md';

await fs.rm(path.join(extensionRoot, licenses), { recursive: true, force: true });
await fs.cp(path.join(repositoryRoot, licenses), path.join(extensionRoot, licenses), { recursive: true });
await fs.copyFile(path.join(repositoryRoot, notices), path.join(extensionRoot, notices));

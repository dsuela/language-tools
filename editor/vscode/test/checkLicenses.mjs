import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const extensionRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const repositoryRoot = path.resolve(extensionRoot, '..', '..');
const lock = JSON.parse(await fs.readFile(path.join(extensionRoot, 'package-lock.json'), 'utf8'));
const packages = Object.entries(lock.packages)
    .filter(([packagePath, metadata]) => packagePath.startsWith('node_modules/') && !metadata.dev)
    .map(([packagePath]) => packagePath.slice('node_modules/'.length))
    .sort();
const licenseRoot = path.join(repositoryRoot, 'THIRD_PARTY_LICENSES', 'vscode');
const distributedPackages = [];
const normalize = (contents) => `${contents.replace(/[ \t]+$/gm, '').trimEnd()}\n`;

async function findDistributedPackages(directory, prefix = '') {
    for (const entry of await fs.readdir(directory, { withFileTypes: true })) {
        const relativePath = path.join(prefix, entry.name);
        if (entry.isDirectory()) {
            await findDistributedPackages(path.join(directory, entry.name), relativePath);
        } else if ('LICENSE' === entry.name) {
            distributedPackages.push(prefix);
        }
    }
}

await findDistributedPackages(licenseRoot);
assert.deepEqual(distributedPackages.sort(), packages);

for (const packageName of packages) {
    const packageDirectory = path.join(extensionRoot, 'node_modules', packageName);
    const licenseName = (await fs.readdir(packageDirectory)).find((name) => /^licen[cs]e(?:\..+)?$/i.test(name));
    assert.ok(licenseName, `${packageName} does not provide a license file`);

    const upstream = await fs.readFile(path.join(packageDirectory, licenseName), 'utf8');
    const distributed = await fs.readFile(path.join(licenseRoot, packageName, 'LICENSE'), 'utf8');
    assert.equal(normalize(distributed), normalize(upstream), `${packageName} has an outdated distributed license`);
}

console.log(`Verified licenses for ${packages.length} production npm packages.`);

#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const guide = path.join(root, 'docs/vscode-guide');
const reference = await fs.readFile(path.join(root, 'docs/features/index.rst'), 'utf8');
const catalog = await fs.readFile(path.join(guide, 'features.html'), 'utf8');
const gettingStarted = await fs.readFile(path.join(guide, 'index.html'), 'utf8');
const stylesheet = await fs.readFile(path.join(guide, 'guide.css'), 'utf8');
const captureScript = await fs.readFile(path.join(root, 'tools/capture-vscode-guide'), 'utf8');
const tourScript = await fs.readFile(path.join(root, 'tools/generate-vscode-guide-tour'), 'utf8');

const referenceSection = reference.split('Supported Integrations\n----------------------')[1]?.split('Runtime Indexing and Trust')[0];
if (!referenceSection) {
    throw new Error('Unable to find the supported integration matrix');
}

const referenceRows = [...referenceSection.matchAll(/^    \* - (.+)\n((?:      - (?:Yes|No)\n){6})/gm)].map((match) => ({
    name: match[1].replace(/:doc:`(.+?)(?: <.+?>)?`/g, '$1'),
    support: [...match[2].matchAll(/^      - (Yes|No)$/gm)].map((cell) => 'Yes' === cell[1]),
}));
const catalogTable = catalog.match(/<table class="coverage-table">([\s\S]+?)<\/table>/)?.[1];
if (!catalogTable) {
    throw new Error('Unable to find the visual coverage matrix');
}

const catalogRows = [...catalogTable.matchAll(/<tr><td>(.+?)<\/td>([\s\S]+?)<\/tr>/g)].map((match) => ({
    name: match[1],
    support: [...match[2].matchAll(/class="(coverage-yes|coverage-no)"/g)].map((cell) => 'coverage-yes' === cell[1]),
}));
if (13 !== referenceRows.length || referenceRows.length !== catalogRows.length) {
    throw new Error(`Expected 13 matching integration rows, found ${referenceRows.length} reference and ${catalogRows.length} visual rows`);
}

for (const [index, row] of referenceRows.entries()) {
    const visual = catalogRows[index];
    if (
        row.name !== visual.name
        || 6 !== visual.support.length
        || row.support.join(',') !== visual.support.join(',')
    ) {
        throw new Error(`Visual coverage differs from the reference matrix at ${visual.name}`);
    }
}

const supported = catalogRows.flatMap((row) => row.support).filter(Boolean).length;
if (65 !== supported) {
    throw new Error(`Expected 65 supported visual combinations, found ${supported}`);
}

const tourSlides = [...tourScript.matchAll(/^    "([a-z-]+)\|/gm)].map((match) => `${match[1]}.webp`);
const duplicateSlides = tourSlides.filter((slide, index) => tourSlides.indexOf(slide) !== index);
if (0 < duplicateSlides.length) {
    throw new Error(`Duplicate tour slides: ${duplicateSlides.join(', ')}`);
}
if (!catalog.includes('<source src="images/tour.mp4" type="video/mp4">')) {
    throw new Error('The visual catalog must embed the video tour');
}

const referencedImages = new Set([
    ...[...`${gettingStarted}\n${catalog}`.matchAll(/(?:src|href)="images\/(.+?\.webp)"/g)].map((match) => match[1]),
    ...tourSlides,
]);
const imageDirectory = path.join(guide, 'images');
const imageFiles = (await fs.readdir(imageDirectory)).filter((file) => file.endsWith('.webp')).sort();
const missing = [...referencedImages].filter((file) => !imageFiles.includes(file));
const unused = imageFiles.filter((file) => !referencedImages.has(file));
if (0 < missing.length || 0 < unused.length) {
    throw new Error(`Image mismatch. Missing: ${missing.join(', ') || 'none'}. Unused: ${unused.join(', ') || 'none'}`);
}
if (30 !== imageFiles.length) {
    throw new Error(`Expected 30 visual captures, found ${imageFiles.length}`);
}
const missingSlides = imageFiles.filter((file) => 'install-extension.webp' !== file && !tourSlides.includes(file));
if (0 < missingSlides.length) {
    throw new Error(`Captures missing from the video tour: ${missingSlides.join(', ')}`);
}

const captureTargets = ['install', 'demo', 'runtime'].flatMap((group) => {
    const targets = captureScript.match(new RegExp(`^${group}_targets=\\(([^)]+)\\)$`, 'm'))?.[1];
    if (!targets) {
        throw new Error(`Unable to find the ${group} screenshot targets`);
    }

    return targets.split(/\s+/);
});
const duplicateTargets = captureTargets.filter((target, index) => captureTargets.indexOf(target) !== index);
const targetImages = captureTargets.map((target) => `${target}.webp`).sort();
if (0 < duplicateTargets.length || targetImages.join(',') !== imageFiles.join(',')) {
    throw new Error(`Capture targets differ from visual guide images. Duplicates: ${duplicateTargets.join(', ') || 'none'}`);
}
if (!gettingStarted.includes('<img src="images/install-extension.webp" width="1440" height="480"')) {
    throw new Error('The installation screenshot must use its compact dimensions');
}

if (!stylesheet.match(/\.tour-video\s*\{[^}]*width: min\(100%, 960px\)/)) {
    throw new Error('.tour-video must keep the tour readable within the page width');
}

console.log(`Visual guide covers ${referenceRows.length} integrations, ${supported} supported combinations, ${imageFiles.length} captures and ${tourSlides.length} tour slides.`);

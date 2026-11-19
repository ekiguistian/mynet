import { readFile, writeFile } from 'node:fs/promises';

const bundlePath = 'public/assets/js/app.bundle.js';
const bundle = await readFile(bundlePath, 'utf8');
await writeFile(bundlePath, bundle.replace(/^[\t ]+$/gm, ''), 'utf8');

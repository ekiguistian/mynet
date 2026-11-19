import { readFile, writeFile } from 'node:fs/promises';

const cssPath = 'public/assets/css/tailwind.css';
const css = await readFile(cssPath, 'utf8');
await writeFile(cssPath, css.endsWith('\n') ? css : `${css}\n`, 'utf8');

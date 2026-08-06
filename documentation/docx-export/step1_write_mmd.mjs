import fs from 'fs';
import { allDiagrams } from './diagrams.mjs';

if (!fs.existsSync('images')) {
  fs.mkdirSync('images');
}

allDiagrams.forEach((diagram, index) => {
  const mmdPath = `images/diagram_${index}.mmd`;
  // Write .mmd
  fs.writeFileSync(mmdPath, diagram.code);
});
console.log('Created .mmd files');

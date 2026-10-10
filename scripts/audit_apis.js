const fs = require('fs');
const path = require('path');

const routesDir = path.join(__dirname, '..', 'server', 'routes');
const files = fs.readdirSync(routesDir).filter(f => f.endsWith('.js'));

console.log('=== EXACT REST ENDPOINT AUDIT ===\n');

let allEndpoints = [];

files.forEach(file => {
  const filePath = path.join(routesDir, file);
  const content = fs.readFileSync(filePath, 'utf8');
  
  // Match router.get, router.post, router.put, router.delete, router.patch
  const regex = /router\.(get|post|put|delete|patch)\(\s*['"`]([^'"`]+)['"`]/g;
  let match;
  let fileEndpoints = [];
  
  while ((match = regex.exec(content)) !== null) {
    fileEndpoints.push({
      method: match[1].toUpperCase(),
      path: match[2],
      file: file
    });
  }
  
  allEndpoints.push(...fileEndpoints);
  console.log(`${file}: ${fileEndpoints.length} endpoints`);
});

// Also check server/index.js
const indexPath = path.join(__dirname, '..', 'server', 'index.js');
const indexContent = fs.readFileSync(indexPath, 'utf8');
const indexRegex = /app\.(get|post|put|delete|patch)\(\s*['"`]([^'"`]+)['"`]/g;
let idxMatch;
let indexEndpoints = [];
while ((idxMatch = indexRegex.exec(indexContent)) !== null) {
  indexEndpoints.push({
    method: idxMatch[1].toUpperCase(),
    path: idxMatch[2],
    file: 'index.js'
  });
}
allEndpoints.push(...indexEndpoints);
console.log(`index.js: ${indexEndpoints.length} endpoints`);

console.log(`\nTOTAL ACTUAL REST ENDPOINTS: ${allEndpoints.length}\n`);

allEndpoints.sort((a, b) => (a.file + a.path).localeCompare(b.file + b.path));
allEndpoints.forEach((ep, i) => {
  console.log(`${i + 1}. [${ep.method}] ${ep.path} (${ep.file})`);
});

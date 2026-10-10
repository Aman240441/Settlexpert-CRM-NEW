const Database = require('better-sqlite3');
const path = require('path');

const dbPath = path.join(__dirname, '..', 'server', 'data', 'settl_expert.db');
const db = new Database(dbPath, { readonly: true });

console.log('=== REAL DATABASE AUDIT (settl_expert.db) ===\n');

const tables = db.prepare("SELECT name, sql FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name").all();
console.log('ACTUAL DATABASE TABLE COUNT:', tables.length);

tables.forEach((t, i) => {
  const colCount = (t.sql.match(/\n\s+[a-zA-Z0-9_]+/g) || []).length;
  console.log(`${i + 1}. ${t.name}`);
});

console.log('\n--- DETAILED SCHEMA EXTRACT ---');
tables.forEach(t => {
  console.log(`\nTable: ${t.name}`);
  console.log(t.sql);
});

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

function escapeHtml(text) {
  return text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

function parseMarkdown(md) {
  const lines = md.split(/\r?\n/);
  let html = [];
  let inCode = false;
  let codeLang = '';
  let codeBuffer = [];
  let inTable = false;
  let tableHeaderParsed = false;
  let inList = false;

  for (let i = 0; i < lines.length; i++) {
    let line = lines[i];

    // Code blocks
    if (line.startsWith('```')) {
      if (inCode) {
        html.push(`<pre><code class="language-${codeLang}">${escapeHtml(codeBuffer.join('\n'))}</code></pre>`);
        codeBuffer = [];
        inCode = false;
      } else {
        if (inList) { html.push('</ul>'); inList = false; }
        if (inTable) { html.push('</tbody></table></div>'); inTable = false; tableHeaderParsed = false; }
        inCode = true;
        codeLang = line.replace('```', '').trim();
      }
      continue;
    }

    if (inCode) {
      codeBuffer.push(line);
      continue;
    }

    // Tables
    if (line.trim().startsWith('|') && line.trim().endsWith('|')) {
      if (inList) { html.push('</ul>'); inList = false; }
      const cells = line.split('|').map(c => c.trim()).slice(1, -1);
      
      // Check if separator line
      if (cells.every(c => /^:?-+:?$/.test(c))) {
        tableHeaderParsed = true;
        continue;
      }

      if (!inTable) {
        inTable = true;
        tableHeaderParsed = false;
        html.push('<div class="table-container"><table>');
        html.push('<thead><tr>' + cells.map(c => `<th>${formatInline(c)}</th>`).join('') + '</tr></thead><tbody>');
      } else {
        html.push('<tr>' + cells.map(c => `<td>${formatInline(c)}</td>`).join('') + '</tr>');
      }
      continue;
    } else if (inTable) {
      html.push('</tbody></table></div>');
      inTable = false;
      tableHeaderParsed = false;
    }

    // Lists
    if (/^\s*[-*]\s+/.test(line)) {
      if (!inList) {
        inList = true;
        html.push('<ul>');
      }
      const itemContent = line.replace(/^\s*[-*]\s+/, '');
      html.push(`<li>${formatInline(itemContent)}</li>`);
      continue;
    } else if (inList && !/^\s*[-*]\s+/.test(line) && line.trim() !== '') {
      // Check if numbered list
      if (/^\s*\d+\.\s+/.test(line)) {
        html.push('</ul>');
        inList = false;
      }
    }

    if (inList && line.trim() === '') {
      html.push('</ul>');
      inList = false;
      continue;
    }

    // Numbered lists
    if (/^\d+\.\s+/.test(line)) {
      const itemContent = line.replace(/^\d+\.\s+/, '');
      html.push(`<div class="list-num-item"><span class="num-bullet">${line.match(/^\d+\./)[0]}</span> ${formatInline(itemContent)}</div>`);
      continue;
    }

    // Blockquote
    if (line.startsWith('>')) {
      html.push(`<blockquote>${formatInline(line.replace(/^>\s*/, ''))}</blockquote>`);
      continue;
    }

    // Headers
    if (line.startsWith('# ')) {
      html.push(`<h1>${formatInline(line.substring(2))}</h1>`);
      continue;
    }
    if (line.startsWith('## ')) {
      html.push(`<h2>${formatInline(line.substring(3))}</h2>`);
      continue;
    }
    if (line.startsWith('### ')) {
      html.push(`<h3>${formatInline(line.substring(4))}</h3>`);
      continue;
    }
    if (line.startsWith('#### ')) {
      html.push(`<h4>${formatInline(line.substring(5))}</h4>`);
      continue;
    }

    // Horizontal Rule
    if (line.trim() === '---') {
      html.push('<hr />');
      continue;
    }

    // Normal paragraph
    if (line.trim().length > 0) {
      html.push(`<p>${formatInline(line)}</p>`);
    }
  }

  if (inTable) html.push('</tbody></table></div>');
  if (inList) html.push('</ul>');
  if (inCode) html.push(`<pre><code>${escapeHtml(codeBuffer.join('\n'))}</code></pre>`);

  return html.join('\n');
}

function formatInline(str) {
  return str
    .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
    .replace(/\*(.*?)\*/g, '<em>$1</em>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2">$1</a>');
}

const mdPath = path.join(__dirname, '..', 'SETTLEXPERT_MASTER_BLUEPRINT.md');
const mdContent = fs.readFileSync(mdPath, 'utf8');
const bodyHtml = parseMarkdown(mdContent);

const fullHtml = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SETTLEXPERT CRM — Master Technical Blueprint & System Specification</title>
  <style>
    @page {
      size: A4;
      margin: 16mm 14mm 16mm 14mm;
      @bottom-right {
        content: counter(page);
      }
    }
    body {
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      font-size: 11pt;
      line-height: 1.55;
      color: #1e293b;
      background: #ffffff;
      padding: 0;
      margin: 0;
    }
    h1 {
      font-size: 20pt;
      color: #0f172a;
      border-bottom: 3px solid #3b82f6;
      padding-bottom: 8px;
      margin-top: 24px;
      margin-bottom: 16px;
      page-break-after: avoid;
    }
    h2 {
      font-size: 14pt;
      color: #1d4ed8;
      border-bottom: 1.5px solid #e2e8f0;
      padding-bottom: 6px;
      margin-top: 28px;
      margin-bottom: 12px;
      page-break-after: avoid;
    }
    h3 {
      font-size: 12pt;
      color: #0f172a;
      margin-top: 18px;
      margin-bottom: 8px;
      page-break-after: avoid;
    }
    h4 {
      font-size: 11pt;
      color: #334155;
      margin-top: 14px;
      margin-bottom: 6px;
      page-break-after: avoid;
    }
    p {
      margin-top: 4px;
      margin-bottom: 8px;
      text-align: justify;
    }
    blockquote {
      border-left: 4px solid #3b82f6;
      background-color: #f8fafc;
      padding: 8px 14px;
      margin: 10px 0;
      color: #475569;
      font-size: 10pt;
      border-radius: 0 4px 4px 0;
    }
    .table-container {
      margin: 12px 0;
      page-break-inside: auto;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 9.5pt;
      margin-bottom: 12px;
      page-break-inside: auto;
    }
    tr {
      page-break-inside: avoid;
      page-break-after: auto;
    }
    th {
      background-color: #f1f5f9;
      color: #0f172a;
      font-weight: 600;
      text-align: left;
      padding: 7px 9px;
      border: 1px solid #cbd5e1;
    }
    td {
      padding: 6px 9px;
      border: 1px solid #e2e8f0;
      vertical-align: top;
    }
    tr:nth-child(even) td {
      background-color: #f8fafc;
    }
    code {
      font-family: Consolas, Monaco, "Courier New", monospace;
      font-size: 9pt;
      background-color: #f1f5f9;
      color: #0969da;
      padding: 2px 4px;
      border-radius: 3px;
    }
    pre {
      background-color: #0f172a;
      color: #f8fafc;
      padding: 12px;
      border-radius: 6px;
      overflow-x: auto;
      font-size: 8.5pt;
      line-height: 1.4;
      page-break-inside: avoid;
      margin: 10px 0;
    }
    pre code {
      background-color: transparent;
      color: #f8fafc;
      padding: 0;
    }
    ul {
      margin-top: 4px;
      margin-bottom: 10px;
      padding-left: 20px;
    }
    li {
      margin-bottom: 4px;
    }
    .list-num-item {
      margin-bottom: 4px;
      padding-left: 4px;
    }
    .num-bullet {
      font-weight: bold;
      color: #2563eb;
    }
    hr {
      border: 0;
      border-top: 1px solid #cbd5e1;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  ${bodyHtml}
</body>
</html>`;

const outHtmlPath = path.join(__dirname, '..', 'SETTLEXPERT_MASTER_BLUEPRINT.html');
fs.writeFileSync(outHtmlPath, fullHtml, 'utf8');
console.log('Generated HTML at:', outHtmlPath);

const edgePath = 'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe';
const outPdfPath = path.join(__dirname, '..', 'SETTLEXPERT_MASTER_BLUEPRINT.pdf');

if (fs.existsSync(edgePath)) {
  console.log('Compiling PDF with Microsoft Edge...');
  const cmd = `"${edgePath}" --headless --disable-gpu --no-pdf-header-footer --print-to-pdf="${outPdfPath}" "${outHtmlPath}"`;
  execSync(cmd);
  console.log('Successfully generated PDF at:', outPdfPath);
} else {
  console.log('Edge executable not found for PDF export.');
}

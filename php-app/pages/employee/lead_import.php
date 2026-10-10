<?php
// php-app/pages/employee/lead_import.php
require_once __DIR__ . '/layout.php';

render_employee_layout('Lead Import & Distribution', 'lead_import', function() {
?>
<div class="max-w-4xl mx-auto space-y-6">
  <!-- Upload Box -->
  <div class="bg-white p-8 rounded-xl border border-slate-200 shadow-xs text-center space-y-4">
    <div class="w-14 h-14 bg-indigo-50 text-indigo-600 rounded-full flex items-center justify-center mx-auto">
      <i data-lucide="file-spreadsheet" class="w-7 h-7"></i>
    </div>
    <div>
      <h3 class="text-base font-bold text-slate-800">Upload Leads Spreadsheet</h3>
      <p class="text-xs text-slate-500 mt-1">Upload CSV or XLSX file containing customer leads.</p>
      <p class="text-[11px] text-slate-400 mt-0.5">Required columns: <b>Name</b>, <b>Phone</b>. Optional: <b>Total Outstanding Amount</b>, <b>Bank Name</b>, <b>City</b>.</p>
    </div>

    <form id="uploadForm" onsubmit="handleUpload(event)" class="max-w-md mx-auto space-y-4">
      <div class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-xl p-6 transition cursor-pointer bg-slate-50" onclick="document.getElementById('fileInput').click()">
        <input type="file" id="fileInput" name="file" accept=".csv,.xlsx,.xls" required class="hidden" onchange="updateFileLabel(this)">
        <p class="text-xs font-semibold text-slate-700" id="fileLabel">Click to browse file</p>
        <p class="text-[11px] text-slate-400 mt-1">Supports CSV, XLSX up to 10MB</p>
      </div>

      <button type="submit" id="submitBtn" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-lg text-xs transition shadow-xs flex items-center justify-center gap-2">
        <i data-lucide="upload-cloud" class="w-4 h-4"></i>
        <span>Upload & Distribute Leads</span>
      </button>
    </form>
  </div>

  <!-- Results Summary Card -->
  <div id="resultsCard" class="hidden bg-white p-6 rounded-xl border border-slate-200 shadow-xs space-y-4">
    <div class="flex items-center justify-between border-b pb-3">
      <h4 class="font-bold text-slate-800 text-sm">Import Results</h4>
      <span class="text-xs font-bold text-indigo-600" id="resImpNumber">IMP-0000</span>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-center">
      <div class="p-3 bg-slate-50 rounded-lg border">
        <p class="text-[11px] font-semibold text-slate-500 uppercase">Total Rows</p>
        <p class="text-xl font-bold text-slate-800 mt-1" id="resTotal">0</p>
      </div>
      <div class="p-3 bg-blue-50 rounded-lg border border-blue-100">
        <p class="text-[11px] font-semibold text-blue-700 uppercase">Valid Leads</p>
        <p class="text-xl font-bold text-blue-800 mt-1" id="resValid">0</p>
      </div>
      <div class="p-3 bg-amber-50 rounded-lg border border-amber-100">
        <p class="text-[11px] font-semibold text-amber-700 uppercase">Duplicates</p>
        <p class="text-xl font-bold text-amber-800 mt-1" id="resDupes">0</p>
      </div>
      <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-100">
        <p class="text-[11px] font-semibold text-emerald-700 uppercase">Imported</p>
        <p class="text-xl font-bold text-emerald-800 mt-1" id="resImported">0</p>
      </div>
    </div>
    <div class="pt-2 text-right">
      <a href="leads.php" class="inline-flex items-center gap-1.5 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold">
        View In Leads Pipeline &rarr;
      </a>
    </div>
  </div>
</div>

<script>
function updateFileLabel(input) {
  if (input.files && input.files[0]) {
    document.getElementById('fileLabel').textContent = 'Selected: ' + input.files[0].name;
  }
}

async function handleUpload(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const fileInput = document.getElementById('fileInput');
  if (!fileInput.files[0]) return;

  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = 'Processing & Distributing Leads...';

  const formData = new FormData();
  formData.append('file', fileInput.files[0]);

  try {
    const res = await fetch('../../api/employee/import_leads.php', {
      method: 'POST',
      headers: { 'Authorization': `Bearer ${token}` },
      body: formData
    });

    const d = await res.json();
    if (res.ok) {
      document.getElementById('resultsCard').classList.remove('hidden');
      document.getElementById('resImpNumber').textContent = d.import_number;
      document.getElementById('resTotal').textContent = d.total_rows;
      document.getElementById('resValid').textContent = d.valid_leads;
      document.getElementById('resDupes').textContent = d.duplicate_leads;
      document.getElementById('resImported').textContent = d.imported_leads;
      alert(`Success: ${d.imported_leads} leads imported and distributed!`);
    } else {
      alert(d.error || 'Import failed');
    }
  } catch(err) {
    alert('Failed to connect to import API');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload & Distribute Leads';
    lucide.createIcons();
  }
}
</script>
<?php
});

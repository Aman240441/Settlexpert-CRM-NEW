<?php
// php-app/pages/employee/dashboard.php
require_once __DIR__ . '/layout.php';

render_employee_layout('Employee Dashboard', 'dashboard', function() {
?>
<div class="max-w-7xl mx-auto space-y-6">
  <!-- Top Stat Cards Grid -->
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Leads</span>
        <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="layers" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="statLeads">0</span>
        <span class="text-xs text-emerald-600 font-medium">Assigned</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Clients</span>
        <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="user-check" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="statClients">0</span>
        <span class="text-xs text-slate-500" id="statConversion">0% conv.</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">This Month Collection</span>
        <span class="p-2 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="banknote" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="statCollection">₹0</span>
        <span class="text-xs text-slate-400">Verified</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Fees</span>
        <span class="p-2 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="clock" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-amber-600" id="statPending">₹0</span>
        <span class="text-xs text-slate-400">Expected</span>
      </div>
    </div>
  </div>

  <!-- Lead Status Pipeline Overview -->
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-semibold text-slate-800">Lead Pipeline Breakdown</h3>
        <p class="text-xs text-slate-500">Live counts across your assigned lead stages</p>
      </div>
      <a href="leads.php" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        View All Leads &rarr;
      </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-7 gap-3 text-center" id="pipelineContainer">
      <!-- Injected via JS -->
    </div>
  </div>

  <!-- Quick Action Row -->
  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
    <div class="bg-gradient-to-br from-indigo-900 to-slate-900 text-white p-6 rounded-xl shadow-xs flex flex-col justify-between">
      <div>
        <span class="text-xs font-semibold text-indigo-300 uppercase tracking-wide">Quick Lead Entry</span>
        <h4 class="text-lg font-bold mt-1">Add a Walk-in or Calling Lead</h4>
        <p class="text-xs text-slate-300 mt-2">Instantly capture debt, salary, credit card dues and assign follow-up schedule.</p>
      </div>
      <div class="mt-5">
        <a href="leads.php?action=new" class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-semibold transition">
          <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add New Lead
        </a>
      </div>
    </div>

    <div class="bg-gradient-to-br from-slate-900 to-indigo-950 text-white p-6 rounded-xl shadow-xs flex flex-col justify-between">
      <div>
        <span class="text-xs font-semibold text-emerald-300 uppercase tracking-wide">Bulk Operations</span>
        <h4 class="text-lg font-bold mt-1">Import Leads from Excel / CSV</h4>
        <p class="text-xs text-slate-300 mt-2">Upload multiple leads with phone normalization, duplicate checks, and round-robin distribution.</p>
      </div>
      <div class="mt-5">
        <a href="lead_import.php" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-semibold transition">
          <i data-lucide="upload" class="w-3.5 h-3.5"></i> Import Lead Sheet
        </a>
      </div>
    </div>
  </div>
</div>

<script>
async function loadDashboard() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/employee/dashboard.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    if (!res.ok) return;
    const d = await res.json();

    document.getElementById('statLeads').textContent = d.total_leads;
    document.getElementById('statClients').textContent = d.active_clients;
    document.getElementById('statConversion').textContent = d.conversion_rate + '% conv.';
    document.getElementById('statCollection').textContent = '₹' + d.collection.toLocaleString('en-IN');
    document.getElementById('statPending').textContent = '₹' + d.pending.toLocaleString('en-IN');

    const pipeEl = document.getElementById('pipelineContainer');
    pipeEl.innerHTML = '';
    const stages = [
      { key: 'new', label: 'New', color: 'bg-blue-50 text-blue-700 border-blue-200' },
      { key: 'contacted', label: 'Contacted', color: 'bg-amber-50 text-amber-700 border-amber-200' },
      { key: 'interested', label: 'Interested', color: 'bg-indigo-50 text-indigo-700 border-indigo-200' },
      { key: 'in_progress', label: 'In Progress', color: 'bg-purple-50 text-purple-700 border-purple-200' },
      { key: 'converted', label: 'Converted', color: 'bg-emerald-50 text-emerald-700 border-emerald-200' },
      { key: 'dropped', label: 'Dropped', color: 'bg-rose-50 text-rose-700 border-rose-200' },
      { key: 'unassigned', label: 'Unassigned', color: 'bg-slate-50 text-slate-700 border-slate-200' }
    ];

    stages.forEach(s => {
      const count = (d.pipeline && d.pipeline[s.key]) || 0;
      pipeEl.innerHTML += `
        <div class="p-3.5 rounded-lg border ${s.color}">
          <p class="text-xs font-semibold uppercase tracking-wider">${s.label}</p>
          <p class="text-xl font-bold mt-1">${count}</p>
        </div>
      `;
    });
  } catch(e) {
    console.error('Dashboard load error:', e);
  }
}
loadDashboard();
</script>
<?php
});

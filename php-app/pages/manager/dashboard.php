<?php
// php-app/pages/manager/dashboard.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Manager Overview', 'dashboard', function() {
?>
<div class="max-w-7xl mx-auto space-y-6">
  <!-- Top Stat Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">My Team Size</span>
        <span class="p-2 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="users" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="cardTeam">0</span>
        <span class="text-xs text-slate-400">Employees</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Team Leads</span>
        <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="layers" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="cardLeads">0</span>
        <span class="text-xs text-slate-400">Pipeline total</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Collection</span>
        <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="banknote" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-emerald-600" id="cardCollection">₹0</span>
        <span class="text-xs text-slate-400">Verified</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Verification</span>
        <span class="p-2 rounded-lg bg-amber-50 text-amber-600"><i data-lucide="alert-circle" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-amber-600" id="cardPendingVer">0</span>
        <span class="text-xs text-slate-400">Payments in queue</span>
      </div>
    </div>
  </div>

  <!-- Second Metric Row -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Clients</p>
      <p class="text-2xl font-bold text-slate-800 mt-2" id="cardClients">0</p>
      <p class="text-[11px] text-slate-400 mt-1">Cases actively running in CRM</p>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Unassigned Advocates</p>
      <p class="text-2xl font-bold text-rose-600 mt-2" id="cardUnadv">0</p>
      <p class="text-[11px] text-slate-400 mt-1">Active cases without assigned legal counsel</p>
    </div>
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Fee Agreements</p>
      <p class="text-2xl font-bold text-indigo-600 mt-2" id="cardAgreements">0</p>
      <p class="text-[11px] text-slate-400 mt-1">Signed or active fee agreements</p>
    </div>
  </div>

  <!-- Team Lead Pipeline -->
  <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-xs">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h3 class="font-bold text-slate-800 text-sm">Team Lead Pipeline</h3>
        <p class="text-xs text-slate-400">Lead distribution across all team members</p>
      </div>
      <a href="leads.php" class="text-xs text-amber-600 font-semibold hover:underline">Manage & Reassign Leads &rarr;</a>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3" id="mgrPipeline"></div>
  </div>
</div>

<script>
async function loadManagerDashboard() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/dashboard.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    if (!res.ok) return;
    const d = await res.json();
    const c = d.topCards;

    document.getElementById('cardTeam').textContent = c.myEmployees;
    document.getElementById('cardLeads').textContent = c.myLeads;
    document.getElementById('cardCollection').textContent = '₹' + Number(c.totalCollection).toLocaleString('en-IN');
    document.getElementById('cardPendingVer').textContent = c.pendingVerification;
    document.getElementById('cardClients').textContent = c.activeClients;
    document.getElementById('cardUnadv').textContent = c.unassignedAdvocates;
    document.getElementById('cardAgreements').textContent = c.activeAgreements;

    const pipeEl = document.getElementById('mgrPipeline');
    pipeEl.innerHTML = '';
    const stages = [
      { k: 'new', l: 'New', col: 'bg-blue-50 text-blue-800 border-blue-200' },
      { k: 'contacted', l: 'Contacted', col: 'bg-amber-50 text-amber-800 border-amber-200' },
      { k: 'interested', l: 'Interested', col: 'bg-indigo-50 text-indigo-800 border-indigo-200' },
      { k: 'in_progress', l: 'In Progress', col: 'bg-purple-50 text-purple-800 border-purple-200' },
      { k: 'converted', l: 'Converted', col: 'bg-emerald-50 text-emerald-800 border-emerald-200' },
      { k: 'dropped', l: 'Dropped', col: 'bg-rose-50 text-rose-800 border-rose-200' }
    ];

    stages.forEach(s => {
      const val = d.pipeline[s.k] || 0;
      pipeEl.innerHTML += `
        <div class="p-3 rounded-lg border ${s.col} text-center">
          <span class="text-[10px] font-bold uppercase tracking-wider">${s.l}</span>
          <p class="text-xl font-extrabold mt-1">${val}</p>
        </div>
      `;
    });
  } catch(e) {
    console.error('Manager dashboard error:', e);
  }
}
loadManagerDashboard();
</script>
<?php
});

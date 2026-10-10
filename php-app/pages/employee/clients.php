<?php
// php-app/pages/employee/clients.php
require_once __DIR__ . '/layout.php';

render_employee_layout('Active Clients', 'clients', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <!-- Top Bar: Search & Status Filter -->
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-1 min-w-[260px]">
      <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        <input type="text" id="searchInput" oninput="loadClients()" placeholder="Search client #, name, phone..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500">
      </div>
      <select id="caseStatusFilter" onchange="loadClients()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-indigo-500">
        <option value="all">All Case Statuses</option>
        <option value="active">Active Cases</option>
        <option value="closed">Closed Cases</option>
        <option value="dropped">Dropped Cases</option>
      </select>
    </div>
  </div>

  <!-- Clients Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Client #</th>
            <th class="py-3 px-4">Client Name</th>
            <th class="py-3 px-4">Phone / City</th>
            <th class="py-3 px-4">Total Debt</th>
            <th class="py-3 px-4">SX Fee</th>
            <th class="py-3 px-4">Case Status</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody id="clientsTableBody" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading clients...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Drawer: Client Details -->
<div id="clientDrawer" class="hidden fixed inset-y-0 right-0 w-full max-w-lg bg-white shadow-2xl z-50 flex flex-col border-l border-slate-200">
  <div class="p-4 border-b flex items-center justify-between bg-slate-50">
    <div>
      <h3 class="font-bold text-slate-800 text-sm" id="dClientNumber">CL-0000</h3>
      <p class="text-xs text-slate-500" id="dClientName">Client Name</p>
    </div>
    <button onclick="closeClientDrawer()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
  </div>
  <div class="flex-1 overflow-y-auto p-5 space-y-4 text-xs" id="dClientContent">
    <!-- Injected via JS -->
  </div>
</div>

<script>
let currentClients = [];

async function loadClients() {
  const token = localStorage.getItem('token');
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('caseStatusFilter').value;

  try {
    const res = await fetch(`../../api/employee/clients.php?status=${status}&search=${encodeURIComponent(search)}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    currentClients = d.clients || [];

    const tbody = document.getElementById('clientsTableBody');
    if (!currentClients.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No clients found matching criteria.</td></tr>';
      return;
    }

    tbody.innerHTML = currentClients.map(c => `
      <tr class="hover:bg-slate-50 transition cursor-pointer" onclick="openClientDrawer('${c.id}')">
        <td class="py-3 px-4 font-bold text-emerald-600">${c.client_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${c.name}</td>
        <td class="py-3 px-4">${c.phone}<br><span class="text-[11px] text-slate-400">${c.city || 'N/A'}</span></td>
        <td class="py-3 px-4 font-semibold">₹${Number(c.total_debt || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-semibold text-indigo-600">₹${Number(c.sx_fee || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${c.case_status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800'}">
            ${c.case_status || c.status}
          </span>
        </td>
        <td class="py-3 px-4 text-right">
          <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]" onclick="event.stopPropagation(); openClientDrawer('${c.id}')">
            View &rarr;
          </button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    console.error('Error loading clients:', e);
  }
}

async function openClientDrawer(id) {
  const token = localStorage.getItem('token');
  const res = await fetch(`../../api/employee/clients.php?id=${id}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const data = await res.json();
  const c = data.client;
  if (!c) return;

  document.getElementById('dClientNumber').textContent = c.client_number;
  document.getElementById('dClientName').textContent = c.name;

  const content = document.getElementById('dClientContent');
  content.innerHTML = `
    <div class="space-y-2.5 bg-slate-50 p-4 rounded-lg border border-slate-200">
      <div class="flex justify-between"><span class="text-slate-500">Phone:</span><span class="font-semibold text-slate-800">${c.phone}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Email:</span><span>${c.email || 'N/A'}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Total Debt:</span><span class="font-bold text-slate-800">₹${Number(c.total_debt || 0).toLocaleString('en-IN')}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">Settlement Target:</span><span class="font-bold text-emerald-600">₹${Number(c.settlement_target || 0).toLocaleString('en-IN')}</span></div>
      <div class="flex justify-between"><span class="text-slate-500">SX Retainer Fee:</span><span class="font-bold text-indigo-600">₹${Number(c.sx_fee || 0).toLocaleString('en-IN')}</span></div>
    </div>

    <!-- Lenders Section -->
    <div class="border-t pt-3">
      <h4 class="font-bold text-slate-700 mb-2">Associated Lenders (${(c.lenders || []).length})</h4>
      ${(c.lenders || []).length ? (c.lenders || []).map(l => `
        <div class="p-2.5 border rounded-lg bg-white mb-2 flex justify-between items-center">
          <div>
            <p class="font-semibold text-slate-800">${l.bank_name}</p>
            <p class="text-[11px] text-slate-400">Balance: ₹${Number(l.balance || 0).toLocaleString('en-IN')}</p>
          </div>
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">${l.status}</span>
        </div>
      `).join('') : '<p class="text-slate-400 text-xs">No lenders attached yet.</p>'}
    </div>

    <!-- Agreements Section -->
    <div class="border-t pt-3">
      <div class="flex justify-between items-center mb-2">
        <h4 class="font-bold text-slate-700">Fee Agreements</h4>
        <a href="agreements.php?client_id=${c.id}" class="text-indigo-600 font-semibold text-[11px]">+ New Agreement</a>
      </div>
      ${(c.agreements || []).length ? (c.agreements || []).map(a => `
        <div class="p-2.5 border rounded-lg bg-white mb-2 flex justify-between items-center">
          <div>
            <p class="font-semibold text-indigo-600">${a.agreement_number}</p>
            <p class="text-[11px] text-slate-500">Fee: ₹${Number(a.total_fee || 0).toLocaleString('en-IN')} (${a.resolution_duration || '12M'})</p>
          </div>
          <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">${a.status}</span>
        </div>
      `).join('') : '<p class="text-slate-400 text-xs">No agreement generated yet.</p>'}
    </div>
  `;

  document.getElementById('clientDrawer').classList.remove('hidden');
}

function closeClientDrawer() { document.getElementById('clientDrawer').classList.add('hidden'); }

loadClients();
</script>
<?php
});

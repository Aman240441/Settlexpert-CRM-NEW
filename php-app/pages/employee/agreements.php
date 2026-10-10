<?php
// php-app/pages/employee/agreements.php
require_once __DIR__ . '/layout.php';

render_employee_layout('Fee Agreements', 'agreements', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <!-- Controls Bar -->
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-1 min-w-[260px]">
      <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        <input type="text" id="searchInput" oninput="loadAgreements()" placeholder="Search agreement #, client..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500">
      </div>
      <select id="statusFilter" onchange="loadAgreements()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-indigo-500">
        <option value="all">All Statuses</option>
        <option value="draft">Draft</option>
        <option value="sent">Sent</option>
        <option value="signed">Signed</option>
        <option value="active">Active</option>
        <option value="completed">Completed</option>
      </select>
    </div>

    <button onclick="openCreateAgrModal()" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Create Agreement
    </button>
  </div>

  <!-- Agreements Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Agreement #</th>
            <th class="py-3 px-4">Client Name / Phone</th>
            <th class="py-3 px-4">Total Fee</th>
            <th class="py-3 px-4">Monthly Fee</th>
            <th class="py-3 px-4">Duration</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody id="agreementsTableBody" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading agreements...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Create Agreement -->
<div id="createAgrModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h3 class="font-bold text-slate-800 text-sm">Create New Fee Agreement</h3>
      <button onclick="closeCreateAgrModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form id="createAgrForm" onsubmit="handleCreateAgr(event)" class="space-y-3 text-xs">
      <div>
        <label class="font-medium text-slate-700 block mb-1">Select Client *</label>
        <select id="clientSelect" name="client_id" required class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
          <option value="">Choose a client...</option>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="font-medium text-slate-700 block mb-1">Total Retainer Fee (₹) *</label>
          <input type="number" id="totalFeeInput" name="total_fee" required placeholder="50000" oninput="calcMonthly()" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
        <div>
          <label class="font-medium text-slate-700 block mb-1">Duration *</label>
          <select id="durationSelect" name="resolution_duration" onchange="calcMonthly()" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
            <option value="6 Months">6 Months</option>
            <option value="12 Months" selected>12 Months</option>
            <option value="18 Months">18 Months</option>
            <option value="24 Months">24 Months</option>
          </select>
        </div>
      </div>
      <div class="p-3 bg-indigo-50 rounded-lg flex justify-between items-center text-indigo-900 font-semibold">
        <span>Calculated Monthly Installment:</span>
        <span id="monthlyDisplay" class="text-sm font-bold">₹0 / month</span>
      </div>
      <div class="pt-3 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeCreateAgrModal()" class="px-3 py-1.5 border rounded-lg hover:bg-slate-50">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold">Generate Agreement</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentAgreements = [];

async function loadAgreements() {
  const token = localStorage.getItem('token');
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;

  try {
    const res = await fetch(`../../api/employee/agreements.php?status=${status}&search=${encodeURIComponent(search)}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    currentAgreements = d.agreements || [];

    const tbody = document.getElementById('agreementsTableBody');
    if (!currentAgreements.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No agreements found.</td></tr>';
      return;
    }

    tbody.innerHTML = currentAgreements.map(a => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-indigo-600">${a.agreement_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${a.name || a.client_name || 'N/A'}<br><span class="text-[11px] text-slate-400">${a.phone || a.client_phone || ''}</span></td>
        <td class="py-3 px-4 font-semibold">₹${Number(a.total_fee || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-semibold text-emerald-600">₹${Number(a.monthly_fee || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4">${a.resolution_duration || '12 Months'}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${a.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'}">
            ${a.status}
          </span>
        </td>
        <td class="py-3 px-4 text-right">
          <button onclick="alert('Agreement Number: ${a.agreement_number}\\nClient: ${a.name}\\nTotal Fee: ₹${a.total_fee}\\nMonthly: ₹${a.monthly_fee}')" class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]">
            Preview &rarr;
          </button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    console.error('Error loading agreements:', e);
  }
}

function calcMonthly() {
  const total = Number(document.getElementById('totalFeeInput').value || 0);
  const durStr = document.getElementById('durationSelect').value;
  const m = parseInt(durStr) || 12;
  const monthly = Math.round(total / m);
  document.getElementById('monthlyDisplay').textContent = '₹' + monthly.toLocaleString('en-IN') + ' / month';
}

async function openCreateAgrModal() {
  document.getElementById('createAgrModal').classList.remove('hidden');
  const token = localStorage.getItem('token');
  const res = await fetch('../../api/employee/clients.php', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const d = await res.json();
  const select = document.getElementById('clientSelect');
  select.innerHTML = '<option value="">Choose a client...</option>' + (d.clients || []).map(c => `
    <option value="${c.id}">${c.client_number} - ${c.name} (Debt: ₹${c.total_debt})</option>
  `).join('');
}

function closeCreateAgrModal() { document.getElementById('createAgrModal').classList.add('hidden'); }

async function handleCreateAgr(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/employee/agreements.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  const resJson = await res.json();
  if (res.ok) {
    alert(`Agreement ${resJson.agreement_number} generated successfully!`);
    closeCreateAgrModal();
    e.target.reset();
    loadAgreements();
  } else {
    alert(resJson.error || 'Failed to generate agreement');
  }
}

loadAgreements();
</script>
<?php
});

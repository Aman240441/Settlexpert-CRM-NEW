<?php
// php-app/pages/manager/clients.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Team Clients & Advocate Assignment', 'clients', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-1 min-w-[260px]">
      <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        <input type="text" id="searchInput" oninput="loadClients()" placeholder="Search client #, name, phone..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
      </div>
      <select id="caseFilter" onchange="loadClients()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-amber-500">
        <option value="all">All Cases</option>
        <option value="active">Active</option>
        <option value="closed">Closed</option>
        <option value="dropped">Dropped</option>
      </select>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Client #</th>
          <th class="py-3 px-4">Name</th>
          <th class="py-3 px-4">Assigned Agent</th>
          <th class="py-3 px-4">Legal Advocate</th>
          <th class="py-3 px-4">Total Debt</th>
          <th class="py-3 px-4">SX Retainer Fee</th>
          <th class="py-3 px-4 text-right">Actions</th>
        </tr>
      </thead>
      <tbody id="clientsTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading clients...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Assign Advocate -->
<div id="advocateModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl border border-slate-200 space-y-4">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Assign Legal Advocate</h4>
      <button onclick="closeAdvocateModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleAssignAdvocate(event)" class="space-y-3 text-xs">
      <input type="hidden" id="advClientId">
      <p class="text-slate-600">Client: <b id="advClientNum" class="text-emerald-600"></b></p>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Select Legal Advocate *</label>
        <select id="advSelect" required class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
          <option value="">Choose advocate...</option>
        </select>
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Case Assignment Notes</label>
        <textarea id="advNotes" rows="2" placeholder="e.g. Injunction notice, urgent summons..." class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none"></textarea>
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAdvocateModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg">Assign Counsel</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentAdvocates = [];

async function init() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/advocates.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    currentAdvocates = d.advocates || [];

    const select = document.getElementById('advSelect');
    currentAdvocates.forEach(a => {
      select.innerHTML += `<option value="${a.id}">${a.name} (${a.specialization || 'Advocate'})</option>`;
    });
  } catch(e){}

  loadClients();
}

async function loadClients() {
  const token = localStorage.getItem('token');
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('caseFilter').value;

  try {
    const res = await fetch(`../../api/manager/clients.php?case_status=${status}&search=${encodeURIComponent(search)}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const clients = d.clients || [];

    const tbody = document.getElementById('clientsTableBody');
    if (!clients.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No clients found.</td></tr>';
      return;
    }

    tbody.innerHTML = clients.map(c => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-emerald-600">${c.client_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${c.name}</td>
        <td class="py-3 px-4 text-slate-600">${c.employee_name || 'Unassigned'}</td>
        <td class="py-3 px-4">
          ${c.advocate_name ? `<span class="font-bold text-indigo-700">${c.advocate_name}</span>` : `<span class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800">Unassigned</span>`}
        </td>
        <td class="py-3 px-4 font-semibold">₹${Number(c.total_debt || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-bold text-indigo-600">₹${Number(c.sx_fee || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 text-right">
          <button onclick="openAdvocateModal('${c.id}', '${c.client_number}')" class="px-2.5 py-1 text-[11px] bg-indigo-50 hover:bg-indigo-100 text-indigo-800 font-bold rounded-lg transition">
            Assign Counsel
          </button>
        </td>
      </tr>
    `).join('');
  } catch(err) {
    console.error('Failed to load clients:', err);
  }
}

function openAdvocateModal(id, num) {
  document.getElementById('advClientId').value = id;
  document.getElementById('advClientNum').textContent = num;
  document.getElementById('advocateModal').classList.remove('hidden');
}

function closeAdvocateModal() { document.getElementById('advocateModal').classList.add('hidden'); }

async function handleAssignAdvocate(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const clientId = document.getElementById('advClientId').value;
  const advocateId = document.getElementById('advSelect').value;
  const notes = document.getElementById('advNotes').value;

  const res = await fetch(`../../api/manager/clients.php?action=advocate`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ client_id: clientId, advocate_id: advocateId, notes })
  });

  const d = await res.json();
  if (res.ok) {
    alert(d.message);
    closeAdvocateModal();
    loadClients();
  } else {
    alert(d.error || 'Failed to assign advocate');
  }
}

init();
</script>
<?php
});

<?php
// php-app/pages/manager/leads.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Team Leads & Reassignment', 'leads', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <!-- Controls Bar -->
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-1 min-w-[260px]">
      <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        <input type="text" id="searchInput" oninput="loadLeads()" placeholder="Search lead #, name, phone, city..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500">
      </div>
      <select id="statusFilter" onchange="loadLeads()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-amber-500">
        <option value="all">All Statuses</option>
        <option value="new">New</option>
        <option value="contacted">Contacted</option>
        <option value="interested">Interested</option>
        <option value="in_progress">In Progress</option>
        <option value="converted">Converted</option>
        <option value="dropped">Dropped</option>
      </select>
      <select id="empFilter" onchange="loadLeads()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-amber-500">
        <option value="">All Team Members</option>
      </select>
    </div>
  </div>

  <!-- Leads Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Lead #</th>
          <th class="py-3 px-4">Customer Name</th>
          <th class="py-3 px-4">Phone / City</th>
          <th class="py-3 px-4">Total Debt</th>
          <th class="py-3 px-4">Assigned To</th>
          <th class="py-3 px-4">Stage</th>
          <th class="py-3 px-4 text-right">Reassign</th>
        </tr>
      </thead>
      <tbody id="leadsTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading leads...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Reassign Lead -->
<div id="reassignModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl border border-slate-200 space-y-4">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Reassign Lead</h4>
      <button onclick="closeReassignModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleReassign(event)" class="space-y-3 text-xs">
      <input type="hidden" id="reassignLeadId">
      <p class="text-slate-600">Reassigning lead: <b id="reassignLeadNum" class="text-indigo-600"></b></p>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Select Target Employee *</label>
        <select id="reassignEmpSelect" required class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
          <option value="">Choose employee...</option>
        </select>
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Reason / Instructions</label>
        <textarea id="reassignReason" rows="2" placeholder="e.g. Higher debt tier, reallocation..." class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none"></textarea>
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeReassignModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg">Confirm Reassignment</button>
      </div>
    </form>
  </div>
</div>

<script>
let teamEmps = [];

async function init() {
  const token = localStorage.getItem('token');
  const res = await fetch('../../api/manager/team.php', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const d = await res.json();
  teamEmps = d.employees || [];

  const empSelect = document.getElementById('empFilter');
  const reassignSelect = document.getElementById('reassignEmpSelect');

  teamEmps.forEach(e => {
    empSelect.innerHTML += `<option value="${e.id}">${e.name}</option>`;
    reassignSelect.innerHTML += `<option value="${e.id}">${e.name} (${e.emp_or_mgr_id || 'EMP'})</option>`;
  });

  loadLeads();
}

async function loadLeads() {
  const token = localStorage.getItem('token');
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;
  const empId = document.getElementById('empFilter').value;

  try {
    const res = await fetch(`../../api/manager/leads.php?status=${status}&employee_id=${empId}&search=${encodeURIComponent(search)}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const leads = d.leads || [];

    const tbody = document.getElementById('leadsTableBody');
    if (!leads.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No leads found.</td></tr>';
      return;
    }

    tbody.innerHTML = leads.map(l => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-indigo-600">${l.lead_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${l.name}</td>
        <td class="py-3 px-4">${l.phone}<br><span class="text-[11px] text-slate-400">${l.city || 'N/A'}</span></td>
        <td class="py-3 px-4 font-semibold">₹${Number(l.total_debt || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-medium text-slate-700">${l.employee_name || '<span class="text-rose-500 font-bold">Unassigned</span>'}</td>
        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-800">${l.status}</span></td>
        <td class="py-3 px-4 text-right">
          <button onclick="openReassignModal('${l.id}', '${l.lead_number}')" class="px-2.5 py-1 text-[11px] bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold rounded-lg transition">
            Reassign
          </button>
        </td>
      </tr>
    `).join('');
  } catch(err) {
    console.error('Failed to load leads:', err);
  }
}

function openReassignModal(id, num) {
  document.getElementById('reassignLeadId').value = id;
  document.getElementById('reassignLeadNum').textContent = num;
  document.getElementById('reassignModal').classList.remove('hidden');
}

function closeReassignModal() { document.getElementById('reassignModal').classList.add('hidden'); }

async function handleReassign(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const leadId = document.getElementById('reassignLeadId').value;
  const newEmpId = document.getElementById('reassignEmpSelect').value;
  const reason = document.getElementById('reassignReason').value;

  const res = await fetch(`../../api/manager/leads.php?action=assign`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ lead_id: leadId, new_employee_id: newEmpId, reason })
  });

  const d = await res.json();
  if (res.ok) {
    alert(d.message);
    closeReassignModal();
    loadLeads();
  } else {
    alert(d.error || 'Failed to reassign');
  }
}

init();
</script>
<?php
});

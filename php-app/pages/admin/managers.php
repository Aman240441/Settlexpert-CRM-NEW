<?php
// php-app/pages/admin/managers.php
require_once __DIR__ . '/layout.php';

render_admin_layout('Managers Roster', 'managers', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Operational Managers Roster</h3>
      <p class="text-xs text-slate-400">Department leaders, team managers, and legal/finance directors</p>
    </div>
    <button onclick="openAddMgrModal()" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition shadow-xs flex items-center gap-1.5">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Manager
    </button>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Manager</th>
          <th class="py-3 px-4">Manager Code</th>
          <th class="py-3 px-4">Manager Role / Type</th>
          <th class="py-3 px-4">Department</th>
          <th class="py-3 px-4 text-center">Team Members</th>
          <th class="py-3 px-4 text-center">Active Clients</th>
          <th class="py-3 px-4">Status</th>
        </tr>
      </thead>
      <tbody id="mgrTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading managers...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Manager -->
<div id="addMgrModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-3">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Add New Manager</h4>
      <button onclick="closeAddMgrModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleCreateMgr(event)" class="space-y-3 text-xs">
      <div>
        <label class="block font-medium text-slate-700 mb-1">Full Name *</label>
        <input type="text" name="name" required placeholder="e.g. Ramesh Kumar" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block font-medium text-slate-700 mb-1">Email Address *</label>
          <input type="email" name="email" required placeholder="ramesh@settlexpert.com" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
        </div>
        <div>
          <label class="block font-medium text-slate-700 mb-1">Phone Number</label>
          <input type="text" name="phone" placeholder="+91 9876543210" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block font-medium text-slate-700 mb-1">Department</label>
          <select id="mgrDeptSelect" name="department_id" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
            <option value="">Select department...</option>
          </select>
        </div>
        <div>
          <label class="block font-medium text-slate-700 mb-1">Manager Role Type</label>
          <select id="mgrTypeSelect" name="manager_type_id" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
            <option value="">Select role type...</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Initial Password *</label>
        <input type="password" name="password" required minlength="6" value="SettleExpert@2026" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAddMgrModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg">Create Manager</button>
      </div>
    </form>
  </div>
</div>

<script>
async function init() {
  const token = localStorage.getItem('token');
  try {
    const [dRes, mtRes] = await Promise.all([
      fetch('../../api/admin/departments.php', { headers: { 'Authorization': `Bearer ${token}` } }),
      fetch('../../api/admin/manager_types.php', { headers: { 'Authorization': `Bearer ${token}` } })
    ]);
    const d = await dRes.json();
    const mt = await mtRes.json();

    const dSel = document.getElementById('mgrDeptSelect');
    (d.departments || []).forEach(dp => { dSel.innerHTML += `<option value="${dp.id}">${dp.name}</option>`; });

    const mtSel = document.getElementById('mgrTypeSelect');
    (mt.managerTypes || []).forEach(m => { mtSel.innerHTML += `<option value="${m.id}">${m.name} (${m.code})</option>`; });
  } catch(e){}
  loadManagers();
}

async function loadManagers() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/admin/managers.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const mgrs = d.managers || [];

    const tbody = document.getElementById('mgrTableBody');
    if (!mgrs.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No managers found.</td></tr>';
      return;
    }

    tbody.innerHTML = mgrs.map(m => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${m.name}<br><span class="text-[11px] font-normal text-slate-400">${m.email}</span></td>
        <td class="py-3 px-4 font-mono font-bold text-rose-700">${m.emp_or_mgr_id || 'MGR-XXXX'}</td>
        <td class="py-3 px-4 font-semibold text-slate-700">${m.manager_type_name || 'General'}</td>
        <td class="py-3 px-4">${m.department_name || 'Unassigned'}</td>
        <td class="py-3 px-4 text-center font-bold text-indigo-600">${m.employee_count || 0}</td>
        <td class="py-3 px-4 text-center font-bold text-emerald-600">${m.client_count || 0}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${m.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
            ${m.status}
          </span>
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

function openAddMgrModal() { document.getElementById('addMgrModal').classList.remove('hidden'); }
function closeAddMgrModal() { document.getElementById('addMgrModal').classList.add('hidden'); }

async function handleCreateMgr(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/admin/managers.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  if (res.ok) {
    alert('Manager registered successfully');
    closeAddMgrModal();
    e.target.reset();
    loadManagers();
  } else {
    alert('Failed to register manager');
  }
}

init();
</script>
<?php
});

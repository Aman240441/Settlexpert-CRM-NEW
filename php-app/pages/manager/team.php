<?php
// php-app/pages/manager/team.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Team Directory', 'team', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Assigned Team Employees</h3>
      <p class="text-xs text-slate-400">Direct reports and department members under your management</p>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Employee</th>
          <th class="py-3 px-4">ID Code</th>
          <th class="py-3 px-4">Phone</th>
          <th class="py-3 px-4">Department</th>
          <th class="py-3 px-4 text-center">Assigned Leads</th>
          <th class="py-3 px-4 text-center">Active Clients</th>
          <th class="py-3 px-4 text-center">Pending Tasks</th>
          <th class="py-3 px-4 text-right">Action</th>
        </tr>
      </thead>
      <tbody id="teamTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="8" class="py-8 text-center text-slate-400">Loading team...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Reset Password -->
<div id="resetModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl border border-slate-200 space-y-4">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Reset Employee Password</h4>
      <button onclick="closeResetModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleResetPassword(event)" class="space-y-3 text-xs">
      <input type="hidden" id="resetEmpId">
      <div>
        <p class="text-slate-600 mb-2">Resetting password for: <b id="resetEmpName" class="text-slate-800"></b></p>
        <label class="block font-medium text-slate-700 mb-1">New Password (min 6 chars) *</label>
        <input type="password" id="resetNewPass" required minlength="6" placeholder="Enter new password" class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeResetModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg">Reset Password</button>
      </div>
    </form>
  </div>
</div>

<script>
let currentEmployees = [];

async function loadTeam() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/team.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    currentEmployees = d.employees || [];

    const tbody = document.getElementById('teamTableBody');
    if (!currentEmployees.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">No team members assigned yet.</td></tr>';
      return;
    }

    tbody.innerHTML = currentEmployees.map(e => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${e.name}<br><span class="text-[11px] font-normal text-slate-400">${e.email}</span></td>
        <td class="py-3 px-4 font-mono text-[11px] text-amber-700 font-semibold">${e.emp_or_mgr_id || 'EMP-XXXX'}</td>
        <td class="py-3 px-4">${e.phone || 'N/A'}</td>
        <td class="py-3 px-4">${e.department_name || 'General'}</td>
        <td class="py-3 px-4 text-center font-bold text-indigo-600">${e.leads_count || 0}</td>
        <td class="py-3 px-4 text-center font-bold text-emerald-600">${e.clients_count || 0}</td>
        <td class="py-3 px-4 text-center font-bold text-amber-600">${e.active_tasks_count || 0}</td>
        <td class="py-3 px-4 text-right">
          <button onclick="openResetModal('${e.id}', '${e.name}')" class="px-2.5 py-1 text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-lg transition">
            Reset PW
          </button>
        </td>
      </tr>
    `).join('');
  } catch(err) {
    console.error('Failed to load team:', err);
  }
}

function openResetModal(id, name) {
  document.getElementById('resetEmpId').value = id;
  document.getElementById('resetEmpName').textContent = name;
  document.getElementById('resetModal').classList.remove('hidden');
}

function closeResetModal() { document.getElementById('resetModal').classList.add('hidden'); }

async function handleResetPassword(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const id = document.getElementById('resetEmpId').value;
  const pw = document.getElementById('resetNewPass').value;

  const res = await fetch(`../../api/manager/team.php?action=reset-password`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ employee_id: id, new_password: pw })
  });

  const d = await res.json();
  if (res.ok) {
    alert(d.message);
    closeResetModal();
  } else {
    alert(d.error || 'Failed to reset password');
  }
}

loadTeam();
</script>
<?php
});

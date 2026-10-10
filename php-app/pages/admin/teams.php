<?php
// php-app/pages/admin/teams.php
require_once __DIR__ . '/layout.php';

render_admin_layout('Teams Management', 'teams', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Operational Teams</h3>
      <p class="text-xs text-slate-400">Department squads, squad leaders, and assigned employees</p>
    </div>
    <button onclick="openAddTeamModal()" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition shadow-xs flex items-center gap-1.5">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Team
    </button>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Team Name</th>
          <th class="py-3 px-4">Code</th>
          <th class="py-3 px-4">Department</th>
          <th class="py-3 px-4">Team Manager</th>
          <th class="py-3 px-4 text-center">Active Members</th>
          <th class="py-3 px-4">Status</th>
        </tr>
      </thead>
      <tbody id="teamsTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="6" class="py-8 text-center text-slate-400">Loading teams...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Team -->
<div id="addTeamModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl border border-slate-200 space-y-3">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Add Team</h4>
      <button onclick="closeAddTeamModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleCreateTeam(event)" class="space-y-3 text-xs">
      <div>
        <label class="block font-medium text-slate-700 mb-1">Team Name *</label>
        <input type="text" name="name" required placeholder="e.g. Credit Card Settlement Squad A" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Team Code *</label>
        <input type="text" name="code" required placeholder="e.g. CCS-A" class="w-full border rounded-lg p-2 text-xs uppercase focus:border-rose-500 focus:outline-none">
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Department</label>
        <select id="teamDeptSelect" name="department_id" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
          <option value="">Select department...</option>
        </select>
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAddTeamModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg">Create Team</button>
      </div>
    </form>
  </div>
</div>

<script>
async function init() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/admin/departments.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const select = document.getElementById('teamDeptSelect');
    (d.departments || []).forEach(dp => {
      select.innerHTML += `<option value="${dp.id}">${dp.name} (${dp.code})</option>`;
    });
  } catch(e){}
  loadTeams();
}

async function loadTeams() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/admin/teams.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const teams = d.teams || [];

    const tbody = document.getElementById('teamsTableBody');
    if (!teams.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">No teams configured yet.</td></tr>';
      return;
    }

    tbody.innerHTML = teams.map(t => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${t.name}</td>
        <td class="py-3 px-4 font-mono font-bold text-rose-700">${t.code}</td>
        <td class="py-3 px-4">${t.department_name || 'Unassigned'}</td>
        <td class="py-3 px-4 text-slate-600">${t.manager_name || 'Unassigned'}</td>
        <td class="py-3 px-4 text-center font-bold text-indigo-600">${t.member_count || 0}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${t.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
            ${t.status}
          </span>
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

function openAddTeamModal() { document.getElementById('addTeamModal').classList.remove('hidden'); }
function closeAddTeamModal() { document.getElementById('addTeamModal').classList.add('hidden'); }

async function handleCreateTeam(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/admin/teams.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  if (res.ok) {
    alert('Team created successfully');
    closeAddTeamModal();
    e.target.reset();
    loadTeams();
  } else {
    alert('Failed to create team');
  }
}

init();
</script>
<?php
});

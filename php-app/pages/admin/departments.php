<?php
// php-app/pages/admin/departments.php
require_once __DIR__ . '/layout.php';

render_admin_layout('Departments Management', 'departments', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Organization Departments</h3>
      <p class="text-xs text-slate-400">Manage business units, department codes, and appointed heads</p>
    </div>
    <button onclick="openAddDeptModal()" class="px-3.5 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-xs transition shadow-xs flex items-center gap-1.5">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Department
    </button>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Department Name</th>
          <th class="py-3 px-4">Code</th>
          <th class="py-3 px-4">Head Manager</th>
          <th class="py-3 px-4 text-center">Managers</th>
          <th class="py-3 px-4 text-center">Employees</th>
          <th class="py-3 px-4 text-center">Teams</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4 text-right">Action</th>
        </tr>
      </thead>
      <tbody id="deptTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="8" class="py-8 text-center text-slate-400">Loading departments...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Add Department -->
<div id="addDeptModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-6 shadow-xl border border-slate-200 space-y-3">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Add Department</h4>
      <button onclick="closeAddDeptModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleCreateDept(event)" class="space-y-3 text-xs">
      <div>
        <label class="block font-medium text-slate-700 mb-1">Department Name *</label>
        <input type="text" name="name" required placeholder="e.g. Legal Operations" class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none">
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Department Code *</label>
        <input type="text" name="code" required placeholder="e.g. LEG-OPS" class="w-full border rounded-lg p-2 text-xs uppercase focus:border-rose-500 focus:outline-none">
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Description</label>
        <textarea name="description" rows="2" placeholder="Brief function of department..." class="w-full border rounded-lg p-2 text-xs focus:border-rose-500 focus:outline-none"></textarea>
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAddDeptModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg">Create Department</button>
      </div>
    </form>
  </div>
</div>

<script>
async function loadDepartments() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/admin/departments.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const depts = d.departments || [];

    const tbody = document.getElementById('deptTableBody');
    if (!depts.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">No departments configured yet.</td></tr>';
      return;
    }

    tbody.innerHTML = depts.map(dp => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${dp.name}</td>
        <td class="py-3 px-4 font-mono font-bold text-rose-700">${dp.code}</td>
        <td class="py-3 px-4 text-slate-600">${dp.head_manager_name || '<span class="text-slate-400">Not Assigned</span>'}</td>
        <td class="py-3 px-4 text-center font-semibold text-slate-800">${dp.manager_count || 0}</td>
        <td class="py-3 px-4 text-center font-semibold text-slate-800">${dp.employee_count || 0}</td>
        <td class="py-3 px-4 text-center font-semibold text-slate-800">${dp.team_count || 0}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${dp.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
            ${dp.status}
          </span>
        </td>
        <td class="py-3 px-4 text-right">
          <button onclick="handleDeleteDept('${dp.id}', '${dp.name}')" class="text-rose-600 hover:text-rose-800 font-semibold text-[11px]">
            Delete
          </button>
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

function openAddDeptModal() { document.getElementById('addDeptModal').classList.remove('hidden'); }
function closeAddDeptModal() { document.getElementById('addDeptModal').classList.add('hidden'); }

async function handleCreateDept(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/admin/departments.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  const d = await res.json();
  if (res.ok) {
    alert('Department created successfully');
    closeAddDeptModal();
    e.target.reset();
    loadDepartments();
  } else {
    alert(d.error || 'Failed to create department');
  }
}

async function handleDeleteDept(id, name) {
  if (!confirm(`Are you sure you want to delete department "${name}"?`)) return;
  const token = localStorage.getItem('token');
  const res = await fetch(`../../api/admin/departments.php?id=${id}`, {
    method: 'DELETE',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const d = await res.json();
  if (res.ok) {
    alert('Department deleted');
    loadDepartments();
  } else {
    alert(d.error || 'Failed to delete department');
  }
}

loadDepartments();
</script>
<?php
});

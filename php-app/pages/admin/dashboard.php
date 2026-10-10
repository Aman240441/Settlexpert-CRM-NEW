<?php
// php-app/pages/admin/dashboard.php
require_once __DIR__ . '/layout.php';

render_admin_layout('Executive Overview', 'dashboard', function() {
?>
<div class="max-w-7xl mx-auto space-y-6">
  <!-- Top Stat Cards Grid -->
  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Workforce</span>
        <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600"><i data-lucide="users" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="statWorkforce">0</span>
        <span class="text-xs text-slate-400" id="statWorkforceSub">0 Mgrs / 0 Emps</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Leads in CRM</span>
        <span class="p-2 rounded-lg bg-blue-50 text-blue-600"><i data-lucide="layers" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-slate-800" id="statLeads">0</span>
        <span class="text-xs text-slate-400">All channels</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Verified Revenue</span>
        <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600"><i data-lucide="banknote" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-emerald-600" id="statCollection">₹0</span>
        <span class="text-xs text-slate-400">Verified</span>
      </div>
    </div>

    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs">
      <div class="flex items-center justify-between">
        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Verification</span>
        <span class="p-2 rounded-lg bg-rose-50 text-rose-600"><i data-lucide="clock" class="w-4 h-4"></i></span>
      </div>
      <div class="mt-3 flex items-baseline gap-2">
        <span class="text-2xl font-bold text-rose-600" id="statPendingVer">0</span>
        <span class="text-xs text-slate-400" id="statPendingAmt">₹0 in review</span>
      </div>
    </div>
  </div>

  <!-- Manager Overview Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="p-4 border-b flex justify-between items-center bg-slate-50">
      <div>
        <h3 class="font-bold text-slate-800 text-sm">Manager Caseload & Department Performance</h3>
        <p class="text-xs text-slate-400">Overview of managers, their team members, and CRM output</p>
      </div>
      <a href="managers.php" class="text-xs text-rose-600 font-bold hover:underline">Manage Managers &rarr;</a>
    </div>
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Manager Name</th>
          <th class="py-3 px-4">Role / Type</th>
          <th class="py-3 px-4">Department</th>
          <th class="py-3 px-4 text-center">Team Size</th>
          <th class="py-3 px-4 text-center">Assigned Leads</th>
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

<script>
async function loadAdminDashboard() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/admin/dashboard.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    if (!res.ok) return;
    const d = await res.json();
    const s = d.stats;

    document.getElementById('statWorkforce').textContent = s.totalManagers + s.totalEmployees;
    document.getElementById('statWorkforceSub').textContent = `${s.totalManagers} Managers / ${s.totalEmployees} Employees`;
    document.getElementById('statLeads').textContent = s.totalLeads;
    document.getElementById('statCollection').textContent = '₹' + Number(s.totalReceived || 0).toLocaleString('en-IN');
    document.getElementById('statPendingVer').textContent = s.pendingVerificationCount;
    document.getElementById('statPendingAmt').textContent = '₹' + Number(s.pendingVerificationAmt || 0).toLocaleString('en-IN') + ' in queue';

    const tbody = document.getElementById('mgrTableBody');
    const mgrs = d.managerOverview || [];
    if (!mgrs.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No managers created yet.</td></tr>';
      return;
    }

    tbody.innerHTML = mgrs.map(m => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${m.name}<br><span class="text-[11px] font-mono text-slate-400 font-normal">${m.emp_or_mgr_id || 'MGR-XXXX'}</span></td>
        <td class="py-3 px-4 font-semibold text-rose-700">${m.manager_type}</td>
        <td class="py-3 px-4">${m.department_name}</td>
        <td class="py-3 px-4 text-center font-bold text-indigo-600">${m.employee_count}</td>
        <td class="py-3 px-4 text-center font-bold text-blue-600">${m.lead_count}</td>
        <td class="py-3 px-4 text-center font-bold text-emerald-600">${m.client_count}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${m.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
            ${m.status}
          </span>
        </td>
      </tr>
    `).join('');
  } catch(e) {
    console.error('Admin dashboard load error:', e);
  }
}
loadAdminDashboard();
</script>
<?php
});

<?php
// php-app/pages/manager/advocates.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Legal Advocates Directory', 'advocates', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Legal Counsel Network</h3>
      <p class="text-xs text-slate-400">Roster of empanelled advocates handling legal notices, summons & representation</p>
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="advocatesGrid">
    <div class="col-span-3 text-center py-10 text-slate-400 text-xs">Loading advocates...</div>
  </div>
</div>

<script>
async function loadAdvocates() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/advocates.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const advocates = d.advocates || [];

    const grid = document.getElementById('advocatesGrid');
    if (!advocates.length) {
      grid.innerHTML = '<div class="col-span-3 text-center py-10 text-slate-400 text-xs">No advocates empanelled yet.</div>';
      return;
    }

    grid.innerHTML = advocates.map(a => `
      <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-mono font-bold bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 rounded">
            ${a.advocate_id || 'ADV-XXXX'}
          </span>
          <span class="text-[10px] font-bold px-2 py-0.5 rounded ${a.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'}">
            ${a.status}
          </span>
        </div>
        <div>
          <h4 class="font-bold text-slate-800 text-sm">${a.name}</h4>
          <p class="text-xs text-indigo-600 font-semibold mt-0.5">${a.specialization || 'Debt Resolution Counsel'}</p>
        </div>
        <div class="text-[11px] text-slate-500 space-y-1 border-t pt-2">
          <p><span class="text-slate-400">Reg No:</span> ${a.registration_number || 'N/A'}</p>
          <p><span class="text-slate-400">Mobile:</span> ${a.mobile || 'N/A'}</p>
          <p><span class="text-slate-400">Email:</span> ${a.email || 'N/A'}</p>
        </div>
        <div class="bg-slate-50 p-2.5 rounded-lg flex items-center justify-between text-xs border border-slate-100">
          <span class="text-slate-500 font-medium">Assigned Cases:</span>
          <span class="font-bold text-slate-800">${a.assigned_clients_count || 0} active clients</span>
        </div>
      </div>
    `).join('');
  } catch(e){}
}
loadAdvocates();
</script>
<?php
});

<?php
// php-app/pages/manager/follow_ups.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Team Follow-ups Monitoring', 'follow_ups', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <!-- Filter Pills -->
  <div class="flex flex-wrap items-center gap-2 bg-white p-3 rounded-xl border border-slate-200 shadow-xs">
    <button onclick="setFollowFilter('all')" id="btnFilterAll" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-slate-950">
      All Follow-ups
    </button>
    <button onclick="setFollowFilter('today')" id="btnFilterToday" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
      Due Today
    </button>
    <button onclick="setFollowFilter('overdue')" id="btnFilterOverdue" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
      Overdue
    </button>
    <button onclick="setFollowFilter('upcoming')" id="btnFilterUpcoming" class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700">
      Upcoming
    </button>
  </div>

  <!-- Followups Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Lead #</th>
          <th class="py-3 px-4">Customer Name / Phone</th>
          <th class="py-3 px-4">Agent</th>
          <th class="py-3 px-4">Call Status</th>
          <th class="py-3 px-4">Remark</th>
          <th class="py-3 px-4">Scheduled Date</th>
        </tr>
      </thead>
      <tbody id="followTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="6" class="py-8 text-center text-slate-400">Loading follow-ups...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<script>
let currentFollowFilter = 'all';

function setFollowFilter(f) {
  currentFollowFilter = f;
  ['all', 'today', 'overdue', 'upcoming'].forEach(id => {
    const el = document.getElementById('btnFilter' + id.charAt(0).toUpperCase() + id.slice(1));
    if (el) {
      if (id === f) {
        el.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-amber-500 text-slate-950';
      } else {
        el.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700';
      }
    }
  });
  loadFollowUps();
}

async function loadFollowUps() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch(`../../api/manager/follow_ups.php?filter=${currentFollowFilter}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const list = d.followUps || [];

    const tbody = document.getElementById('followTableBody');
    if (!list.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">No follow-ups recorded for this filter.</td></tr>';
      return;
    }

    tbody.innerHTML = list.map(f => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-indigo-600">${f.lead_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${f.lead_name}<br><span class="text-[11px] text-slate-400 font-normal">${f.lead_phone}</span></td>
        <td class="py-3 px-4 font-medium text-slate-700">${f.employee_name || f.user_name || 'Agent'}</td>
        <td class="py-3 px-4"><span class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800">${f.call_status}</span></td>
        <td class="py-3 px-4 max-w-xs truncate">${f.remark || 'No remark logged'}</td>
        <td class="py-3 px-4 font-mono font-bold text-amber-700">${f.next_follow_up_date || 'N/A'}</td>
      </tr>
    `).join('');
  } catch(e){}
}
loadFollowUps();
</script>
<?php
});

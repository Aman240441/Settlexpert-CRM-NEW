<?php
// php-app/pages/manager/payments.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Payment Queue & Fees Control', 'payments', function() {
?>
<div class="max-w-7xl mx-auto space-y-6">
  <!-- Nav Tabs -->
  <div class="flex border-b border-slate-200">
    <button onclick="switchTab('queue')" id="tabBtnQueue" class="px-4 py-2.5 text-xs font-bold border-b-2 border-amber-500 text-amber-600">
      Verification Queue
    </button>
    <button onclick="switchTab('clients')" id="tabBtnClients" class="px-4 py-2.5 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
      Client Fee Schedules
    </button>
  </div>

  <!-- Tab 1: Verification Queue -->
  <div id="tabQueue" class="space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <table class="w-full text-left text-xs text-slate-600">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Receipt #</th>
            <th class="py-3 px-4">Client Name</th>
            <th class="py-3 px-4">Amount</th>
            <th class="py-3 px-4">Method / Trans ID</th>
            <th class="py-3 px-4">Date</th>
            <th class="py-3 px-4">Status</th>
            <th class="py-3 px-4 text-right">Actions</th>
          </tr>
        </thead>
        <tbody id="queueTableBody" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading verification queue...</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tab 2: Client Fee Schedules -->
  <div id="tabClients" class="hidden space-y-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
      <table class="w-full text-left text-xs text-slate-600">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Client #</th>
            <th class="py-3 px-4">Client Name</th>
            <th class="py-3 px-4">Monthly Fee</th>
            <th class="py-3 px-4">Total Agreement</th>
            <th class="py-3 px-4">Total Received</th>
            <th class="py-3 px-4">Pending</th>
            <th class="py-3 px-4">Status</th>
          </tr>
        </thead>
        <tbody id="feeTableBody" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading fee schedules...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function switchTab(t) {
  if (t === 'queue') {
    document.getElementById('tabQueue').classList.remove('hidden');
    document.getElementById('tabClients').classList.add('hidden');
    document.getElementById('tabBtnQueue').className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-amber-500 text-amber-600';
    document.getElementById('tabBtnClients').className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800';
    loadQueue();
  } else {
    document.getElementById('tabQueue').classList.add('hidden');
    document.getElementById('tabClients').classList.remove('hidden');
    document.getElementById('tabBtnClients').className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-amber-500 text-amber-600';
    document.getElementById('tabBtnQueue').className = 'px-4 py-2.5 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800';
    loadFeeSchedules();
  }
}

async function loadQueue() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/payments.php?action=queue', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const payments = d.payments || [];

    const tbody = document.getElementById('queueTableBody');
    if (!payments.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No pending payments in queue.</td></tr>';
      return;
    }

    tbody.innerHTML = payments.map(p => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-mono font-bold text-slate-800">${p.receipt_number || 'REC-XXXX'}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${p.client_name || 'N/A'}<br><span class="text-[11px] text-slate-400 font-normal">${p.client_number || ''}</span></td>
        <td class="py-3 px-4 font-bold text-emerald-600">₹${Number(p.amount || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4">${p.payment_method}<br><span class="text-[11px] text-slate-400">${p.transaction_id || 'N/A'}</span></td>
        <td class="py-3 px-4 text-slate-500">${p.payment_date || p.created_at}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${p.status === 'verified' ? 'bg-emerald-100 text-emerald-800' : p.status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800'}">
            ${p.status}
          </span>
        </td>
        <td class="py-3 px-4 text-right space-x-1">
          ${p.status === 'pending_verification' ? `
            <button onclick="handleVerifyPayment('${p.id}', 'verified')" class="px-2.5 py-1 text-[11px] bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition">
              Approve
            </button>
            <button onclick="handleVerifyPayment('${p.id}', 'rejected')" class="px-2.5 py-1 text-[11px] bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg transition">
              Reject
            </button>
          ` : '<span class="text-slate-400 text-[11px]">Processed</span>'}
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

async function handleVerifyPayment(id, status) {
  if (!confirm(`Confirm marking payment as ${status}?`)) return;
  const token = localStorage.getItem('token');
  const res = await fetch(`../../api/manager/payments.php?action=verify`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ payment_id: id, status })
  });
  const d = await res.json();
  if (res.ok) {
    alert(d.message);
    loadQueue();
  } else {
    alert(d.error || 'Failed to update payment');
  }
}

async function loadFeeSchedules() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/payments.php?action=clients', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const clients = d.clients || [];

    const tbody = document.getElementById('feeTableBody');
    if (!clients.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No client fee schedules found.</td></tr>';
      return;
    }

    tbody.innerHTML = clients.map(c => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${c.client_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${c.name}</td>
        <td class="py-3 px-4 font-semibold">₹${Number(c.monthly_fee || 0).toLocaleString('en-IN')} / mo</td>
        <td class="py-3 px-4 font-bold text-indigo-700">₹${Number(c.total_agreement_fee || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-bold text-emerald-600">₹${Number(c.total_received || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4 font-bold text-amber-600">₹${Number(c.pending_amount || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${c.fees_status === 'Paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'}">
            ${c.fees_status}
          </span>
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

loadQueue();
</script>
<?php
});

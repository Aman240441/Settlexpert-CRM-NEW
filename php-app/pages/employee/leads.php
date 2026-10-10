<?php
// php-app/pages/employee/leads.php
require_once __DIR__ . '/layout.php';

render_employee_layout('Leads Management', 'leads', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <!-- Controls Bar: Search, Status Filter & Add Button -->
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
    <div class="flex items-center gap-2 flex-1 min-w-[260px]">
      <div class="relative w-full max-w-sm">
        <i data-lucide="search" class="w-4 h-4 text-slate-400 absolute left-3 top-2.5"></i>
        <input type="text" id="searchInput" oninput="loadLeads()" placeholder="Search by name, phone, lead ID..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-indigo-500">
      </div>
      <select id="statusFilter" onchange="loadLeads()" class="text-xs bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:border-indigo-500">
        <option value="all">All Stages</option>
        <option value="new">New</option>
        <option value="contacted">Contacted</option>
        <option value="interested">Interested</option>
        <option value="in_progress">In Progress</option>
        <option value="converted">Converted</option>
        <option value="dropped">Dropped</option>
      </select>
    </div>

    <button onclick="openAddLeadModal()" class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-xs transition">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Add Lead
    </button>
  </div>

  <!-- Leads Table -->
  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <div class="overflow-x-auto">
      <table class="w-full text-left text-xs text-slate-600">
        <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
          <tr>
            <th class="py-3 px-4">Lead #</th>
            <th class="py-3 px-4">Client Name</th>
            <th class="py-3 px-4">Phone / City</th>
            <th class="py-3 px-4">Total Debt</th>
            <th class="py-3 px-4">Stage</th>
            <th class="py-3 px-4">Created</th>
            <th class="py-3 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody id="leadsTableBody" class="divide-y divide-slate-100">
          <tr><td colspan="7" class="py-8 text-center text-slate-400">Loading leads...</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal: Add New Lead -->
<div id="addLeadModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-lg w-full p-6 shadow-xl border border-slate-200">
    <div class="flex items-center justify-between border-b pb-3 mb-4">
      <h3 class="font-bold text-slate-800 text-sm">Add New Lead</h3>
      <button onclick="closeAddLeadModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form id="addLeadForm" onsubmit="handleAddLead(event)" class="space-y-3 text-xs">
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="font-medium text-slate-700 block mb-1">Full Name *</label>
          <input type="text" name="name" required class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
        <div>
          <label class="font-medium text-slate-700 block mb-1">Phone Number *</label>
          <input type="text" name="phone" required placeholder="+91 9876543210" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="font-medium text-slate-700 block mb-1">Email Address</label>
          <input type="email" name="email" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
        <div>
          <label class="font-medium text-slate-700 block mb-1">City</label>
          <input type="text" name="city" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="font-medium text-slate-700 block mb-1">Total Outstanding Debt (₹)</label>
          <input type="number" name="total_debt" placeholder="500000" class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
        <div>
          <label class="font-medium text-slate-700 block mb-1">Primary Bank Name</label>
          <input type="text" name="bank_name" placeholder="HDFC, ICICI, SBI..." class="w-full border rounded-lg p-2 focus:border-indigo-500 focus:outline-none">
        </div>
      </div>
      <div class="pt-3 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAddLeadModal()" class="px-3 py-1.5 border rounded-lg hover:bg-slate-50">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold">Save Lead</button>
      </div>
    </form>
  </div>
</div>

<!-- Drawer: Lead Details & Status Change -->
<div id="leadDrawer" class="hidden fixed inset-y-0 right-0 w-full max-w-md bg-white shadow-2xl z-50 flex flex-col border-l border-slate-200">
  <div class="p-4 border-b flex items-center justify-between bg-slate-50">
    <div>
      <h3 class="font-bold text-slate-800 text-sm" id="dLeadNumber">LEAD-0000</h3>
      <p class="text-xs text-slate-500" id="dLeadName">Client Name</p>
    </div>
    <button onclick="closeLeadDrawer()" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
  </div>
  <div class="flex-1 overflow-y-auto p-5 space-y-4 text-xs" id="dLeadContent">
    <!-- Content injected -->
  </div>
</div>

<script>
let currentLeads = [];
let activeDrawerLeadId = null;

async function loadLeads() {
  const token = localStorage.getItem('token');
  const search = document.getElementById('searchInput').value;
  const status = document.getElementById('statusFilter').value;

  try {
    const res = await fetch(`../../api/employee/leads.php?status=${status}&search=${encodeURIComponent(search)}`, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    currentLeads = d.leads || [];

    const tbody = document.getElementById('leadsTableBody');
    if (!currentLeads.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-slate-400">No leads found matching criteria.</td></tr>';
      return;
    }

    tbody.innerHTML = currentLeads.map(l => `
      <tr class="hover:bg-slate-50 transition cursor-pointer" onclick="openLeadDrawer('${l.id}')">
        <td class="py-3 px-4 font-bold text-indigo-600">${l.lead_number}</td>
        <td class="py-3 px-4 font-semibold text-slate-800">${l.name}</td>
        <td class="py-3 px-4">${l.phone}<br><span class="text-[11px] text-slate-400">${l.city || 'N/A'}</span></td>
        <td class="py-3 px-4 font-semibold">₹${Number(l.total_debt || 0).toLocaleString('en-IN')}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${getStatusBadge(l.status)}">
            ${l.status}
          </span>
        </td>
        <td class="py-3 px-4 text-slate-400 text-[11px]">${(l.created_at || '').substring(0, 10)}</td>
        <td class="py-3 px-4 text-right">
          <button class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]" onclick="event.stopPropagation(); openLeadDrawer('${l.id}')">
            View &rarr;
          </button>
        </td>
      </tr>
    `).join('');
  } catch (e) {
    console.error('Error loading leads:', e);
  }
}

function getStatusBadge(st) {
  switch (st) {
    case 'new': return 'bg-blue-100 text-blue-800';
    case 'contacted': return 'bg-amber-100 text-amber-800';
    case 'interested': return 'bg-indigo-100 text-indigo-800';
    case 'in_progress': return 'bg-purple-100 text-purple-800';
    case 'converted': return 'bg-emerald-100 text-emerald-800';
    case 'dropped': return 'bg-rose-100 text-rose-800';
    default: return 'bg-slate-100 text-slate-800';
  }
}

function openAddLeadModal() { document.getElementById('addLeadModal').classList.remove('hidden'); }
function closeAddLeadModal() { document.getElementById('addLeadModal').classList.add('hidden'); }

async function handleAddLead(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/employee/leads.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  if (res.ok) {
    closeAddLeadModal();
    e.target.reset();
    loadLeads();
  } else {
    alert('Failed to save lead');
  }
}

async function openLeadDrawer(id) {
  activeDrawerLeadId = id;
  const token = localStorage.getItem('token');
  const res = await fetch(`../../api/employee/leads.php?id=${id}`, {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  const data = await res.json();
  const l = data.lead;
  if (!l) return;

  document.getElementById('dLeadNumber').textContent = l.lead_number;
  document.getElementById('dLeadName').textContent = l.name;

  const content = document.getElementById('dLeadContent');
  content.innerHTML = `
    <div class="space-y-3 bg-slate-50 p-3.5 rounded-lg border border-slate-200">
      <div class="flex justify-between">
        <span class="text-slate-500">Phone:</span>
        <span class="font-semibold text-slate-800">${l.phone}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-500">Email:</span>
        <span>${l.email || 'N/A'}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-500">Total Debt:</span>
        <span class="font-bold text-slate-800">₹${Number(l.total_debt || 0).toLocaleString('en-IN')}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-slate-500">Primary Bank:</span>
        <span>${l.bank_name || 'N/A'}</span>
      </div>
    </div>

    <!-- Status Change Form -->
    <div class="border-t pt-4 space-y-3">
      <h4 class="font-bold text-slate-700">Update Lead Stage</h4>
      <select id="updateStatusSelect" class="w-full border rounded-lg p-2 text-xs">
        <option value="new" ${l.status === 'new' ? 'selected' : ''}>New</option>
        <option value="contacted" ${l.status === 'contacted' ? 'selected' : ''}>Contacted</option>
        <option value="interested" ${l.status === 'interested' ? 'selected' : ''}>Interested</option>
        <option value="in_progress" ${l.status === 'in_progress' ? 'selected' : ''}>In Progress</option>
        <option value="dropped" ${l.status === 'dropped' ? 'selected' : ''}>Dropped</option>
      </select>
      <textarea id="updateRemark" placeholder="Add remark for this follow-up..." class="w-full border rounded-lg p-2 text-xs" rows="2"></textarea>
      <button onclick="handleStatusUpdate()" class="w-full py-1.5 bg-slate-800 text-white font-semibold rounded-lg hover:bg-slate-900 transition">
        Update Stage & Log Call
      </button>
    </div>

    <!-- Convert to Client Action -->
    ${l.status !== 'converted' ? `
      <div class="border-t pt-4">
        <button onclick="handleConvertToClient('${l.id}')" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-lg transition shadow-xs">
          Convert to Client (CL-XXXX) &rarr;
        </button>
      </div>
    ` : '<div class="p-3 bg-emerald-50 text-emerald-800 rounded-lg text-center font-bold">Already Converted to Client</div>'}
  `;

  document.getElementById('leadDrawer').classList.remove('hidden');
}

function closeLeadDrawer() { document.getElementById('leadDrawer').classList.add('hidden'); }

async function handleStatusUpdate() {
  const token = localStorage.getItem('token');
  const status = document.getElementById('updateStatusSelect').value;
  const remark = document.getElementById('updateRemark').value;

  const res = await fetch('../../api/employee/lead_status.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ lead_id: activeDrawerLeadId, status, remark })
  });

  if (res.ok) {
    loadLeads();
    openLeadDrawer(activeDrawerLeadId);
  } else {
    alert('Failed to update status');
  }
}

async function handleConvertToClient(leadId) {
  if (!confirm('Are you sure you want to convert this lead to an active client?')) return;
  const token = localStorage.getItem('token');
  const res = await fetch('../../api/employee/lead_convert.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ lead_id: leadId })
  });

  const data = await res.json();
  if (res.ok) {
    alert(`Success: Converted to client ${data.client_number}`);
    closeLeadDrawer();
    loadLeads();
  } else {
    alert(data.error || 'Conversion failed');
  }
}

loadLeads();
</script>
<?php
});

<?php
// php-app/pages/manager/tasks.php
require_once __DIR__ . '/layout.php';

render_manager_layout('Task Delegation & Ops', 'tasks', function() {
?>
<div class="max-w-7xl mx-auto space-y-5">
  <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex justify-between items-center">
    <div>
      <h3 class="text-sm font-bold text-slate-800">Assigned Team Tasks</h3>
      <p class="text-xs text-slate-400">Track operations, escalations and daily task progress</p>
    </div>
    <button onclick="openAddTaskModal()" class="px-3.5 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg text-xs transition shadow-xs flex items-center gap-1.5">
      <i data-lucide="plus" class="w-3.5 h-3.5"></i> Assign Task
    </button>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
    <table class="w-full text-left text-xs text-slate-600">
      <thead class="bg-slate-50 text-slate-500 uppercase tracking-wider text-[11px] border-b border-slate-200">
        <tr>
          <th class="py-3 px-4">Task Title</th>
          <th class="py-3 px-4">Module</th>
          <th class="py-3 px-4">Assigned Employee</th>
          <th class="py-3 px-4">Priority</th>
          <th class="py-3 px-4">Due Date</th>
          <th class="py-3 px-4">Status</th>
        </tr>
      </thead>
      <tbody id="tasksTableBody" class="divide-y divide-slate-100">
        <tr><td colspan="6" class="py-8 text-center text-slate-400">Loading tasks...</td></tr>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal: Assign Task -->
<div id="addTaskModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center z-50 p-4">
  <div class="bg-white rounded-xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-3">
    <div class="flex justify-between items-center border-b pb-2">
      <h4 class="font-bold text-slate-800 text-sm">Assign New Task</h4>
      <button onclick="closeAddTaskModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
    </div>
    <form onsubmit="handleCreateTask(event)" class="space-y-3 text-xs">
      <div>
        <label class="block font-medium text-slate-700 mb-1">Task Title *</label>
        <input type="text" name="title" required placeholder="e.g. Call client regarding bank notice..." class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Detailed Description</label>
        <textarea name="description" rows="2" placeholder="Specific instructions..." class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none"></textarea>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div>
          <label class="block font-medium text-slate-700 mb-1">Assign To Employee *</label>
          <select id="taskEmpSelect" name="assigned_to" required class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
            <option value="">Select team member...</option>
          </select>
        </div>
        <div>
          <label class="block font-medium text-slate-700 mb-1">Priority</label>
          <select name="priority" class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
            <option value="urgent">Urgent</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block font-medium text-slate-700 mb-1">Due Date</label>
        <input type="date" name="due_date" class="w-full border rounded-lg p-2 text-xs focus:border-amber-500 focus:outline-none">
      </div>
      <div class="pt-2 flex justify-end gap-2 border-t">
        <button type="button" onclick="closeAddTaskModal()" class="px-3 py-1.5 border rounded-lg">Cancel</button>
        <button type="submit" class="px-4 py-1.5 bg-amber-500 hover:bg-amber-600 text-slate-950 font-bold rounded-lg">Assign Task</button>
      </div>
    </form>
  </div>
</div>

<script>
async function initTasks() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/team.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const select = document.getElementById('taskEmpSelect');
    (d.employees || []).forEach(e => {
      select.innerHTML += `<option value="${e.id}">${e.name} (${e.emp_or_mgr_id || 'EMP'})</option>`;
    });
  } catch(e){}
  loadTasks();
}

async function loadTasks() {
  const token = localStorage.getItem('token');
  try {
    const res = await fetch('../../api/manager/tasks.php', {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    const d = await res.json();
    const tasks = d.tasks || [];

    const tbody = document.getElementById('tasksTableBody');
    if (!tasks.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-slate-400">No active tasks created.</td></tr>';
      return;
    }

    tbody.innerHTML = tasks.map(t => `
      <tr class="hover:bg-slate-50 transition">
        <td class="py-3 px-4 font-bold text-slate-800">${t.title}<br><span class="text-[11px] text-slate-400 font-normal">${t.description || ''}</span></td>
        <td class="py-3 px-4 font-semibold text-slate-600">${t.module || 'General'}</td>
        <td class="py-3 px-4 font-medium text-slate-700">${t.assigned_to_name || 'Unassigned'}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${t.priority === 'urgent' ? 'bg-rose-100 text-rose-800' : t.priority === 'high' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700'}">
            ${t.priority}
          </span>
        </td>
        <td class="py-3 px-4 font-mono text-[11px] text-slate-500">${t.due_date || 'No deadline'}</td>
        <td class="py-3 px-4">
          <span class="px-2 py-0.5 rounded text-[10px] font-bold ${t.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800'}">
            ${t.status}
          </span>
        </td>
      </tr>
    `).join('');
  } catch(e){}
}

function openAddTaskModal() { document.getElementById('addTaskModal').classList.remove('hidden'); }
function closeAddTaskModal() { document.getElementById('addTaskModal').classList.add('hidden'); }

async function handleCreateTask(e) {
  e.preventDefault();
  const token = localStorage.getItem('token');
  const formData = new FormData(e.target);
  const data = Object.fromEntries(formData.entries());

  const res = await fetch('../../api/manager/tasks.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify(data)
  });

  if (res.ok) {
    alert('Task queued and assigned successfully');
    closeAddTaskModal();
    e.target.reset();
    loadTasks();
  } else {
    alert('Failed to create task');
  }
}

initTasks();
</script>
<?php
});

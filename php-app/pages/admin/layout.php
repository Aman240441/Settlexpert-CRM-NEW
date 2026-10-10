<?php
// php-app/pages/admin/layout.php
function render_admin_layout(string $title, string $activePage, callable $contentRenderer) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> | SettleXpert Master Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  </style>
</head>
<body class="bg-[#F8FAFC] text-[#334155] min-h-screen flex flex-col antialiased">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-[#090D16] text-slate-300 flex flex-col shrink-0 border-r border-slate-800">
      <!-- Brand Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-black/40">
        <span class="text-xl font-bold text-white tracking-wide flex items-center gap-2">
          <i data-lucide="shield-check" class="w-6 h-6 text-rose-500"></i>
          Master<span class="text-rose-500">Admin</span>
        </span>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Analytics</p>
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'dashboard' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="gauge" class="w-4 h-4"></i> Executive Dashboard
        </a>

        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mt-4 mb-1">Organization Structure</p>
        <a href="departments.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'departments' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="building-2" class="w-4 h-4"></i> Departments
        </a>
        <a href="teams.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'teams' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="network" class="w-4 h-4"></i> Teams
        </a>
        <a href="managers.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'managers' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="briefcase" class="w-4 h-4"></i> Managers Roster
        </a>
        <a href="manager_types.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'manager_types' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="sliders" class="w-4 h-4"></i> Manager Roles & Types
        </a>
        <a href="employees.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'employees' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="users" class="w-4 h-4"></i> Employees Master
        </a>

        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-500 mt-4 mb-1">System & Finance</p>
        <a href="fee_plans.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'fee_plans' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="receipt" class="w-4 h-4"></i> Fee Plans
        </a>
        <a href="audit.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'audit' ? 'bg-rose-600 text-white shadow-sm' : 'hover:bg-slate-900 text-slate-300' ?>">
          <i data-lucide="clipboard-list" class="w-4 h-4"></i> Audit Trail
        </a>
      </nav>

      <!-- User footer -->
      <div class="p-4 border-t border-slate-800 bg-black/50 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-full bg-rose-600 text-white flex items-center justify-center font-bold text-xs" id="admInitial">A</div>
          <div class="text-xs truncate max-w-[120px]">
            <p class="font-semibold text-white truncate" id="admName">Admin</p>
            <p class="text-slate-400 capitalize">Super Admin</p>
          </div>
        </div>
        <button onclick="handleLogout()" class="p-1.5 text-slate-400 hover:text-rose-400 transition" title="Logout">
          <i data-lucide="log-out" class="w-4 h-4"></i>
        </button>
      </div>
    </aside>

    <!-- Main Content wrapper -->
    <div class="flex-1 flex flex-col overflow-hidden">
      <!-- Top header bar -->
      <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-6 shrink-0">
        <h1 class="text-lg font-semibold text-slate-800"><?= htmlspecialchars($title) ?></h1>
        <div class="flex items-center gap-3">
          <span class="text-xs bg-rose-50 text-rose-800 font-bold px-2.5 py-1 rounded-full border border-rose-200 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span>
            Super Administrator Session
          </span>
        </div>
      </header>

      <!-- Body view -->
      <main class="flex-1 overflow-y-auto p-6">
        <?php $contentRenderer(); ?>
      </main>
    </div>
  </div>

  <script>
    const userJson = localStorage.getItem('user');
    if (userJson) {
      try {
        const u = JSON.parse(userJson);
        document.getElementById('admName').textContent = u.name || 'Admin';
        document.getElementById('admInitial').textContent = (u.name || 'A')[0].toUpperCase();
      } catch(e){}
    }

    function handleLogout() {
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      window.location.href = '../auth/login.php';
    }

    lucide.createIcons();
  </script>
</body>
</html>
<?php
}

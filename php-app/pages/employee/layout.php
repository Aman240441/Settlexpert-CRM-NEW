<?php
// php-app/pages/employee/layout.php
function render_employee_layout(string $title, string $activePage, callable $contentRenderer) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> | SettleXpert CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  </style>
</head>
<body class="bg-[#F8FAFC] text-[#334155] min-h-screen flex flex-col antialiased">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-[#1E293B] text-slate-300 flex flex-col shrink-0 border-r border-[#334155]">
      <!-- Brand Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-700 bg-slate-900/40">
        <span class="text-xl font-bold text-white tracking-wide flex items-center gap-2">
          <i data-lucide="shield-check" class="w-6 h-6 text-indigo-400"></i>
          Settle<span class="text-indigo-400">Xpert</span>
        </span>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition <?= $activePage === 'dashboard' ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="gauge" class="w-4 h-4"></i> Dashboard
        </a>
        <a href="leads.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition <?= $activePage === 'leads' ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="layers" class="w-4 h-4"></i> Leads Pipeline
        </a>
        <a href="clients.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition <?= $activePage === 'clients' ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="user-check" class="w-4 h-4"></i> Active Clients
        </a>
        <a href="agreements.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition <?= $activePage === 'agreements' ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="file-signature" class="w-4 h-4"></i> Fee Agreements
        </a>
        <a href="lead_import.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm font-medium transition <?= $activePage === 'lead_import' ? 'bg-indigo-600 text-white shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="file-spreadsheet" class="w-4 h-4"></i> Import & Distribution
        </a>
      </nav>

      <!-- User footer -->
      <div class="p-4 border-t border-slate-700 bg-slate-900/30 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-full bg-indigo-500 text-white flex items-center justify-center font-bold text-xs" id="userInitial">U</div>
          <div class="text-xs truncate max-w-[120px]">
            <p class="font-semibold text-white truncate" id="userName">User</p>
            <p class="text-slate-400 capitalize" id="userRole">Employee</p>
          </div>
        </div>
        <button onclick="handleLogout()" class="p-1.5 text-slate-400 hover:text-red-400 transition" title="Logout">
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
          <span class="text-xs bg-indigo-50 text-indigo-700 font-medium px-2.5 py-1 rounded-full border border-indigo-100 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            Live CRM Connected
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
    // JWT auth check & user header population
    const token = localStorage.getItem('token');
    const userJson = localStorage.getItem('user');
    if (!token && !window.location.pathname.includes('login')) {
      // Auto-fallback for testing: if no token, create a demo token
    }
    if (userJson) {
      try {
        const u = JSON.parse(userJson);
        document.getElementById('userName').textContent = u.name || 'User';
        document.getElementById('userRole').textContent = u.role || 'Employee';
        document.getElementById('userInitial').textContent = (u.name || 'U')[0].toUpperCase();
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

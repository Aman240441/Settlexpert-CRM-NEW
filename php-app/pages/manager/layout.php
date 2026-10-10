<?php
// php-app/pages/manager/layout.php
function render_manager_layout(string $title, string $activePage, callable $contentRenderer) {
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title) ?> | SettleXpert Manager Control</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  </style>
</head>
<body class="bg-[#F8FAFC] text-[#334155] min-h-screen flex flex-col antialiased">
  <div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <aside class="w-64 bg-[#0F172A] text-slate-300 flex flex-col shrink-0 border-r border-slate-800">
      <!-- Brand Logo -->
      <div class="h-16 flex items-center px-6 border-b border-slate-800 bg-slate-950/50">
        <span class="text-xl font-bold text-white tracking-wide flex items-center gap-2">
          <i data-lucide="shield-alert" class="w-6 h-6 text-amber-400"></i>
          Manager<span class="text-amber-400">Hub</span>
        </span>
      </div>

      <!-- Navigation Links -->
      <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
        <a href="dashboard.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'dashboard' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="gauge" class="w-4 h-4"></i> Overview
        </a>
        <a href="team.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'team' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="users" class="w-4 h-4"></i> Team Directory
        </a>
        <a href="leads.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'leads' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="layers" class="w-4 h-4"></i> Team Leads
        </a>
        <a href="clients.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'clients' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="user-check" class="w-4 h-4"></i> Team Clients
        </a>
        <a href="payments.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'payments' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="banknote" class="w-4 h-4"></i> Payment Queue & Fees
        </a>
        <a href="advocates.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'advocates' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="scale" class="w-4 h-4"></i> Advocates Network
        </a>
        <a href="follow_ups.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'follow_ups' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="phone-call" class="w-4 h-4"></i> Team Follow-ups
        </a>
        <a href="tasks.php" class="flex items-center gap-3 px-3.5 py-2 rounded-lg text-xs font-semibold transition <?= $activePage === 'tasks' ? 'bg-amber-500 text-slate-950 shadow-sm' : 'hover:bg-slate-800 text-slate-300' ?>">
          <i data-lucide="check-square" class="w-4 h-4"></i> Tasks & Delegation
        </a>
      </nav>

      <!-- User footer -->
      <div class="p-4 border-t border-slate-800 bg-slate-950/40 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-full bg-amber-500 text-slate-950 flex items-center justify-center font-bold text-xs" id="mgrInitial">M</div>
          <div class="text-xs truncate max-w-[120px]">
            <p class="font-semibold text-white truncate" id="mgrName">Manager</p>
            <p class="text-slate-400 capitalize" id="mgrRole">Manager</p>
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
          <span class="text-xs bg-amber-50 text-amber-800 font-semibold px-2.5 py-1 rounded-full border border-amber-200 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            Manager Privilege Active
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
        document.getElementById('mgrName').textContent = u.name || 'Manager';
        document.getElementById('mgrRole').textContent = u.role || 'Manager';
        document.getElementById('mgrInitial').textContent = (u.name || 'M')[0].toUpperCase();
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

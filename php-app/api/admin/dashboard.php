<?php
// php-app/api/admin/dashboard.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_admin($authUser);

try {
    // 1. Organization & CRM Totals
    $totalManagers = (int)db()->query("SELECT COUNT(*) as c FROM users WHERE role = 'manager'")->fetch()['c'];
    $totalEmployees = (int)db()->query("SELECT COUNT(*) as c FROM users WHERE role = 'employee'")->fetch()['c'];
    $totalAdvocates = (int)db()->query("SELECT COUNT(*) as c FROM advocates")->fetch()['c'];
    $totalLeads = (int)db()->query("SELECT COUNT(*) as c FROM leads")->fetch()['c'];
    $totalClients = (int)db()->query("SELECT COUNT(*) as c FROM clients")->fetch()['c'];
    $totalAgreements = (int)db()->query("SELECT COUNT(*) as c FROM agreements")->fetch()['c'];

    // 2. Finance Highlights
    $totalFees = (float)db()->query("SELECT COALESCE(SUM(total_fee), 0) as total FROM agreements")->fetch()['total'];
    $totalReceived = (float)db()->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'verified'")->fetch()['total'];
    $pendingVerificationCount = (int)db()->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending_verification'")->fetch()['c'];
    $pendingVerificationAmt = (float)db()->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'pending_verification'")->fetch()['total'];
    $totalPending = max(0, $totalFees - $totalReceived);

    // 3. Operations Highlights
    $pendingTasks = (int)db()->query("SELECT COUNT(*) as c FROM tasks WHERE status != 'completed'")->fetch()['c'];

    // 4. Manager Performance Overview
    $managerOverview = db()->query("
        SELECT 
            u.id, 
            u.name, 
            u.emp_or_mgr_id, 
            u.status,
            COALESCE(mt.name, 'General') as manager_type,
            COALESCE(d.name, 'Unassigned') as department_name,
            (SELECT COUNT(*) FROM users e WHERE e.manager_id = u.id AND e.role = 'employee') as employee_count,
            (SELECT COUNT(*) FROM leads l WHERE l.manager_id = u.id) as lead_count,
            (SELECT COUNT(*) FROM clients c WHERE c.manager_id = u.id) as client_count
        FROM users u
        LEFT JOIN manager_types mt ON u.manager_type_id = mt.id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.role = 'manager'
        ORDER BY employee_count DESC, u.name ASC
    ")->fetchAll();

    // 5. Recent Leads
    $recentLeads = db()->query("
        SELECT l.id, l.lead_number, l.name, l.phone, l.status, l.created_at,
               u.name as manager_name
        FROM leads l
        LEFT JOIN users u ON l.manager_id = u.id
        ORDER BY l.created_at DESC LIMIT 5
    ")->fetchAll();

    // 6. Recent Clients
    $recentClients = db()->query("
        SELECT c.id, c.client_number, c.name, c.phone, c.total_debt, c.status, c.created_at,
               u.name as manager_name
        FROM clients c
        LEFT JOIN users u ON c.manager_id = u.id
        ORDER BY c.created_at DESC LIMIT 5
    ")->fetchAll();

    echo json_encode([
        'stats' => [
            'totalManagers'            => $totalManagers,
            'totalEmployees'           => $totalEmployees,
            'totalAdvocates'           => $totalAdvocates,
            'totalLeads'               => $totalLeads,
            'totalClients'             => $totalClients,
            'totalAgreements'          => $totalAgreements,
            'totalFees'                => $totalFees,
            'totalReceived'            => $totalReceived,
            'totalPending'             => $totalPending,
            'pendingVerificationCount' => $pendingVerificationCount,
            'pendingVerificationAmt'   => $pendingVerificationAmt,
            'pendingTasks'             => $pendingTasks
        ],
        'managerOverview' => $managerOverview,
        'recentLeads'     => $recentLeads,
        'recentClients'   => $recentClients
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load admin stats: ' . $e->getMessage()]);
}

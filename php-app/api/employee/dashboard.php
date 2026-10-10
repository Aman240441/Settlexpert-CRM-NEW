<?php
// php-app/api/employee/dashboard.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_employee_or_admin($authUser);

$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

try {
    // 1. Leads Count
    if ($isScoped) {
        $stmtLead = db()->prepare('SELECT COUNT(*) as c FROM leads WHERE (employee_id = ? OR manager_id = ?)');
        $stmtLead->execute([$empId, $empId]);
    } else {
        $stmtLead = db()->query('SELECT COUNT(*) as c FROM leads');
    }
    $totalLeads = (int)$stmtLead->fetch()['c'];

    // 2. Clients Count
    if ($isScoped) {
        $stmtClient = db()->prepare('SELECT COUNT(*) as c FROM clients WHERE (employee_id = ? OR manager_id = ?)');
        $stmtClient->execute([$empId, $empId]);
        $totalClients = (int)$stmtClient->fetch()['c'];

        $stmtAct = db()->prepare("SELECT COUNT(*) as c FROM clients WHERE (employee_id = ? OR manager_id = ?) AND (case_status = 'active' OR status = 'active')");
        $stmtAct->execute([$empId, $empId]);
        $activeClients = (int)$stmtAct->fetch()['c'];

        $stmtDrop = db()->prepare("SELECT COUNT(*) as c FROM clients WHERE (employee_id = ? OR manager_id = ?) AND (case_status = 'dropped' OR status = 'dropped')");
        $stmtDrop->execute([$empId, $empId]);
        $droppedClients = (int)$stmtDrop->fetch()['c'];
    } else {
        $totalClients = (int)db()->query('SELECT COUNT(*) as c FROM clients')->fetch()['c'];
        $activeClients = (int)db()->query("SELECT COUNT(*) as c FROM clients WHERE (case_status = 'active' OR status = 'active')")->fetch()['c'];
        $droppedClients = (int)db()->query("SELECT COUNT(*) as c FROM clients WHERE (case_status = 'dropped' OR status = 'dropped')")->fetch()['c'];
    }

    $conversionRate = $totalLeads > 0 ? (int)round(($totalClients / $totalLeads) * 100) : 0;

    // 3. Financial Metrics
    $currentMonth = date('Y-m');
    if ($isScoped) {
        $stmtColl = db()->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as total
            FROM payments p
            LEFT JOIN clients c ON p.client_id = c.id
            WHERE p.status = 'verified' AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?
              AND (c.employee_id = ? OR c.manager_id = ?)
        ");
        $stmtColl->execute([$currentMonth, $empId, $empId]);
        $collection = (float)$stmtColl->fetch()['total'];

        $stmtExp = db()->prepare("
            SELECT COALESCE(SUM(c.sx_fee), 0) as total
            FROM clients c
            WHERE (case_status = 'active' OR status = 'active')
              AND (c.employee_id = ? OR c.manager_id = ?)
        ");
        $stmtExp->execute([$empId, $empId]);
        $expected = (float)$stmtExp->fetch()['total'];
    } else {
        $stmtColl = db()->prepare("
            SELECT COALESCE(SUM(p.amount), 0) as total
            FROM payments p
            WHERE p.status = 'verified' AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?
        ");
        $stmtColl->execute([$currentMonth]);
        $collection = (float)$stmtColl->fetch()['total'];

        $expected = (float)db()->query("SELECT COALESCE(SUM(sx_fee), 0) as total FROM clients WHERE (case_status = 'active' OR status = 'active')")->fetch()['total'];
    }

    $pending = max(0, $expected - $collection);

    // 4. Lead Pipeline Breakdown
    $statusMap = [
        'new' => 0, 'contacted' => 0, 'interested' => 0,
        'in_progress' => 0, 'converted' => 0, 'dropped' => 0, 'unassigned' => 0
    ];

    if ($isScoped) {
        $stmtPipe = db()->prepare('SELECT status, COUNT(*) as c FROM leads WHERE (employee_id = ? OR manager_id = ?) GROUP BY status');
        $stmtPipe->execute([$empId, $empId]);
    } else {
        $stmtPipe = db()->query('SELECT status, COUNT(*) as c FROM leads GROUP BY status');
    }
    foreach ($stmtPipe->fetchAll() as $row) {
        if (isset($statusMap[$row['status']])) {
            $statusMap[$row['status']] = (int)$row['c'];
        }
    }

    echo json_encode([
        'total_leads'     => $totalLeads,
        'total_clients'   => $totalClients,
        'active_clients'  => $activeClients,
        'dropped_clients' => $droppedClients,
        'conversion_rate' => $conversionRate,
        'collection'      => $collection,
        'expected'        => $expected,
        'pending'         => $pending,
        'pipeline'        => $statusMap
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load employee dashboard: ' . $e->getMessage()]);
}

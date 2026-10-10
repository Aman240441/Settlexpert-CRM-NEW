<?php
// php-app/api/manager/dashboard.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

try {
    $scope = get_manager_scope($authUser);
    $empIds = $scope['employeeIds'];
    $hasEmps = count($empIds) > 0;
    $empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

    // 1. My Employees Count
    $employeesCount = count($empIds);

    // 2. Leads Count
    if ($scope['isAdmin']) {
        $myLeads = (int)db()->query('SELECT COUNT(*) as c FROM leads')->fetch()['c'];
    } else {
        $sql = "SELECT COUNT(*) as c FROM leads WHERE (manager_id = ? OR department_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ")";
        $params = array_merge([$scope['managerId'], $scope['departmentId']], $hasEmps ? $empIds : []);
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $myLeads = (int)$stmt->fetch()['c'];
    }

    // 3. Clients Count
    if ($scope['isAdmin']) {
        $myClients = (int)db()->query('SELECT COUNT(*) as c FROM clients')->fetch()['c'];
        $activeClients = (int)db()->query("SELECT COUNT(*) as c FROM clients WHERE (case_status = 'active' OR status = 'active')")->fetch()['c'];
        $unassignedAdvocates = (int)db()->query("SELECT COUNT(*) as c FROM clients WHERE (advocate_id IS NULL OR advocate_id = '') AND (case_status = 'active' OR status = 'active')")->fetch()['c'];
        $totalFees = (float)db()->query('SELECT COALESCE(SUM(sx_fee), 0) as total FROM clients')->fetch()['total'];
        $totalCollection = (float)db()->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'verified'")->fetch()['total'];
        $pendingVerification = (int)db()->query("SELECT COUNT(*) as c FROM payments WHERE status = 'pending_verification'")->fetch()['c'];
        $activeAgreements = (int)db()->query("SELECT COUNT(*) as c FROM agreements WHERE status IN ('active', 'signed')")->fetch()['c'];
    } else {
        $sqlCl = "SELECT COUNT(*) as c FROM clients WHERE (manager_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ")";
        $paramsCl = array_merge([$scope['managerId']], $hasEmps ? $empIds : []);
        $stmtCl = db()->prepare($sqlCl);
        $stmtCl->execute($paramsCl);
        $myClients = (int)$stmtCl->fetch()['c'];

        $sqlAct = "SELECT COUNT(*) as c FROM clients WHERE (case_status = 'active' OR status = 'active') AND (manager_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtAct = db()->prepare($sqlAct);
        $stmtAct->execute($paramsCl);
        $activeClients = (int)$stmtAct->fetch()['c'];

        $sqlUnadv = "SELECT COUNT(*) as c FROM clients WHERE (advocate_id IS NULL OR advocate_id = '') AND (case_status = 'active' OR status = 'active') AND (manager_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtUnadv = db()->prepare($sqlUnadv);
        $stmtUnadv->execute($paramsCl);
        $unassignedAdvocates = (int)$stmtUnadv->fetch()['c'];

        $sqlFees = "SELECT COALESCE(SUM(sx_fee), 0) as total FROM clients WHERE (manager_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtFees = db()->prepare($sqlFees);
        $stmtFees->execute($paramsCl);
        $totalFees = (float)$stmtFees->fetch()['total'];

        $sqlPay = "SELECT COALESCE(SUM(p.amount), 0) as total FROM payments p LEFT JOIN clients c ON p.client_id = c.id WHERE p.status = 'verified' AND (c.manager_id = ?" . ($hasEmps ? " OR c.employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtPay = db()->prepare($sqlPay);
        $stmtPay->execute($paramsCl);
        $totalCollection = (float)$stmtPay->fetch()['total'];

        $sqlVer = "SELECT COUNT(*) as c FROM payments p LEFT JOIN clients c ON p.client_id = c.id WHERE p.status = 'pending_verification' AND (c.manager_id = ?" . ($hasEmps ? " OR c.employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtVer = db()->prepare($sqlVer);
        $stmtVer->execute($paramsCl);
        $pendingVerification = (int)$stmtVer->fetch()['c'];

        $sqlAgr = "SELECT COUNT(*) as c FROM agreements a LEFT JOIN clients c ON a.client_id = c.id WHERE a.status IN ('active', 'signed') AND (c.manager_id = ?" . ($hasEmps ? " OR c.employee_id IN ($empPlaceholders)" : "") . ")";
        $stmtAgr = db()->prepare($sqlAgr);
        $stmtAgr->execute($paramsCl);
        $activeAgreements = (int)$stmtAgr->fetch()['c'];
    }

    $pendingCollection = max(0, $totalFees - $totalCollection);

    // 4. Tasks Count
    if ($scope['isAdmin']) {
        $pendingTasks = (int)db()->query("SELECT COUNT(*) as c FROM tasks WHERE status != 'completed'")->fetch()['c'];
    } else {
        $sqlT = "SELECT COUNT(*) as c FROM tasks WHERE status != 'completed' AND (created_by = ? " . ($hasEmps ? " OR assigned_to IN ($empPlaceholders)" : "") . ")";
        $paramsT = array_merge([$authUser['name']], $hasEmps ? $empIds : []);
        $stmtT = db()->prepare($sqlT);
        $stmtT->execute($paramsT);
        $pendingTasks = (int)$stmtT->fetch()['c'];
    }

    // 5. Advocates Count
    $advocatesCount = (int)db()->query("SELECT COUNT(*) as c FROM advocates WHERE status = 'active'")->fetch()['c'];

    // 6. Lead Pipeline
    $pipeline = ['new' => 0, 'contacted' => 0, 'interested' => 0, 'in_progress' => 0, 'converted' => 0, 'dropped' => 0];
    if ($scope['isAdmin']) {
        $pipeRows = db()->query('SELECT status, COUNT(*) as c FROM leads GROUP BY status')->fetchAll();
    } else {
        $sqlPipe = "SELECT status, COUNT(*) as c FROM leads WHERE (manager_id = ? OR department_id = ?" . ($hasEmps ? " OR employee_id IN ($empPlaceholders)" : "") . ") GROUP BY status";
        $paramsPipe = array_merge([$scope['managerId'], $scope['departmentId']], $hasEmps ? $empIds : []);
        $stmtPipe = db()->prepare($sqlPipe);
        $stmtPipe->execute($paramsPipe);
        $pipeRows = $stmtPipe->fetchAll();
    }
    foreach ($pipeRows as $pr) {
        $k = str_replace(' ', '_', strtolower($pr['status'] ?? ''));
        if (isset($pipeline[$k])) {
            $pipeline[$k] = (int)$pr['c'];
        }
    }

    echo json_encode([
        'topCards' => [
            'myEmployees'         => $employeesCount,
            'myLeads'             => $myLeads,
            'myClients'           => $myClients,
            'activeClients'       => $activeClients,
            'pendingTasks'        => $pendingTasks,
            'pendingVerification' => $pendingVerification,
            'totalCollection'     => $totalCollection,
            'pendingCollection'   => $pendingCollection,
            'totalFees'           => $totalFees,
            'advocatesCount'      => $advocatesCount,
            'unassignedAdvocates' => $unassignedAdvocates,
            'activeAgreements'    => $activeAgreements
        ],
        'pipeline'    => $pipeline,
        'managerType' => $scope['managerType']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to generate manager dashboard: ' . $e->getMessage()]);
}

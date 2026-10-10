<?php
// php-app/api/employee/leads.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_employee_or_admin($authUser);

$method = $_SERVER['REQUEST_METHOD'];
$isScoped = ($authUser['role'] !== 'admin');
$empId = $authUser['id'];

function generate_lead_number(): string {
    $stmt = db()->query("SELECT lead_number FROM leads WHERE lead_number LIKE 'LEAD-%'");
    $maxNum = 0;
    while ($row = $stmt->fetch()) {
        if (preg_match('/^LEAD-(\d+)$/', $row['lead_number'], $m)) {
            $num = (int)$m[1];
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
    }
    return 'LEAD-' . str_pad((string)($maxNum + 1), 4, '0', STR_PAD_LEFT);
}

// ── GET: List leads or single lead ──────────────────────────────────────────
if ($method === 'GET') {
    $leadId = $_GET['id'] ?? null;

    if ($leadId) {
        $sql = 'SELECT l.*, u.name as employee_name FROM leads l LEFT JOIN users u ON l.employee_id = u.id WHERE l.id = ?';
        $params = [$leadId];
        if ($isScoped) {
            $sql .= ' AND (l.employee_id = ? OR l.manager_id = ?)';
            $params[] = $empId;
            $params[] = $empId;
        }
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $lead = $stmt->fetch();

        if (!$lead) {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
            exit;
        }

        // Fetch followups
        $stmtFollow = db()->prepare('SELECT * FROM follow_ups WHERE lead_id = ? ORDER BY created_at DESC');
        $stmtFollow->execute([$leadId]);
        $lead['follow_ups'] = $stmtFollow->fetchAll();

        echo json_encode(['lead' => $lead]);
        exit;
    }

    // List with filters
    $status = $_GET['status'] ?? null;
    $search = trim($_GET['search'] ?? '');

    $sql = 'SELECT l.*, u.name as employee_name FROM leads l LEFT JOIN users u ON l.employee_id = u.id WHERE 1=1';
    $params = [];

    if ($isScoped) {
        $sql .= ' AND (l.employee_id = ? OR l.manager_id = ?)';
        $params[] = $empId;
        $params[] = $empId;
    }

    if ($status && $status !== 'all') {
        $sql .= ' AND l.status = ?';
        $params[] = $status;
    }

    if ($search !== '') {
        $sql .= ' AND (l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.lead_number LIKE ?)';
        $searchTerm = "%$search%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= ' ORDER BY l.created_at DESC';
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    echo json_encode(['leads' => $stmt->fetchAll()]);
    exit;
}

// ── POST: Create new lead ───────────────────────────────────────────────────
if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

    $name = trim($input['name'] ?? '');
    $phone = trim($input['phone'] ?? '');

    if ($name === '' || $phone === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Lead name and phone are required']);
        exit;
    }

    $id = 'lead-' . substr(bin2hex(random_bytes(8)), 0, 10);
    $leadNumber = generate_lead_number();

    $stmt = db()->prepare('
        INSERT INTO leads (
            id, lead_number, name, phone, email, city, state, total_debt, monthly_income,
            service_needed, paying_emis, harassment_calls, employment_status, employment_type,
            settlement_needed, consultation_timing, credit_card_dues, personal_loan_dues,
            service_fee, bank_name, status, employee_id, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ');

    $stmt->execute([
        $id,
        $leadNumber,
        $name,
        $phone,
        $input['email'] ?? null,
        $input['city'] ?? null,
        $input['state'] ?? null,
        (float)($input['total_debt'] ?? 0),
        (float)($input['monthly_income'] ?? 0),
        $input['service_needed'] ?? 'Debt Settlement',
        $input['paying_emis'] ?? 'Yes',
        $input['harassment_calls'] ?? 'No',
        $input['employment_status'] ?? 'Employed',
        $input['employment_type'] ?? 'Salaried',
        $input['settlement_needed'] ?? 'Yes',
        $input['consultation_timing'] ?? null,
        (float)($input['credit_card_dues'] ?? 0),
        (float)($input['personal_loan_dues'] ?? 0),
        (float)($input['service_fee'] ?? 0),
        $input['bank_name'] ?? null,
        $input['status'] ?? 'new',
        $input['employee_id'] ?? $empId,
        $authUser['name']
    ]);

    log_audit($authUser, 'Lead Created', 'Leads', $id, "Lead $leadNumber created for $name");

    echo json_encode([
        'message'     => 'Lead created successfully',
        'id'          => $id,
        'lead_number' => $leadNumber
    ]);
    exit;
}

// ── PUT: Update lead ────────────────────────────────────────────────────────
if ($method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $id = $_GET['id'] ?? $input['id'] ?? null;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID required']);
        exit;
    }

    // Verify ownership
    if ($isScoped) {
        $chk = db()->prepare('SELECT id FROM leads WHERE id = ? AND (employee_id = ? OR manager_id = ?)');
        $chk->execute([$id, $empId, $empId]);
        if (!$chk->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
    }

    $stmt = db()->prepare('
        UPDATE leads SET 
            name = COALESCE(?, name),
            phone = COALESCE(?, phone),
            email = COALESCE(?, email),
            city = COALESCE(?, city),
            state = COALESCE(?, state),
            total_debt = COALESCE(?, total_debt),
            monthly_income = COALESCE(?, monthly_income),
            service_needed = COALESCE(?, service_needed),
            paying_emis = COALESCE(?, paying_emis),
            harassment_calls = COALESCE(?, harassment_calls),
            credit_card_dues = COALESCE(?, credit_card_dues),
            personal_loan_dues = COALESCE(?, personal_loan_dues),
            bank_name = COALESCE(?, bank_name),
            updated_at = NOW()
        WHERE id = ?
    ');

    $stmt->execute([
        $input['name'] ?? null,
        $input['phone'] ?? null,
        $input['email'] ?? null,
        $input['city'] ?? null,
        $input['state'] ?? null,
        isset($input['total_debt']) ? (float)$input['total_debt'] : null,
        isset($input['monthly_income']) ? (float)$input['monthly_income'] : null,
        $input['service_needed'] ?? null,
        $input['paying_emis'] ?? null,
        $input['harassment_calls'] ?? null,
        isset($input['credit_card_dues']) ? (float)$input['credit_card_dues'] : null,
        isset($input['personal_loan_dues']) ? (float)$input['personal_loan_dues'] : null,
        $input['bank_name'] ?? null,
        $id
    ]);

    log_audit($authUser, 'Lead Updated', 'Leads', $id, "Lead $id updated");

    echo json_encode(['message' => 'Lead updated successfully']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);

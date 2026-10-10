<?php
// php-app/api/manager/tasks.php
header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/auth.php';

$authUser = authenticate_token();
require_manager_or_admin($authUser);

$scope = get_manager_scope($authUser);
$method = $_SERVER['REQUEST_METHOD'];
$empIds = $scope['employeeIds'];
$hasEmps = count($empIds) > 0;
$empPlaceholders = $hasEmps ? implode(',', array_fill(0, count($empIds), '?')) : "''";

if ($method === 'GET') {
    try {
        $sql = "
            SELECT t.*, u.name as assigned_to_name, u.emp_or_mgr_id as assigned_to_code
            FROM tasks t
            LEFT JOIN users u ON t.assigned_to = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!$scope['isAdmin']) {
            $sql .= " AND (t.created_by = ? " . ($hasEmps ? " OR t.assigned_to IN ($empPlaceholders)" : "") . ")";
            $params = array_merge([$authUser['name']], $hasEmps ? $empIds : []);
        }

        $sql .= ' ORDER BY t.created_at DESC LIMIT 100';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        echo json_encode(['tasks' => $stmt->fetchAll()]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to load tasks: ' . $e->getMessage()]);
        exit;
    }
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $title = trim($input['title'] ?? '');
    $assignedTo = $input['assigned_to'] ?? null;
    $priority = $input['priority'] ?? 'medium';
    $dueDate = $input['due_date'] ?? null;
    $module = $input['module'] ?? 'General';

    if ($title === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Task title is required']);
        exit;
    }

    if ($assignedTo && !$scope['isAdmin']) {
        $stmtEmp = db()->prepare('SELECT * FROM users WHERE id = ?');
        $stmtEmp->execute([$assignedTo]);
        $emp = $stmtEmp->fetch();
        if ($emp && $emp['manager_id'] !== $scope['managerId'] && $emp['department_id'] !== $scope['departmentId']) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden: You can only assign tasks to employees in your team']);
            exit;
        }
    }

    $taskId = 'task-' . substr(bin2hex(random_bytes(8)), 0, 8);
    $stmt = db()->prepare('
        INSERT INTO tasks (id, title, description, module, priority, due_date, status, assigned_to, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, "pending", ?, ?, NOW())
    ');
    $stmt->execute([
        $taskId,
        $title,
        $input['description'] ?? null,
        $module,
        $priority,
        $dueDate,
        $assignedTo,
        $authUser['name']
    ]);

    log_audit($authUser, 'Task Created', 'Tasks', $taskId, ['title' => $title, 'assigned_to' => $assignedTo]);

    http_response_code(201);
    echo json_encode(['message' => 'Task queued successfully', 'id' => $taskId]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method Not Allowed']);

<?php
declare(strict_types=1);

// Centralized error and exception handling
set_exception_handler(function (Throwable $e) {
    error_log('Unhandled Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    $payload = ['error' => 'Internal Server Error'];
    if (getenv('APP_DEBUG') === 'true' || (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true')) {
        $payload['message'] = $e->getMessage();
    }
    echo json_encode($payload);
    exit;
});

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Autoload/require foundation components
$config = require __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/Response.php';
require_once __DIR__ . '/helpers/Router.php';
require_once __DIR__ . '/helpers/Jwt.php';
require_once __DIR__ . '/helpers/IdGenerator.php';
require_once __DIR__ . '/middleware/CorsMiddleware.php';
require_once __DIR__ . '/middleware/AuthMiddleware.php';
require_once __DIR__ . '/middleware/RateLimiter.php';

// Controllers
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/DepartmentController.php';
require_once __DIR__ . '/controllers/ManagerTypeController.php';
require_once __DIR__ . '/controllers/TeamController.php';
require_once __DIR__ . '/controllers/ManagerController.php';
require_once __DIR__ . '/controllers/EmployeeController.php';
require_once __DIR__ . '/controllers/AdvocateController.php';
require_once __DIR__ . '/controllers/PermissionController.php';
require_once __DIR__ . '/controllers/FeePlanController.php';
require_once __DIR__ . '/controllers/AuditLogController.php';
require_once __DIR__ . '/controllers/DashboardController.php';

// Handle CORS and preflight requests
CorsMiddleware::handle($config['allowed_origins']);

// Base health check routes
$healthHandler = function () {
    Response::json([
        'status' => 'ok',
        'service' => 'settlexpert-php-api',
        'timestamp' => date('c'),
        'version' => '1.0.0'
    ]);
};

Router::get('/', $healthHandler);
Router::get('/health', $healthHandler);
Router::get('/api/health', $healthHandler);

// Authentication routes
Router::post('/api/auth/login', [AuthController::class, 'login']);
Router::post('/auth/login', [AuthController::class, 'login']);

Router::get('/api/auth/me', [AuthController::class, 'me']);
Router::get('/auth/me', [AuthController::class, 'me']);

Router::put('/api/auth/profile', [AuthController::class, 'updateProfile']);
Router::put('/auth/profile', [AuthController::class, 'updateProfile']);

// C1: Departments
Router::get('/api/departments', [DepartmentController::class, 'index']);
Router::get('/departments', [DepartmentController::class, 'index']);
Router::post('/api/departments', [DepartmentController::class, 'create']);
Router::post('/departments', [DepartmentController::class, 'create']);
Router::put('/api/departments/:id', [DepartmentController::class, 'update']);
Router::put('/departments/:id', [DepartmentController::class, 'update']);
Router::delete('/api/departments/:id', [DepartmentController::class, 'delete']);
Router::delete('/departments/:id', [DepartmentController::class, 'delete']);

// C1: Manager Types
Router::get('/api/manager-types', [ManagerTypeController::class, 'index']);
Router::get('/manager-types', [ManagerTypeController::class, 'index']);
Router::post('/api/manager-types', [ManagerTypeController::class, 'create']);
Router::post('/manager-types', [ManagerTypeController::class, 'create']);
Router::put('/api/manager-types/:id', [ManagerTypeController::class, 'update']);
Router::put('/manager-types/:id', [ManagerTypeController::class, 'update']);
Router::delete('/api/manager-types/:id', [ManagerTypeController::class, 'delete']);
Router::delete('/manager-types/:id', [ManagerTypeController::class, 'delete']);

// C1: Teams
Router::get('/api/teams', [TeamController::class, 'index']);
Router::get('/teams', [TeamController::class, 'index']);
Router::post('/api/teams', [TeamController::class, 'create']);
Router::post('/teams', [TeamController::class, 'create']);
Router::put('/api/teams/:id', [TeamController::class, 'update']);
Router::put('/teams/:id', [TeamController::class, 'update']);
Router::delete('/api/teams/:id', [TeamController::class, 'delete']);
Router::delete('/teams/:id', [TeamController::class, 'delete']);

// C1: Managers
Router::get('/api/managers', [ManagerController::class, 'index']);
Router::get('/managers', [ManagerController::class, 'index']);
Router::get('/api/managers/:id', [ManagerController::class, 'show']);
Router::get('/managers/:id', [ManagerController::class, 'show']);
Router::post('/api/managers', [ManagerController::class, 'create']);
Router::post('/managers', [ManagerController::class, 'create']);
Router::put('/api/managers/:id', [ManagerController::class, 'update']);
Router::put('/managers/:id', [ManagerController::class, 'update']);
Router::patch('/api/managers/:id/toggle-status', [ManagerController::class, 'toggleStatus']);
Router::patch('/managers/:id/toggle-status', [ManagerController::class, 'toggleStatus']);
Router::post('/api/managers/:id/reset-password', [ManagerController::class, 'resetPassword']);
Router::post('/managers/:id/reset-password', [ManagerController::class, 'resetPassword']);

// C1: Employees
Router::get('/api/employees', [EmployeeController::class, 'index']);
Router::get('/employees', [EmployeeController::class, 'index']);
Router::get('/api/employees/:id', [EmployeeController::class, 'show']);
Router::get('/employees/:id', [EmployeeController::class, 'show']);
Router::post('/api/employees', [EmployeeController::class, 'create']);
Router::post('/employees', [EmployeeController::class, 'create']);
Router::put('/api/employees/:id', [EmployeeController::class, 'update']);
Router::put('/employees/:id', [EmployeeController::class, 'update']);
Router::post('/api/employees/:id/assign-manager', [EmployeeController::class, 'assignManager']);
Router::post('/employees/:id/assign-manager', [EmployeeController::class, 'assignManager']);
Router::post('/api/employees/:id/transfer-manager', [EmployeeController::class, 'transferManager']);
Router::post('/employees/:id/transfer-manager', [EmployeeController::class, 'transferManager']);
Router::patch('/api/employees/:id/toggle-status', [EmployeeController::class, 'toggleStatus']);
Router::patch('/employees/:id/toggle-status', [EmployeeController::class, 'toggleStatus']);
Router::post('/api/employees/:id/reset-password', [EmployeeController::class, 'resetPassword']);
Router::post('/employees/:id/reset-password', [EmployeeController::class, 'resetPassword']);

// C1: Advocates
Router::get('/api/advocates', [AdvocateController::class, 'index']);
Router::get('/advocates', [AdvocateController::class, 'index']);
Router::get('/api/advocates/:id', [AdvocateController::class, 'show']);
Router::get('/advocates/:id', [AdvocateController::class, 'show']);
Router::post('/api/advocates', [AdvocateController::class, 'create']);
Router::post('/advocates', [AdvocateController::class, 'create']);
Router::put('/api/advocates/:id', [AdvocateController::class, 'update']);
Router::put('/advocates/:id', [AdvocateController::class, 'update']);
Router::patch('/api/advocates/:id/toggle-status', [AdvocateController::class, 'toggleStatus']);
Router::patch('/advocates/:id/toggle-status', [AdvocateController::class, 'toggleStatus']);

// C1: Permissions
Router::get('/api/permissions/modules', [PermissionController::class, 'modules']);
Router::get('/permissions/modules', [PermissionController::class, 'modules']);
Router::get('/api/permissions/:userId', [PermissionController::class, 'show']);
Router::get('/permissions/:userId', [PermissionController::class, 'show']);
Router::put('/api/permissions/:userId', [PermissionController::class, 'update']);
Router::put('/permissions/:userId', [PermissionController::class, 'update']);

// C1: Fee Plans
Router::get('/api/fee-plans', [FeePlanController::class, 'index']);
Router::get('/fee-plans', [FeePlanController::class, 'index']);
Router::post('/api/fee-plans', [FeePlanController::class, 'create']);
Router::post('/fee-plans', [FeePlanController::class, 'create']);
Router::put('/api/fee-plans/:id', [FeePlanController::class, 'update']);
Router::put('/fee-plans/:id', [FeePlanController::class, 'update']);
Router::patch('/api/fee-plans/:id/toggle-status', [FeePlanController::class, 'toggleStatus']);
Router::patch('/fee-plans/:id/toggle-status', [FeePlanController::class, 'toggleStatus']);

// C1: Audit Logs
Router::get('/api/audit-logs', [AuditLogController::class, 'index']);
Router::get('/audit-logs', [AuditLogController::class, 'index']);

// C1: Dashboard
Router::get('/api/dashboard/stats', [DashboardController::class, 'stats']);
Router::get('/dashboard/stats', [DashboardController::class, 'stats']);

// Parse request path
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Normalize path: strip script directory if hosted in a subdirectory
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($uri, $scriptDir)) {
    $uri = substr($uri, strlen($scriptDir));
}
$trimmed = trim($uri, '/');
$uri = $trimmed === '' ? '/' : '/' . $trimmed;

// Dispatch to matching route
Router::dispatch($method, $uri);

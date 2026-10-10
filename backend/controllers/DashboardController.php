<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../helpers/Router.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';

class DashboardController {
    private static function checkAdmin(): array {
        $config = require __DIR__ . '/../config/config.php';
        AuthMiddleware::authenticate($config['jwt_secret']);
        return AuthMiddleware::requireAdmin();
    }

    public static function stats(): void {
        self::checkAdmin();
        $db = Database::getConnection();

        try {
            // 1. Organization & CRM Totals
            $totalManagers = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'manager'")->fetchColumn() ?: 0);
            $totalEmployees = (int)($db->query("SELECT COUNT(*) FROM users WHERE role = 'employee'")->fetchColumn() ?: 0);
            $totalAdvocates = (int)($db->query("SELECT COUNT(*) FROM advocates")->fetchColumn() ?: 0);
            $totalLeads = (int)($db->query("SELECT COUNT(*) FROM leads")->fetchColumn() ?: 0);
            $totalClients = (int)($db->query("SELECT COUNT(*) FROM clients")->fetchColumn() ?: 0);
            $totalAgreements = (int)($db->query("SELECT COUNT(*) FROM agreements")->fetchColumn() ?: 0);

            // 2. Finance Highlights
            $totalFees = (float)($db->query("SELECT COALESCE(SUM(total_fee), 0) FROM agreements")->fetchColumn() ?: 0);
            $totalReceived = (float)($db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'verified'")->fetchColumn() ?: 0);
            $pendingVerificationCount = (int)($db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending_verification'")->fetchColumn() ?: 0);
            $pendingVerificationAmt = (float)($db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'pending_verification'")->fetchColumn() ?: 0);
            $totalPending = max(0.0, $totalFees - $totalReceived);

            // 3. Operations Highlights
            $pendingTasks = (int)($db->query("SELECT COUNT(*) FROM tasks WHERE status != 'completed'")->fetchColumn() ?: 0);
            $pendingAgreements = (int)($db->query("SELECT COUNT(*) FROM agreements WHERE status = 'pending'")->fetchColumn() ?: 0);
            $todayFollowups = (int)($db->query("SELECT COUNT(*) FROM tasks WHERE status != 'completed' AND (due_date <= CURDATE() OR due_date IS NULL)")->fetchColumn() ?: 0);

            // 4. Manager Overview
            $managerStmt = $db->query("
                SELECT 
                    u.id, 
                    u.name, 
                    u.emp_or_mgr_id, 
                    u.status,
                    u.profile_image,
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
            ");
            $managerOverview = $managerStmt->fetchAll();
            foreach ($managerOverview as &$m) {
                $m['employee_count'] = (int)$m['employee_count'];
                $m['lead_count'] = (int)$m['lead_count'];
                $m['client_count'] = (int)$m['client_count'];
            }

            // 5. Recent Leads
            $leadStmt = $db->query("
                SELECT l.id, l.lead_number, l.name, l.phone, l.status, l.created_at,
                       u.name as manager_name
                FROM leads l
                LEFT JOIN users u ON l.manager_id = u.id
                ORDER BY l.created_at DESC LIMIT 5
            ");
            $recentLeads = $leadStmt->fetchAll();

            // 6. Recent Clients
            $clientStmt = $db->query("
                SELECT c.id, c.client_number, c.name, c.phone, c.total_debt, c.status, c.created_at,
                       u.name as manager_name
                FROM clients c
                LEFT JOIN users u ON c.manager_id = u.id
                ORDER BY c.created_at DESC LIMIT 5
            ");
            $recentClients = $clientStmt->fetchAll();
            foreach ($recentClients as &$c) {
                if (isset($c['total_debt'])) {
                    $c['total_debt'] = (float)$c['total_debt'];
                }
            }

            // 7. Recent Payments
            $paymentStmt = $db->query("
                SELECT p.id, p.receipt_number, p.amount, p.payment_method, p.transaction_id, p.status, p.payment_date, p.created_at,
                       c.name as client_name
                FROM payments p
                LEFT JOIN clients c ON p.client_id = c.id
                ORDER BY p.created_at DESC LIMIT 5
            ");
            $recentPayments = $paymentStmt->fetchAll();
            foreach ($recentPayments as &$p) {
                if (isset($p['amount'])) {
                    $p['amount'] = (float)$p['amount'];
                }
            }

            // 8. Recent Audit Activity
            $auditStmt = $db->query("
                SELECT id, user_name, role, action, module, record_id, details_json, created_at
                FROM audit_logs
                ORDER BY created_at DESC LIMIT 8
            ");
            $recentActivity = $auditStmt->fetchAll();

            Response::json([
                'counts' => [
                    'totalManagers' => $totalManagers,
                    'totalEmployees' => $totalEmployees,
                    'totalAdvocates' => $totalAdvocates,
                    'totalLeads' => $totalLeads,
                    'totalClients' => $totalClients,
                    'totalAgreements' => $totalAgreements
                ],
                'finance' => [
                    'totalFees' => $totalFees,
                    'totalReceived' => $totalReceived,
                    'totalPending' => $totalPending,
                    'pendingVerificationCount' => $pendingVerificationCount,
                    'pendingVerificationAmt' => $pendingVerificationAmt
                ],
                'operations' => [
                    'todayFollowups' => $todayFollowups,
                    'pendingTasks' => $pendingTasks,
                    'pendingAgreements' => $pendingAgreements
                ],
                'managerOverview' => $managerOverview,
                'recentLeads' => $recentLeads,
                'recentClients' => $recentClients,
                'recentPayments' => $recentPayments,
                'recentActivity' => $recentActivity
            ]);
        } catch (Throwable $e) {
            error_log('Dashboard stats error: ' . $e->getMessage());
            Response::error('Failed to generate dashboard metrics', 500);
        }
    }
}

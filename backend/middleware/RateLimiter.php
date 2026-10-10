<?php
declare(strict_types=1);

class RateLimiter {
    public static function getClientIp(): string {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    public static function isLocked(PDO $db, string $ip): bool {
        try {
            $stmt = $db->prepare('SELECT locked_until FROM login_rate_limit WHERE ip_address = ? AND locked_until > NOW()');
            $stmt->execute([$ip]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable) {
            return false;
        }
    }

    public static function recordFailure(PDO $db, string $ip, int $maxAttempts = 5, int $lockoutMinutes = 15): void {
        try {
            $stmt = $db->prepare('SELECT attempts FROM login_rate_limit WHERE ip_address = ?');
            $stmt->execute([$ip]);
            $currentAttempts = $stmt->fetchColumn();

            if ($currentAttempts === false) {
                $insert = $db->prepare('INSERT INTO login_rate_limit (ip_address, attempts, last_attempt) VALUES (?, 1, NOW())');
                $insert->execute([$ip]);
            } else {
                $newAttempts = (int)$currentAttempts + 1;
                if ($newAttempts >= $maxAttempts) {
                    $update = $db->prepare('UPDATE login_rate_limit SET attempts = ?, locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE), last_attempt = NOW() WHERE ip_address = ?');
                    $update->execute([$newAttempts, $lockoutMinutes, $ip]);
                } else {
                    $update = $db->prepare('UPDATE login_rate_limit SET attempts = ?, locked_until = NULL, last_attempt = NOW() WHERE ip_address = ?');
                    $update->execute([$newAttempts, $ip]);
                }
            }
        } catch (Throwable $e) {
            error_log('RateLimiter recordFailure error: ' . $e->getMessage());
        }
    }

    public static function reset(PDO $db, string $ip): void {
        try {
            $stmt = $db->prepare('DELETE FROM login_rate_limit WHERE ip_address = ?');
            $stmt->execute([$ip]);
        } catch (Throwable) {
            // Ignore if table missing or failure
        }
    }
}

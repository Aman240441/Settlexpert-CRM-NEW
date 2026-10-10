<?php
declare(strict_types=1);

class IdGenerator {
    public static function generateId(string $prefix = ''): string {
        $random = bin2hex(random_bytes(4)); // 8 hex chars
        return $prefix !== '' ? "{$prefix}-{$random}" : $random;
    }

    public static function nextBusinessNumber(PDO $db, string $counterName): string {
        $shouldCommit = false;
        if (!$db->inTransaction()) {
            $db->beginTransaction();
            $shouldCommit = true;
        }

        try {
            $stmt = $db->prepare('SELECT current_val, prefix, padding FROM business_counters WHERE counter_name = ? FOR UPDATE');
            $stmt->execute([$counterName]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new RuntimeException("Business counter not found: {$counterName}");
            }

            $nextVal = (int)$row['current_val'] + 1;
            $updateStmt = $db->prepare('UPDATE business_counters SET current_val = ? WHERE counter_name = ?');
            $updateStmt->execute([$nextVal, $counterName]);

            if ($shouldCommit) {
                $db->commit();
            }

            $prefix = $row['prefix'];
            $padding = (int)$row['padding'];

            if ($counterName === 'receipt') {
                $year = date('Y');
                return sprintf('REC-%s-%0' . $padding . 'd', $year, $nextVal);
            }

            return sprintf('%s-%0' . $padding . 'd', $prefix, $nextVal);
        } catch (Throwable $e) {
            if ($shouldCommit && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

<?php
define('BASE_URL', '');
define('CANCEL_HOURS_LIMIT', 24);

function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function formatMoney(float $amount): string {
    return number_format($amount, 2, ',', '.') . ' €';
}

function formatDate(string $date): string {
    if (!$date) return '—';
    return date('d/m/Y', strtotime($date));
}

function formatDatetime(string $dt): string {
    if (!$dt) return '—';
    return date('d/m/Y H:i', strtotime($dt));
}

function nightsBetween(string $start, string $end): int {
    $s = new DateTime($start);
    $e = new DateTime($end);
    return max(0, (int)$s->diff($e)->days);
}

function calculateTotal(array $rt, int $numRooms, int $numGuests, bool $breakfast, string $start, string $end, int $numChildren = 0): float {
    $nights = nightsBetween($start, $end);
    if ($nights <= 0) return 0;

    $base = $rt['base_daily_rate'] * $numRooms * $nights;

    // Extra guests surcharge (beyond base capacity per room)
    $baseCapacityTotal = $rt['base_capacity'] * $numRooms;
    $extraGuests = max(0, $numGuests - $baseCapacityTotal);
    $extraSurcharge = $extraGuests * $rt['extra_guest_surcharge'] * $nights;

    // Breakfast: crianças < 3 anos são GRATUITAS (proposta fase 1)
    // numGuests já exclui crianças < 3 passadas via $numChildren
    $payingGuests = max(0, $numGuests - $numChildren);
    $breakfastCost = $breakfast ? ($rt['breakfast_cost_per_guest'] * $payingGuests * $nights) : 0;

    return round($base + $extraSurcharge + $breakfastCost, 2);
}

function canEditReservation(string $startDate): bool {
    $start = new DateTime($startDate);
    $now   = new DateTime();
    $diffHours = ($start->getTimestamp() - $now->getTimestamp()) / 3600;
    return $diffHours > CANCEL_HOURS_LIMIT;
}

function availableRoomCount(int $roomTypeId, string $start, string $end, ?int $excludeReservationId = null): int {
    $pdo = getDB();
    // Total rooms of this type
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND status != "maintenance"');
    $stmt->execute([$roomTypeId]);
    $total = (int)$stmt->fetchColumn();

    // Rooms occupied in the given period
    $sql = '
        SELECT COALESCE(SUM(r.num_rooms), 0)
        FROM reservations r
        WHERE r.room_type_id = ?
          AND r.status NOT IN ("cancelled","completed")
          AND r.start_date < ?
          AND r.end_date > ?
    ';
    $params = [$roomTypeId, $end, $start];
    if ($excludeReservationId) {
        $sql .= ' AND r.id != ?';
        $params[] = $excludeReservationId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $occupied = (int)$stmt->fetchColumn();

    return max(0, $total - $occupied);
}

function logAction(string $action, string $entity, ?int $entityId, string $details = ''): void {
    try {
        $pdo = getDB();
        startSession();
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt   = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$userId, $action, $entity, $entityId, $details, $ip]);
    } catch (Throwable) {
        // Non-critical; silently ignore logging failures
    }
}

function flash(string $msg, string $type = 'success'): void {
    startSession();
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    startSession();
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function redirect(string $url): void {
    header('Location: ' . BASE_URL . $url);
    exit;
}

function post(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = ''): mixed {
    return $_GET[$key] ?? $default;
}

function csrf(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    startSession();
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Token CSRF inválido.');
    }
}

function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function validateNIF(string $nif): bool {
    $nif = preg_replace('/\D/', '', $nif);
    if (strlen($nif) !== 9) return false;
    $sum = 0;
    for ($i = 0; $i < 8; $i++) {
        $sum += (int)$nif[$i] * (9 - $i);
    }
    $check = 11 - ($sum % 11);
    if ($check >= 10) $check = 0;
    return $check === (int)$nif[8];
}

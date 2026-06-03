<?php
// ============================================================
// functions.php — Funções utilitárias globais da aplicação
// Carregado por TODAS as páginas via require_once
// ============================================================

// URL base do projeto no XAMPP (pasta /hotel)
define('BASE_URL', '/hotel');

// Horas mínimas antes do check-in para permitir editar/cancelar
define('CANCEL_HOURS_LIMIT', 24);


// ------------------------------------------------------------
// SEGURANÇA — Escapar output para evitar XSS
// Usar sempre que mostrar dados do utilizador no HTML
// ------------------------------------------------------------
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}


// ------------------------------------------------------------
// FORMATAÇÃO — Datas e valores monetários
// ------------------------------------------------------------
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

// Calcula o número de noites entre duas datas
function nightsBetween(string $start, string $end): int {
    $s = new DateTime($start);
    $e = new DateTime($end);
    return max(0, (int)$s->diff($e)->days);
}


// ------------------------------------------------------------
// CÁLCULO DE PREÇO
// Fórmula: (taxa diária × quartos × noites)
//        + (hóspedes extra × suplemento × noites)
//        + (pequeno-almoço × adultos × noites)
// Crianças < 3 anos não pagam pequeno-almoço
// ------------------------------------------------------------
function calculateTotal(array $rt, int $numRooms, int $numGuests, bool $breakfast, string $start, string $end, int $numChildren = 0): float {
    $nights = nightsBetween($start, $end);
    if ($nights <= 0) return 0;

    // Custo base: taxa diária × nº de quartos × nº de noites
    $base = $rt['base_daily_rate'] * $numRooms * $nights;

    // Suplemento por hóspedes extra (acima da capacidade base)
    $baseCapacityTotal = $rt['base_capacity'] * $numRooms;
    $extraGuests       = max(0, $numGuests - $baseCapacityTotal);
    $extraSurcharge    = $extraGuests * $rt['extra_guest_surcharge'] * $nights;

    // Pequeno-almoço: apenas adultos pagam (crianças < 3 anos grátis)
    $payingGuests  = max(0, $numGuests - $numChildren);
    $breakfastCost = $breakfast ? ($rt['breakfast_cost_per_guest'] * $payingGuests * $nights) : 0;

    return round($base + $extraSurcharge + $breakfastCost, 2);
}


// ------------------------------------------------------------
// REGRA DAS 24 HORAS
// Verifica se o cliente ainda pode editar/cancelar a reserva
// Só é possível se faltarem mais de 24h para o check-in
// ------------------------------------------------------------
function canEditReservation(string $startDate): bool {
    $start     = new DateTime($startDate);
    $now       = new DateTime();
    $diffHours = ($start->getTimestamp() - $now->getTimestamp()) / 3600;
    return $diffHours > CANCEL_HOURS_LIMIT;
}


// ------------------------------------------------------------
// ANTI-OVERBOOKING
// Conta quantos quartos de um tipo estão disponíveis num período
// Total de quartos do tipo - quartos já reservados nesse período
// $excludeReservationId: ignorar uma reserva (usado ao editar)
// ------------------------------------------------------------
function availableRoomCount(int $roomTypeId, string $start, string $end, ?int $excludeReservationId = null): int {
    $pdo = getDB();

    // Total de quartos físicos deste tipo (excluindo manutenção)
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_type_id = ? AND status != "maintenance"');
    $stmt->execute([$roomTypeId]);
    $total = (int)$stmt->fetchColumn();

    // Quartos já ocupados por reservas que se sobrepõem ao período pedido
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
        $sql    .= ' AND r.id != ?';
        $params[] = $excludeReservationId;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $occupied = (int)$stmt->fetchColumn();

    return max(0, $total - $occupied);
}


// ------------------------------------------------------------
// AUDITORIA — Registo de ações críticas
// Guarda na tabela audit_logs: quem fez, o quê, quando, de onde
// ------------------------------------------------------------
function logAction(string $action, string $entity, ?int $entityId, string $details = ''): void {
    try {
        $pdo    = getDB();
        startSession();
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt   = $pdo->prepare('INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$userId, $action, $entity, $entityId, $details, $ip]);
    } catch (Throwable) {
        // Log não é crítico — se falhar, a aplicação continua
    }
}


// ------------------------------------------------------------
// MENSAGENS FLASH
// Guarda uma mensagem na sessão para mostrar após redirect
// Tipos: 'success' (verde) ou 'error' (vermelho)
// ------------------------------------------------------------
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


// ------------------------------------------------------------
// NAVEGAÇÃO
// Redireciona para uma URL interna (BASE_URL + $url)
// ------------------------------------------------------------
function redirect(string $url): void {
    header('Location: ' . BASE_URL . $url);
    exit;
}


// ------------------------------------------------------------
// LEITURA DE DADOS DO FORMULÁRIO
// Atalhos para $_POST e $_GET com valor padrão
// ------------------------------------------------------------
function post(string $key, mixed $default = ''): mixed {
    return $_POST[$key] ?? $default;
}

function get(string $key, mixed $default = ''): mixed {
    return $_GET[$key] ?? $default;
}


// ------------------------------------------------------------
// PROTEÇÃO CSRF
// Gera um token único por sessão para proteger formulários
// verifyCsrf() valida o token enviado no POST
// ------------------------------------------------------------
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


// ------------------------------------------------------------
// VALIDAÇÕES
// ------------------------------------------------------------

// Valida formato de data YYYY-MM-DD
function validateDate(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Valida NIF português (9 dígitos com dígito de controlo)
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

// Valida formato do número de documento por tipo
function validateDocument(string $type, string $number): bool {
    $number = trim($number);
    if ($type === 'cc') {
        // Cartao de Cidadao: 6 a 15 caracteres alfanumericos
        return (bool)preg_match('/^[A-Za-z0-9]{6,15}$/', $number);
    }
    if ($type === 'passport') {
        // Passaporte: 5 a 12 caracteres alfanumericos
        return (bool)preg_match('/^[A-Za-z0-9]{5,12}$/', $number);
    }
    if ($type === 'other') {
        // Outro documento: entre 3 e 30 caracteres
        return strlen($number) >= 3 && strlen($number) <= 30;
    }
    return true;
}

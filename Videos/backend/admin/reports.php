<?php
$pageTitle = 'Relatórios';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$monthStart = get('month_start', date('Y-m-01'));
$monthEnd   = get('month_end', date('Y-m-t'));
if (!validateDate($monthStart)) $monthStart = date('Y-m-01');
if (!validateDate($monthEnd))   $monthEnd   = date('Y-m-t');

// Occupancy rate
$totalRooms    = (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status != "maintenance"')->fetchColumn();
$occupiedToday = (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn();
$occupancyRate = $totalRooms > 0 ? round($occupiedToday / $totalRooms * 100, 1) : 0;

// Reservations by status in period
$stmt = $pdo->prepare('SELECT status, COUNT(*) AS cnt FROM reservations WHERE start_date BETWEEN ? AND ? GROUP BY status');
$stmt->execute([$monthStart, $monthEnd]);
$byStatus = [];
foreach ($stmt->fetchAll() as $row) $byStatus[$row['status']] = $row['cnt'];

// Future reservations (start_date > today, not cancelled)
$futureCount = (int)$pdo->prepare('SELECT COUNT(*) FROM reservations WHERE start_date > CURDATE() AND status IN ("pending","active")')->execute() ? 0 : 0;
$fcStmt = $pdo->query('SELECT COUNT(*) FROM reservations WHERE start_date > CURDATE() AND status IN ("pending","active")');
$futureCount = (int)$fcStmt->fetchColumn();

// Revenue by period
$revenue = (float)$pdo->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_date BETWEEN ? AND ?')->execute([$monthStart,$monthEnd]) ? 0 : 0;
$revStmt = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE payment_date BETWEEN ? AND ?');
$revStmt->execute([$monthStart, $monthEnd]);
$revenue = (float)$revStmt->fetchColumn();

// Revenue by room type
$revByType = $pdo->prepare('
    SELECT rt.name, COALESCE(SUM(p.amount),0) AS total, COUNT(DISTINCT r.id) AS reservations
    FROM room_types rt
    LEFT JOIN reservations r ON r.room_type_id = rt.id
    LEFT JOIN payments p ON p.reservation_id = r.id AND p.payment_date BETWEEN ? AND ?
    GROUP BY rt.id, rt.name ORDER BY total DESC
');
$revByType->execute([$monthStart, $monthEnd]);
$revByType = $revByType->fetchAll();

// Daily occupancy (month)
$dailyOcc = $pdo->prepare('
    SELECT d.day_date,
        COUNT(DISTINCT rr.room_id) AS occupied_rooms
    FROM (
        SELECT DATE_ADD(?, INTERVAL seq DAY) AS day_date
        FROM (SELECT 0 AS seq UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4
              UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
              UNION SELECT 10 UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14
              UNION SELECT 15 UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19
              UNION SELECT 20 UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24
              UNION SELECT 25 UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30) seq
        WHERE DATE_ADD(?, INTERVAL seq DAY) <= ?
    ) d
    LEFT JOIN reservation_rooms rr ON d.day_date BETWEEN DATE(rr.checkin_at) AND DATE(IFNULL(rr.checkout_at, NOW()))
    GROUP BY d.day_date ORDER BY d.day_date
');
$dailyOcc->execute([$monthStart, $monthStart, $monthEnd]);
$dailyOcc = $dailyOcc->fetchAll();

// Currently occupied rooms list
$occupiedRoomsList = $pdo->query('
    SELECT r.room_number, r.floor, rt.name AS room_type,
           u.name AS client_name, res.start_date, res.end_date,
           rr.checkin_at
    FROM reservation_rooms rr
    JOIN rooms r         ON rr.room_id = r.id
    JOIN room_types rt   ON r.room_type_id = rt.id
    JOIN reservations res ON rr.reservation_id = res.id
    JOIN users u          ON res.user_id = u.id
    WHERE rr.checkout_at IS NULL AND res.status = "checked_in"
    ORDER BY r.floor, r.room_number
')->fetchAll();

// Guest history (top guests)
$topGuests = $pdo->query('
    SELECT u.name, u.email, COUNT(r.id) AS total_res,
        COALESCE(SUM(r.total_estimated),0) AS total_spent,
        MAX(r.end_date) AS last_stay
    FROM users u
    JOIN reservations r ON r.user_id = u.id AND r.status != "cancelled"
    WHERE u.role = "client"
    GROUP BY u.id ORDER BY total_res DESC LIMIT 10
')->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">📈 Relatórios</div>

<!-- Period filter -->
<div class="filter-bar" style="margin-bottom:1.5rem">
    <form method="get" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap">
        <div class="form-group"><label class="form-label">De</label><input type="date" name="month_start" class="form-control" value="<?= e($monthStart) ?>"></div>
        <div class="form-group"><label class="form-label">Até</label><input type="date" name="month_end" class="form-control" value="<?= e($monthEnd) ?>"></div>
        <button type="submit" class="btn btn-primary">Filtrar</button>
    </form>
</div>

<!-- Summary cards -->
<div class="stat-cards" style="margin-bottom:2rem">
    <div class="stat-card"><span class="icon">🏨</span><div class="value"><?= $occupancyRate ?>%</div><div class="label">Ocupação Atual</div></div>
    <div class="stat-card"><span class="icon">💰</span><div class="value"><?= formatMoney($revenue) ?></div><div class="label">Receita no Período</div></div>
    <div class="stat-card"><span class="icon">📅</span><div class="value"><?= array_sum($byStatus) ?></div><div class="label">Total de Reservas</div></div>
    <div class="stat-card"><span class="icon">✓</span><div class="value"><?= ($byStatus['completed']??0)+($byStatus['checked_in']??0) ?></div><div class="label">Estadias Realizadas</div></div>
</div>

<div class="grid-2" style="gap:1.5rem;margin-bottom:1.5rem">
    <!-- Reservations by status -->
    <div class="detail-card">
        <h3>📊 Reservas por Estado <small style="font-weight:400;color:#888">(no período)</small></h3>
        <div style="display:flex;gap:1rem;flex-wrap:wrap;margin:.75rem 0 1rem">
            <div style="text-align:center;padding:.5rem 1rem;background:#e8f4fd;border-radius:6px">
                <div style="font-size:1.4rem;font-weight:700;color:#2563eb"><?= $futureCount ?></div>
                <div style="font-size:.75rem;color:#555">Futuras (globais)</div>
            </div>
            <div style="text-align:center;padding:.5rem 1rem;background:#e8f8ee;border-radius:6px">
                <div style="font-size:1.4rem;font-weight:700;color:#198754"><?= ($byStatus['checked_in']??0) ?></div>
                <div style="font-size:.75rem;color:#555">Ativas (check-in)</div>
            </div>
            <div style="text-align:center;padding:.5rem 1rem;background:#fdf0e8;border-radius:6px">
                <div style="font-size:1.4rem;font-weight:700;color:#dc3545"><?= ($byStatus['cancelled']??0) ?></div>
                <div style="font-size:.75rem;color:#555">Canceladas</div>
            </div>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Estado</th><th>Contagem no Período</th></tr></thead>
                <tbody>
                <?php foreach (['pending'=>'Pendentes','active'=>'Confirmadas','checked_in'=>'Check-in Feito','completed'=>'Concluídas','cancelled'=>'Canceladas'] as $s=>$l): ?>
                <tr><td><?= $l ?></td><td><strong><?= $byStatus[$s]??0 ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Revenue by type -->
    <div class="detail-card">
        <h3>💳 Receita por Tipo de Quarto</h3>
        <div class="table-wrapper" style="margin-top:.75rem">
            <table>
                <thead><tr><th>Tipo</th><th>Reservas</th><th>Receita</th></tr></thead>
                <tbody>
                <?php foreach ($revByType as $rt): ?>
                <tr><td><?= e($rt['name']) ?></td><td><?= e($rt['reservations']) ?></td><td><?= formatMoney($rt['total']) ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Daily occupancy table -->
<div class="detail-card" style="margin-bottom:1.5rem">
    <h3>📅 Ocupação Diária</h3>
    <div class="table-wrapper" style="margin-top:.75rem">
        <table>
            <thead><tr><th>Data</th><th>Quartos Ocupados</th><th>Taxa (%)</th></tr></thead>
            <tbody>
            <?php foreach ($dailyOcc as $day): ?>
            <tr>
                <td><?= e(formatDate($day['day_date'])) ?></td>
                <td><?= e($day['occupied_rooms']) ?> / <?= $totalRooms ?></td>
                <td><?= $totalRooms > 0 ? round($day['occupied_rooms'] / $totalRooms * 100) : 0 ?>%</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Currently occupied rooms -->
<div class="detail-card" style="margin-bottom:1.5rem">
    <h3>🚪 Quartos Atualmente Ocupados (<?= count($occupiedRoomsList) ?>/<?= $totalRooms ?>)</h3>
    <?php if (empty($occupiedRoomsList)): ?>
        <p style="color:#888;margin-top:.75rem">Nenhum quarto ocupado neste momento.</p>
    <?php else: ?>
    <div class="table-wrapper" style="margin-top:.75rem">
        <table>
            <thead><tr><th>Quarto</th><th>Piso</th><th>Tipo</th><th>Hóspede</th><th>Check-in</th><th>Check-out Prev.</th></tr></thead>
            <tbody>
            <?php foreach ($occupiedRoomsList as $or): ?>
            <tr>
                <td><strong><?= e($or['room_number']) ?></strong></td>
                <td><?= e($or['floor']) ?></td>
                <td><?= e($or['room_type']) ?></td>
                <td><?= e($or['client_name']) ?></td>
                <td><?= e(formatDatetime($or['checkin_at'])) ?></td>
                <td><?= e(formatDate($or['end_date'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Top guests -->
<div class="detail-card">
    <h3>👥 Histórico de Hóspedes (Top 10)</h3>
    <div class="table-wrapper" style="margin-top:.75rem">
        <table>
            <thead><tr><th>Nome</th><th>Email</th><th>Reservas</th><th>Total Gasto</th><th>Última Estadia</th></tr></thead>
            <tbody>
            <?php foreach ($topGuests as $g): ?>
            <tr>
                <td><?= e($g['name']) ?></td>
                <td><?= e($g['email']) ?></td>
                <td><?= e($g['total_res']) ?></td>
                <td><?= formatMoney($g['total_spent']) ?></td>
                <td><?= e(formatDate($g['last_stay'])) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($topGuests)): ?>
            <tr><td colspan="5" class="text-center" style="padding:1.5rem;color:#888">Nenhum dado disponível.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


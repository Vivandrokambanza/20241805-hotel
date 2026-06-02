<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

// Stats
$totalRooms        = (int)$pdo->query('SELECT COUNT(*) FROM rooms')->fetchColumn();
$occupiedRooms     = (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status = "occupied"')->fetchColumn();
$todayCheckins     = (int)$pdo->query('SELECT COUNT(*) FROM reservations WHERE start_date = CURDATE() AND status IN ("active","pending")')->fetchColumn();
$todayCheckouts    = (int)$pdo->query('SELECT COUNT(*) FROM reservations WHERE end_date = CURDATE() AND status = "checked_in"')->fetchColumn();
$pendingReservations = (int)$pdo->query('SELECT COUNT(*) FROM reservations WHERE status = "pending"')->fetchColumn();
$monthRevenue      = (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE MONTH(payment_date) = MONTH(CURDATE()) AND YEAR(payment_date) = YEAR(CURDATE())')->fetchColumn();
$pendingPayments   = (float)$pdo->query('SELECT COALESCE(SUM(total_estimated - total_paid),0) FROM reservations WHERE status IN ("pending","active","checked_in")')->fetchColumn();
$occupancyRate     = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100, 1) : 0;

// Recent reservations
$recent = $pdo->query('
    SELECT r.*, rt.name AS type_name, u.name AS client_name
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC LIMIT 8
')->fetchAll();

// Today's check-ins
$checkinList = $pdo->query('
    SELECT r.*, rt.name AS type_name, u.name AS client_name
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN users u ON r.user_id = u.id
    WHERE r.start_date = CURDATE() AND r.status IN ("active","pending")
    ORDER BY r.id ASC
')->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page-title">
    <span>📊 Dashboard</span>
    <span style="font-size:.9rem;color:#777;font-weight:400"><?= date('d/m/Y') ?></span>
</div>

<div class="stat-cards">
    <div class="stat-card">
        <span class="icon">🚪</span>
        <div class="value"><?= $occupancyRate ?>%</div>
        <div class="label">Taxa de Ocupação<br><small><?= $occupiedRooms ?>/<?= $totalRooms ?> quartos</small></div>
    </div>
    <div class="stat-card">
        <span class="icon">📅</span>
        <div class="value"><?= $pendingReservations ?></div>
        <div class="label">Reservas Pendentes</div>
    </div>
    <div class="stat-card">
        <span class="icon">🔑</span>
        <div class="value"><?= $todayCheckins ?></div>
        <div class="label">Check-ins Hoje</div>
    </div>
    <div class="stat-card">
        <span class="icon">💰</span>
        <div class="value"><?= formatMoney($monthRevenue) ?></div>
        <div class="label">Receita Este Mês</div>
    </div>
</div>

<div class="grid-2" style="gap:1.5rem;margin-bottom:1.5rem">
    <div>
        <div class="page-header">
            <h2 style="font-size:1.1rem;font-weight:700">Check-ins de Hoje</h2>
            <a href="checkin.php" class="btn btn-sm btn-primary">Ver todos</a>
        </div>
        <?php if (empty($checkinList)): ?>
            <div class="empty-state" style="padding:1.5rem"><p>Sem check-ins hoje.</p></div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Hóspedes</th><th>Ação</th></tr></thead>
                <tbody>
                <?php foreach ($checkinList as $r): ?>
                <tr>
                    <td>#<?= e($r['id']) ?></td>
                    <td><?= e($r['client_name']) ?></td>
                    <td><?= e($r['type_name']) ?></td>
                    <td><?= e($r['num_guests']) ?></td>
                    <td><a href="reservation-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Check-in</a></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div>
        <div class="page-header">
            <h2 style="font-size:1.1rem;font-weight:700">Últimas Reservas</h2>
            <a href="reservations.php" class="btn btn-sm btn-secondary">Ver todas</a>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>#</th><th>Cliente</th><th>Check-in</th><th>Estado</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $r): ?>
                <tr>
                    <td><a href="reservation-detail.php?id=<?= $r['id'] ?>">#<?= e($r['id']) ?></a></td>
                    <td><?= e($r['client_name']) ?></td>
                    <td><?= e(formatDate($r['start_date'])) ?></td>
                    <td><span class="badge status-<?= e($r['status']) ?>"><?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in','completed'=>'Concluída','cancelled'=>'Cancelada'][$r['status']] ?? $r['status'] ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="detail-card">
    <h3>⚠️ Alertas</h3>
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;padding:.5rem 0">
        <div>📤 <strong><?= $todayCheckouts ?></strong> check-out<?= $todayCheckouts !== 1 ? 's' : '' ?> pendente<?= $todayCheckouts !== 1 ? 's' : '' ?> hoje</div>
        <div>💳 Pagamentos em falta: <strong><?= formatMoney(max(0, $pendingPayments)) ?></strong></div>
        <div>🔧 Quartos em manutenção: <strong><?= (int)$pdo->query('SELECT COUNT(*) FROM rooms WHERE status="maintenance"')->fetchColumn() ?></strong></div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


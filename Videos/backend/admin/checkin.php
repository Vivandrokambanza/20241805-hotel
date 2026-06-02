<?php
$pageTitle = 'Check-in / Check-out';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('receptionist', 'manager');
$pdo = getDB();

$tab = get('tab', 'checkin');

$checkins = $pdo->query('
    SELECT r.*, rt.name AS type_name, u.name AS client_name
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status = "active" AND r.start_date <= CURDATE()
    ORDER BY r.start_date ASC
')->fetchAll();

$checkouts = $pdo->query('
    SELECT r.*, rt.name AS type_name, u.name AS client_name
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status = "checked_in"
    ORDER BY r.end_date ASC
')->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">🔑 Check-in / Check-out</div>

<div style="display:flex;gap:.5rem;margin-bottom:1.5rem">
    <a href="?tab=checkin" class="btn <?= $tab==='checkin'?'btn-primary':'btn-secondary' ?>">
        🔑 Check-in Pendentes (<?= count($checkins) ?>)
    </a>
    <a href="?tab=checkout" class="btn <?= $tab==='checkout'?'btn-primary':'btn-secondary' ?>">
        🚪 Check-out Pendentes (<?= count($checkouts) ?>)
    </a>
</div>

<?php if ($tab === 'checkin'): ?>
<div class="table-wrapper">
    <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Check-in</th><th>Check-out</th><th>Quartos</th><th>Hóspedes</th><th>Ação</th></tr></thead>
        <tbody>
        <?php if (empty($checkins)): ?>
            <tr><td colspan="8" class="text-center" style="padding:2rem;color:#888">Nenhum check-in pendente.</td></tr>
        <?php endif; ?>
        <?php foreach ($checkins as $r): ?>
        <tr>
            <td><a href="reservation-detail.php?id=<?= $r['id'] ?>">#<?= e($r['id']) ?></a></td>
            <td><?= e($r['client_name']) ?></td>
            <td><?= e($r['type_name']) ?></td>
            <td><?= e(formatDate($r['start_date'])) ?></td>
            <td><?= e(formatDate($r['end_date'])) ?></td>
            <td><?= e($r['num_rooms']) ?></td>
            <td><?= e($r['num_guests']) ?></td>
            <td><a href="reservation-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Efectuar Check-in</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php else: ?>
<div class="table-wrapper">
    <table>
        <thead><tr><th>#</th><th>Cliente</th><th>Tipo</th><th>Check-in</th><th>Check-out</th><th>Noites</th><th>Ação</th></tr></thead>
        <tbody>
        <?php if (empty($checkouts)): ?>
            <tr><td colspan="7" class="text-center" style="padding:2rem;color:#888">Nenhum check-out pendente.</td></tr>
        <?php endif; ?>
        <?php foreach ($checkouts as $r): ?>
        <tr <?= $r['end_date'] <= date('Y-m-d') ? 'style="background:#fff8e1"' : '' ?>>
            <td><a href="reservation-detail.php?id=<?= $r['id'] ?>">#<?= e($r['id']) ?></a></td>
            <td><?= e($r['client_name']) ?></td>
            <td><?= e($r['type_name']) ?></td>
            <td><?= e(formatDate($r['start_date'])) ?></td>
            <td><?= e(formatDate($r['end_date'])) ?><?= $r['end_date'] < date('Y-m-d') ? ' <span class="badge badge-red">Atrasado</span>' : '' ?></td>
            <td><?= nightsBetween($r['start_date'], $r['end_date']) ?></td>
            <td><a href="reservation-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Efectuar Check-out</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


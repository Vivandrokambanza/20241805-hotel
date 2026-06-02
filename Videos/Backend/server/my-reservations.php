<?php
$pageTitle = 'As Minhas Reservas';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole('client');

$pdo    = getDB();
$userId = currentUser()['id'];

$filter = get('status', 'all');
$sql = '
    SELECT r.*, rt.name AS type_name, rt.base_daily_rate
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.user_id = ?
';
$params = [$userId];
if ($filter !== 'all') {
    $sql .= ' AND r.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY r.start_date DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="page-header">
            <h1>As Minhas Reservas</h1>
            <a href="book.php" class="btn btn-primary">+ Nova Reserva</a>
        </div>

        <div class="filter-bar">
            <?php foreach (['all'=>'Todas','pending'=>'Pendentes','active'=>'Confirmadas','checked_in'=>'Check-in efetuado','completed'=>'Concluídas','cancelled'=>'Canceladas'] as $val => $label): ?>
                <a href="?status=<?= $val ?>" class="btn btn-sm <?= $filter === $val ? 'btn-primary' : 'btn-secondary' ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($reservations)): ?>
            <div class="empty-state">
                <div class="icon">🗓️</div>
                <p>Não tem reservas<?= $filter !== 'all' ? ' com este estado' : '' ?>.</p>
                <a href="book.php" class="btn btn-primary mt-2">Fazer primeira reserva</a>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tipo de Quarto</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Noites</th>
                        <th>Hóspedes</th>
                        <th>Total Est.</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td><strong>#<?= e($res['id']) ?></strong></td>
                        <td><?= e($res['type_name']) ?></td>
                        <td><?= e(formatDate($res['start_date'])) ?></td>
                        <td><?= e(formatDate($res['end_date'])) ?></td>
                        <td><?= nightsBetween($res['start_date'], $res['end_date']) ?></td>
                        <td><?= e($res['num_guests']) ?></td>
                        <td><?= formatMoney($res['total_estimated']) ?></td>
                        <td>
                            <span class="badge status-<?= e($res['status']) ?>">
                                <?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in feito','completed'=>'Concluída','cancelled'=>'Cancelada'][$res['status']] ?? $res['status'] ?>
                            </span>
                        </td>
                        <td class="td-actions">
                            <a href="reservation.php?id=<?= $res['id'] ?>" class="btn btn-sm btn-secondary">Ver</a>
                            <?php if (in_array($res['status'], ['pending','active'])): ?>
                                <?php if (canEditReservation($res['start_date'])): ?>
                                    <a href="reservation.php?id=<?= $res['id'] ?>&action=edit" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="reservation.php?id=<?= $res['id'] ?>&action=cancel"
                                        class="btn btn-sm btn-danger"
                                        data-confirm="Tem a certeza que pretende cancelar esta reserva?">Cancelar</a>
                                <?php else: ?>
                                    <span title="Edição/cancelamento bloqueado — faltam menos de 24 h para o check-in"
                                          style="font-size:.78rem;color:#c0392b;white-space:nowrap">⚠️ &lt;24 h</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="policy-box" style="margin-top:1.5rem">
            Pode <strong>editar ou cancelar</strong> uma reserva até <strong>24 horas antes do check-in</strong>.
            Reservas assinaladas com <strong style="color:#c0392b">⚠️ &lt;24 h</strong> já não podem ser alteradas — contacte a receção para situações de força maior.
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

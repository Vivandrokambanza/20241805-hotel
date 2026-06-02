<?php
$pageTitle = 'Reserva';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole('client');

$pdo    = getDB();
$userId = currentUser()['id'];
$id     = (int)get('id');
$action = get('action');

$stmt = $pdo->prepare('
    SELECT r.*, rt.name AS type_name, rt.base_daily_rate, rt.base_capacity, rt.max_capacity,
           rt.breakfast_cost_per_guest, rt.extra_guest_surcharge
    FROM reservations r
    JOIN room_types rt ON r.room_type_id = rt.id
    WHERE r.id = ? AND r.user_id = ?
');
$stmt->execute([$id, $userId]);
$res = $stmt->fetch();
if (!$res) {
    flash('Reserva não encontrada.', 'error');
    redirect('/my-reservations.php');
}

$canEdit = in_array($res['status'], ['pending', 'active']) && canEditReservation($res['start_date']);
$errors  = [];

// Cancel action
if ($action === 'cancel' && $canEdit) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ? AND user_id = ?')->execute([$id, $userId]);
        logAction('cancel_reservation', 'reservations', $id, 'Client cancelled reservation');
        flash("Reserva #{$id} cancelada.");
        redirect('/my-reservations.php');
    }
}

// Edit action
if ($action === 'edit' && $canEdit && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $checkIn   = post('check_in');
    $checkOut  = post('check_out');
    $numGuests = max(1, (int)post('num_guests'));
    $numRooms  = max(1, (int)post('num_rooms'));
    $breakfast = (bool)post('include_breakfast');
    $nif       = preg_replace('/\D/', '', post('nif'));
    $notes     = trim(post('notes'));

    if (!validateDate($checkIn))          $errors[] = 'Data de check-in inválida.';
    if (!validateDate($checkOut))         $errors[] = 'Data de check-out inválida.';
    if ($checkIn >= $checkOut)            $errors[] = 'Check-out deve ser posterior ao check-in.';
    if ($checkIn < date('Y-m-d'))         $errors[] = 'Data de check-in no passado.';
    if ($nif && !validateNIF($nif))       $errors[] = 'NIF inválido.';

    if (!$errors) {
        if ($numGuests > $res['max_capacity'] * $numRooms) {
            $errors[] = 'Hóspedes excedem capacidade máxima (' . ($res['max_capacity'] * $numRooms) . ').';
        }
        $avail = availableRoomCount($res['room_type_id'], $checkIn, $checkOut, $id);
        if ($avail < $numRooms) {
            $errors[] = "Apenas {$avail} quarto(s) disponível(is).";
        }
    }

    if (!$errors) {
        $total = calculateTotal($res, $numRooms, $numGuests, $breakfast, $checkIn, $checkOut);
        $pdo->prepare('
            UPDATE reservations SET start_date=?, end_date=?, num_rooms=?, num_guests=?, include_breakfast=?, nif=?, notes=?, total_estimated=?
            WHERE id = ? AND user_id = ?
        ')->execute([$checkIn, $checkOut, $numRooms, $numGuests, $breakfast ? 1 : 0, $nif ?: null, $notes ?: null, $total, $id, $userId]);
        logAction('edit_reservation', 'reservations', $id, "Client edited: {$checkIn} to {$checkOut}");
        flash("Reserva #{$id} atualizada.");
        redirect('/my-reservations.php');
    }
}

// Payments
$payments = $pdo->prepare('SELECT p.*, u.name AS operator_name FROM payments p JOIN users u ON p.operator_id = u.id WHERE p.reservation_id = ? ORDER BY p.created_at DESC');
$payments->execute([$id]);
$payments = $payments->fetchAll();

$nights   = nightsBetween($res['start_date'], $res['end_date']);
$pageTitle = 'Reserva #' . $id;

include __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:860px">
        <div class="page-header">
            <h1>Reserva #<?= e($id) ?></h1>
            <a href="my-reservations.php" class="btn btn-secondary">← Voltar</a>
        </div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <?php if (!$canEdit && in_array($res['status'], ['pending','active'])): ?>
            <div class="alert alert-warning">⚠️ Não pode editar/cancelar esta reserva — faltam menos de 24 horas para o check-in.</div>
        <?php endif; ?>

        <?php if ($action === 'cancel' && $canEdit && $_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
        <div class="alert alert-warning" style="margin-bottom:1.5rem">
            <strong>Tem a certeza que pretende cancelar esta reserva?</strong><br>Esta ação não pode ser desfeita.
        </div>
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <div style="display:flex;gap:1rem">
                <a href="reservation.php?id=<?= $id ?>" class="btn btn-secondary">Não, manter</a>
                <button type="submit" class="btn btn-danger">Sim, cancelar reserva</button>
            </div>
        </form>

        <?php elseif ($action === 'edit' && $canEdit): ?>
        <div class="card" style="padding:1.5rem;margin-bottom:1.5rem">
            <h3 style="margin-bottom:1rem">Editar Reserva</h3>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Check-in *</label>
                        <input type="date" name="check_in" class="form-control" value="<?= e($res['start_date']) ?>" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Check-out *</label>
                        <input type="date" name="check_out" class="form-control" value="<?= e($res['end_date']) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Quartos</label>
                        <input type="number" name="num_rooms" class="form-control" value="<?= e($res['num_rooms']) ?>" min="1" max="5">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hóspedes</label>
                        <input type="number" name="num_guests" class="form-control" value="<?= e($res['num_guests']) ?>" min="1" max="14">
                    </div>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="include_breakfast" value="1" <?= $res['include_breakfast'] ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Incluir Pequeno-Almoço</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">NIF</label>
                    <input type="text" name="nif" class="form-control" value="<?= e($res['nif']) ?>" maxlength="9">
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="2"><?= e($res['notes']) ?></textarea>
                </div>
                <div style="display:flex;gap:1rem">
                    <a href="reservation.php?id=<?= $id ?>" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Alterações</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Reservation details -->
        <div class="detail-grid">
            <div class="detail-card">
                <h3>🏨 Detalhes da Reserva</h3>
                <div class="dl">
                    <dt>Estado</dt>
                    <dd><span class="badge status-<?= e($res['status']) ?>"><?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in feito','completed'=>'Concluída','cancelled'=>'Cancelada'][$res['status']] ?? $res['status'] ?></span></dd>
                    <dt>Tipo de Quarto</dt><dd><?= e($res['type_name']) ?></dd>
                    <dt>Check-in</dt><dd><?= e(formatDate($res['start_date'])) ?></dd>
                    <dt>Check-out</dt><dd><?= e(formatDate($res['end_date'])) ?></dd>
                    <dt>Noites</dt><dd><?= $nights ?></dd>
                    <dt>Quartos</dt><dd><?= e($res['num_rooms']) ?></dd>
                    <dt>Hóspedes</dt><dd><?= e($res['num_guests']) ?></dd>
                    <dt>Pequeno-almoço</dt><dd><?= $res['include_breakfast'] ? 'Sim' : 'Não' ?></dd>
                    <?php if ($res['nif']): ?><dt>NIF</dt><dd><?= e($res['nif']) ?></dd><?php endif; ?>
                    <?php if ($res['notes']): ?><dt>Notas</dt><dd><?= e($res['notes']) ?></dd><?php endif; ?>
                    <dt>Criada em</dt><dd><?= e(formatDatetime($res['created_at'])) ?></dd>
                </div>
            </div>
            <div class="detail-card">
                <h3>💰 Faturação</h3>
                <div class="dl">
                    <dt>Total estimado</dt><dd><strong><?= formatMoney($res['total_estimated']) ?></strong></dd>
                    <dt>Total pago</dt><dd><?= formatMoney($res['total_paid']) ?></dd>
                    <dt>Saldo pendente</dt><dd style="color:<?= ($res['total_estimated'] - $res['total_paid']) > 0 ? '#dc3545' : '#198754' ?>">
                        <?= formatMoney(max(0, $res['total_estimated'] - $res['total_paid'])) ?>
                    </dd>
                </div>
                <?php if (!empty($payments)): ?>
                <div style="margin-top:1rem">
                    <strong style="font-size:.85rem">Pagamentos registados:</strong>
                    <?php foreach ($payments as $p): ?>
                    <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.25rem 0;border-bottom:1px dashed #eee">
                        <span><?= e(formatDate($p['payment_date'])) ?> — <?= e($p['operator_name']) ?></span>
                        <span><?= formatMoney($p['amount']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($canEdit && $action !== 'cancel' && $action !== 'edit'): ?>
        <div style="display:flex;gap:1rem;margin-top:1.5rem">
            <a href="?id=<?= $id ?>&action=edit" class="btn btn-warning">✏️ Editar Reserva</a>
            <a href="?id=<?= $id ?>&action=cancel" class="btn btn-danger">🗑️ Cancelar Reserva</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

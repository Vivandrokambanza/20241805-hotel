<?php
$pageTitle = 'Detalhe de Reserva';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$id     = (int)get('id');
$action = get('action');
$errors = [];

// NEW reservation by admin
if ($action === 'new') {
    $clients   = $pdo->query('SELECT id, name, email FROM users WHERE role = "client" AND status = "active" ORDER BY name')->fetchAll();
    $roomTypes = $pdo->query('SELECT * FROM room_types WHERE status = "active" ORDER BY base_daily_rate')->fetchAll();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verifyCsrf();
        $userId    = (int)post('user_id');
        $rtId      = (int)post('room_type_id');
        $checkIn   = post('check_in');
        $checkOut  = post('check_out');
        $numRooms  = max(1,(int)post('num_rooms'));
        $numGuests = max(1,(int)post('num_guests'));
        $breakfast = (bool)post('include_breakfast');
        $nif       = preg_replace('/\D/', '', post('nif'));
        $notes     = trim(post('notes'));

        if (!$userId)                            $errors[] = 'Selecione um cliente.';
        if (!$rtId)                              $errors[] = 'Selecione um tipo de quarto.';
        if (!validateDate($checkIn))             $errors[] = 'Data de check-in inválida.';
        if (!validateDate($checkOut))            $errors[] = 'Data de check-out inválida.';
        if ($checkIn >= $checkOut)               $errors[] = 'Check-out deve ser posterior ao check-in.';
        if ($nif && !validateNIF($nif))          $errors[] = 'NIF inválido.';

        if (!$errors) {
            $rtStmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?');
            $rtStmt->execute([$rtId]);
            $rt = $rtStmt->fetch();
            if ($numGuests > $rt['max_capacity'] * $numRooms) $errors[] = 'Hóspedes excedem capacidade.';
            $avail = availableRoomCount($rtId, $checkIn, $checkOut);
            if ($avail < $numRooms) $errors[] = "Apenas {$avail} quarto(s) disponível(is).";
        }

        if (!$errors) {
            $rtStmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?');
            $rtStmt->execute([$rtId]);
            $rt    = $rtStmt->fetch();
            $total = calculateTotal($rt, $numRooms, $numGuests, $breakfast, $checkIn, $checkOut);
            $pdo->prepare('INSERT INTO reservations (user_id, room_type_id, num_rooms, num_guests, start_date, end_date, include_breakfast, nif, notes, total_estimated, status) VALUES (?,?,?,?,?,?,?,?,?,?,"active")')
                ->execute([$userId, $rtId, $numRooms, $numGuests, $checkIn, $checkOut, $breakfast?1:0, $nif?:null, $notes?:null, $total]);
            $newId = (int)$pdo->lastInsertId();
            logAction('create_reservation_admin', 'reservations', $newId, "Admin created reservation");
            flash("Reserva #{$newId} criada.");
            redirect('/admin/reservation-detail.php?id=' . $newId);
        }
    }

    include __DIR__ . '/../includes/admin_header.php';
    ?>
    <div class="admin-page-title"><span>+ Nova Reserva</span></div>
    <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
    <div class="card" style="padding:1.5rem;max-width:640px">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
        <div class="form-group">
            <label class="form-label">Cliente *</label>
            <select name="user_id" class="form-control" required>
                <option value="">-- Selecionar cliente --</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Tipo de Quarto *</label>
            <select name="room_type_id" class="form-control" required>
                <option value="">-- Selecionar --</option>
                <?php foreach ($roomTypes as $rt): ?>
                    <option value="<?= $rt['id'] ?>"><?= e($rt['name']) ?> — <?= formatMoney($rt['base_daily_rate']) ?>/noite</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Check-in *</label><input type="date" name="check_in" class="form-control" min="<?= date('Y-m-d') ?>" required></div>
            <div class="form-group"><label class="form-label">Check-out *</label><input type="date" name="check_out" class="form-control" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Quartos</label><input type="number" name="num_rooms" class="form-control" value="1" min="1" max="5"></div>
            <div class="form-group"><label class="form-label">Hóspedes</label><input type="number" name="num_guests" class="form-control" value="1" min="1" max="14"></div>
        </div>
        <div class="form-group">
            <label style="display:flex;gap:.5rem;cursor:pointer;align-items:center">
                <input type="checkbox" name="include_breakfast" value="1">
                <span class="form-label" style="margin:0">Incluir Pequeno-Almoço</span>
            </label>
        </div>
        <div class="form-group"><label class="form-label">NIF</label><input type="text" name="nif" class="form-control" maxlength="9"></div>
        <div class="form-group"><label class="form-label">Notas</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
        <div style="display:flex;gap:1rem">
            <a href="reservations.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Criar Reserva</button>
        </div>
    </form>
    </div>
    <?php include __DIR__ . '/../includes/admin_footer.php'; return;
}

// Load reservation
$stmt = $pdo->prepare('SELECT r.*, rt.name AS type_name, rt.base_daily_rate, rt.base_capacity, rt.max_capacity, rt.breakfast_cost_per_guest, rt.extra_guest_surcharge, u.name AS client_name, u.email AS client_email, u.phone AS client_phone FROM reservations r JOIN room_types rt ON r.room_type_id = rt.id JOIN users u ON r.user_id = u.id WHERE r.id = ?');
$stmt->execute([$id]);
$res = $stmt->fetch();
if (!$res) { flash('Reserva não encontrada.', 'error'); redirect('/admin/reservations.php'); }

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $act = post('_action');

    if ($act === 'confirm' && $res['status'] === 'pending') {
        $pdo->prepare('UPDATE reservations SET status = "active" WHERE id = ?')->execute([$id]);
        logAction('confirm_reservation', 'reservations', $id, 'Admin confirmed');
        flash("Reserva #{$id} confirmada."); redirect('/admin/reservation-detail.php?id=' . $id);
    }
    if ($act === 'cancel' && in_array($res['status'], ['pending','active'])) {
        $pdo->prepare('UPDATE reservations SET status = "cancelled" WHERE id = ?')->execute([$id]);
        logAction('cancel_reservation', 'reservations', $id, 'Admin cancelled');
        flash("Reserva #{$id} cancelada.", 'warning'); redirect('/admin/reservations.php');
    }
    if ($act === 'checkin' && $res['status'] === 'active') {
        // Assign available rooms
        $roomStmt = $pdo->prepare('SELECT id FROM rooms WHERE room_type_id = ? AND status = "available" LIMIT ?');
        $roomStmt->execute([$res['room_type_id'], $res['num_rooms']]);
        $rooms = $roomStmt->fetchAll();
        if (count($rooms) < $res['num_rooms']) {
            flash('Não há quartos disponíveis suficientes para efectuar check-in.', 'error');
            redirect('/admin/reservation-detail.php?id=' . $id);
        }
        foreach ($rooms as $room) {
            $pdo->prepare('INSERT INTO reservation_rooms (reservation_id, room_id, checkin_at) VALUES (?,?,NOW())')->execute([$id, $room['id']]);
            $pdo->prepare('UPDATE rooms SET status = "occupied" WHERE id = ?')->execute([$room['id']]);
        }
        $pdo->prepare('UPDATE reservations SET status = "checked_in" WHERE id = ?')->execute([$id]);
        logAction('checkin', 'reservations', $id, 'Admin performed check-in. Rooms: ' . implode(',', array_column($rooms, 'id')));
        flash("Check-in efetuado para reserva #{$id}.");
        redirect('/admin/reservation-detail.php?id=' . $id);
    }
    if ($act === 'checkout' && $res['status'] === 'checked_in') {
        // Registo do hóspede no checkout (proposta fase 1: "quando o cliente fizer check-out criar hóspede")
        $guestName    = trim(post('guest_name'));
        $guestDocType = post('guest_doc_type') ?: null;
        $guestDocNum  = trim(post('guest_doc_number')) ?: null;
        $guestNif     = preg_replace('/\D/', '', post('guest_nif'));
        $guestPhone   = trim(post('guest_phone')) ?: null;

        if ($guestNif && !validateNIF($guestNif)) {
            $errors[] = 'NIF do hóspede inválido.';
        }
        if ($errors) {
            // Fall through to re-render page with errors
        } else {
            // Update guest profile
            if ($guestName || $guestDocType || $guestDocNum || $guestNif || $guestPhone) {
                $updateFields = [];
                $updateParams = [];
                if ($guestName)    { $updateFields[] = 'name = ?';            $updateParams[] = $guestName; }
                if ($guestDocType) { $updateFields[] = 'document_type = ?';   $updateParams[] = $guestDocType; }
                if ($guestDocNum)  { $updateFields[] = 'document_number = ?'; $updateParams[] = $guestDocNum; }
                if ($guestNif)     { $updateFields[] = 'nif = ?';             $updateParams[] = $guestNif; }
                if ($guestPhone)   { $updateFields[] = 'phone = ?';           $updateParams[] = $guestPhone; }
                if ($updateFields) {
                    $updateParams[] = $res['user_id'];
                    $pdo->prepare('UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?')->execute($updateParams);
                }
            }
            // Mark guest as registered and perform checkout
            $rrStmt = $pdo->prepare('SELECT room_id FROM reservation_rooms WHERE reservation_id = ? AND checkout_at IS NULL');
            $rrStmt->execute([$id]);
            foreach ($rrStmt->fetchAll() as $rr) {
                $pdo->prepare('UPDATE rooms SET status = "available" WHERE id = ?')->execute([$rr['room_id']]);
            }
            $pdo->prepare('UPDATE reservation_rooms SET checkout_at = NOW() WHERE reservation_id = ?')->execute([$id]);
            $pdo->prepare('UPDATE reservations SET status = "completed", guest_registered = 1 WHERE id = ?')->execute([$id]);
            logAction('checkout', 'reservations', $id, 'Check-out + registo hóspede: ' . ($guestName ?: $res['client_name']));
            flash("Check-out efetuado e hóspede registado para reserva #{$id}.");
            redirect('/admin/reservation-detail.php?id=' . $id);
        }
    }
    if ($act === 'edit') {
        $checkIn   = post('check_in');
        $checkOut  = post('check_out');
        $numGuests = max(1,(int)post('num_guests'));
        $numRooms  = max(1,(int)post('num_rooms'));
        $breakfast = (bool)post('include_breakfast');
        $nif       = preg_replace('/\D/', '', post('nif'));
        $notes     = trim(post('notes'));

        if (!validateDate($checkIn)||!validateDate($checkOut)||$checkIn>=$checkOut) $errors[] = 'Datas inválidas.';
        if ($nif && !validateNIF($nif)) $errors[] = 'NIF inválido.';
        if (!$errors) {
            $avail = availableRoomCount($res['room_type_id'], $checkIn, $checkOut, $id);
            if ($avail < $numRooms) $errors[] = "Apenas {$avail} quarto(s) disponível(is).";
        }
        if (!$errors) {
            $rtStmt = $pdo->prepare('SELECT * FROM room_types WHERE id = ?'); $rtStmt->execute([$res['room_type_id']]); $rt = $rtStmt->fetch();
            $total = calculateTotal($rt, $numRooms, $numGuests, $breakfast, $checkIn, $checkOut);
            $pdo->prepare('UPDATE reservations SET start_date=?,end_date=?,num_rooms=?,num_guests=?,include_breakfast=?,nif=?,notes=?,total_estimated=? WHERE id=?')
                ->execute([$checkIn,$checkOut,$numRooms,$numGuests,$breakfast?1:0,$nif?:null,$notes?:null,$total,$id]);
            logAction('edit_reservation', 'reservations', $id, "Admin edited: {$checkIn} to {$checkOut}");
            flash("Reserva #{$id} atualizada."); redirect('/admin/reservation-detail.php?id=' . $id);
        }
    }
    // Reload
    $stmt->execute([$id]); $res = $stmt->fetch();
}

// Payments & assigned rooms
$payments = $pdo->prepare('SELECT p.*, u.name AS op FROM payments p JOIN users u ON p.operator_id = u.id WHERE p.reservation_id = ? ORDER BY p.created_at DESC');
$payments->execute([$id]); $payments = $payments->fetchAll();

$assignedRooms = $pdo->prepare('SELECT rr.*, r.room_number, r.floor FROM reservation_rooms rr JOIN rooms r ON rr.room_id = r.id WHERE rr.reservation_id = ?');
$assignedRooms->execute([$id]); $assignedRooms = $assignedRooms->fetchAll();

$nights = nightsBetween($res['start_date'], $res['end_date']);
$pageTitle = 'Reserva #' . $id;

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="admin-page-title">
    <span>Reserva #<?= e($id) ?></span>
    <a href="reservations.php" class="btn btn-sm btn-secondary">← Voltar</a>
</div>

<?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>

<!-- Action buttons -->
<div style="display:flex;gap:.75rem;flex-wrap:wrap;margin-bottom:1.5rem">
    <?php if ($res['status'] === 'pending'): ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>"><input type="hidden" name="_action" value="confirm">
        <button class="btn btn-success">✓ Confirmar</button>
    </form>
    <?php endif; ?>
    <?php if ($res['status'] === 'active'): ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>"><input type="hidden" name="_action" value="checkin">
        <button class="btn btn-primary">🔑 Efectuar Check-in</button>
    </form>
    <?php endif; ?>
    <?php if ($res['status'] === 'checked_in'): ?>
        <button class="btn btn-warning" onclick="document.getElementById('checkout-form').style.display='block';this.style.display='none'">🚪 Efectuar Check-out + Registar Hóspede</button>
    <?php endif; ?>
    <?php if (in_array($res['status'], ['pending','active'])): ?>
    <form method="post"><input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>"><input type="hidden" name="_action" value="cancel">
        <button class="btn btn-danger" data-confirm="Cancelar esta reserva?">✕ Cancelar Reserva</button>
    </form>
    <?php endif; ?>
    <a href="payments.php?reservation_id=<?= $id ?>" class="btn btn-secondary">💰 Registar Pagamento</a>
</div>

<?php if ($res['status'] === 'checked_in'): ?>
<!-- Formulário de Checkout com Registo de Hóspede (proposta fase 1) -->
<div id="checkout-form" style="display:<?= !empty($errors) ? 'block' : 'none' ?>" class="detail-card" style="margin-bottom:1.5rem">
    <h3>🚪 Check-out + Registo do Hóspede</h3>
    <p style="color:#555;font-size:.9rem;margin:.5rem 0 1rem">
        Confirme ou complete os dados do hóspede antes de efectuar o check-out. Este registo formaliza a estadia no sistema do hotel.
    </p>
    <?php
    $guestStmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $guestStmt->execute([$res['user_id']]);
    $guestData = $guestStmt->fetch();
    ?>
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
        <input type="hidden" name="_action" value="checkout">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Nome do Hóspede</label>
                <input type="text" name="guest_name" class="form-control" value="<?= e($guestData['name']) ?>" placeholder="Nome completo">
            </div>
            <div class="form-group">
                <label class="form-label">Telefone</label>
                <input type="tel" name="guest_phone" class="form-control" value="<?= e($guestData['phone']) ?>" placeholder="+351 9XX XXX XXX">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo de Documento</label>
                <select name="guest_doc_type" class="form-control">
                    <option value="">-- Selecionar --</option>
                    <option value="cc" <?= $guestData['document_type']==='cc'?'selected':'' ?>>Cartão de Cidadão</option>
                    <option value="passport" <?= $guestData['document_type']==='passport'?'selected':'' ?>>Passaporte</option>
                    <option value="other" <?= $guestData['document_type']==='other'?'selected':'' ?>>Outro</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Número do Documento</label>
                <input type="text" name="guest_doc_number" class="form-control" value="<?= e($guestData['document_number']) ?>" placeholder="Número do documento de identificação">
            </div>
        </div>
        <div class="form-group">
            <label class="form-label">NIF <span style="font-weight:400;color:#888">(opcional, apenas para clientes portugueses)</span></label>
            <input type="text" name="guest_nif" class="form-control" value="<?= e($guestData['nif']) ?>" maxlength="9" placeholder="123456789">
        </div>
        <div style="display:flex;gap:.75rem;align-items:center">
            <button type="submit" class="btn btn-warning">✓ Confirmar Check-out e Registar Hóspede</button>
            <button type="button" onclick="document.getElementById('checkout-form').style.display='none';document.querySelector('[onclick*=checkout-form]').style.display='inline-block'" class="btn btn-secondary">Cancelar</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="detail-grid">
    <div class="detail-card">
        <h3>🏨 Detalhes</h3>
        <div class="dl">
            <dt>Estado</dt><dd><span class="badge status-<?= e($res['status']) ?>"><?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in','completed'=>'Concluída','cancelled'=>'Cancelada'][$res['status']] ?? $res['status'] ?></span></dd>
            <dt>Tipo</dt><dd><?= e($res['type_name']) ?></dd>
            <dt>Check-in</dt><dd><?= e(formatDate($res['start_date'])) ?></dd>
            <dt>Check-out</dt><dd><?= e(formatDate($res['end_date'])) ?></dd>
            <dt>Noites</dt><dd><?= $nights ?></dd>
            <dt>Quartos</dt><dd><?= e($res['num_rooms']) ?></dd>
            <dt>Adultos</dt><dd><?= e($res['num_guests']) ?></dd>
            <?php if (!empty($res['num_children'])): ?><dt>Crianças &lt;3</dt><dd><?= e($res['num_children']) ?> <span class="badge badge-green">Gratuitas</span></dd><?php endif; ?>
            <dt>P. Almoço</dt><dd><?= $res['include_breakfast'] ? 'Sim' : 'Não' ?></dd>
            <dt>Hóspede Reg.</dt><dd><?= $res['guest_registered'] ? '<span class="badge badge-green">✓ Registado</span>' : '<span class="badge badge-orange">Pendente</span>' ?></dd>
            <?php if ($res['nif']): ?><dt>NIF</dt><dd><?= e($res['nif']) ?></dd><?php endif; ?>
            <?php if ($res['notes']): ?><dt>Notas</dt><dd><?= e($res['notes']) ?></dd><?php endif; ?>
            <dt>Criada</dt><dd><?= e(formatDatetime($res['created_at'])) ?></dd>
        </div>
    </div>
    <div class="detail-card">
        <h3>👤 Cliente</h3>
        <div class="dl">
            <dt>Nome</dt><dd><?= e($res['client_name']) ?></dd>
            <dt>Email</dt><dd><?= e($res['client_email']) ?></dd>
            <?php if ($res['client_phone']): ?><dt>Tel.</dt><dd><?= e($res['client_phone']) ?></dd><?php endif; ?>
        </div>
        <h3 style="margin-top:1.25rem">💰 Faturação</h3>
        <div class="dl">
            <dt>Total est.</dt><dd><strong><?= formatMoney($res['total_estimated']) ?></strong></dd>
            <dt>Total pago</dt><dd><?= formatMoney($res['total_paid']) ?></dd>
            <dt>Pendente</dt><dd style="color:<?= ($res['total_estimated']-$res['total_paid'])>0?'#dc3545':'#198754' ?>"><?= formatMoney(max(0,$res['total_estimated']-$res['total_paid'])) ?></dd>
        </div>
    </div>
</div>

<!-- Assigned rooms -->
<?php if (!empty($assignedRooms)): ?>
<div class="detail-card" style="margin-top:1.25rem">
    <h3>🚪 Quartos Atribuídos</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Quarto</th><th>Piso</th><th>Check-in</th><th>Check-out</th></tr></thead>
            <tbody>
            <?php foreach ($assignedRooms as $rr): ?>
            <tr><td><?= e($rr['room_number']) ?></td><td><?= e($rr['floor']) ?></td><td><?= e(formatDatetime($rr['checkin_at'])) ?></td><td><?= $rr['checkout_at'] ? e(formatDatetime($rr['checkout_at'])) : '—' ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Payments -->
<?php if (!empty($payments)): ?>
<div class="detail-card" style="margin-top:1.25rem">
    <h3>💳 Pagamentos</h3>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Data</th><th>Montante</th><th>Tipo</th><th>Método</th><th>Operador</th><th>Notas</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($payments as $p): ?>
            <tr>
                <td><?= e(formatDate($p['payment_date'])) ?></td>
                <td><?= formatMoney($p['amount']) ?></td>
                <td><?= $p['payment_type'] === 'total' ? 'Total' : 'Parcial' ?></td>
                <td><?= ucfirst($p['payment_method']) ?></td>
                <td><?= e($p['op']) ?></td>
                <td><?= e($p['notes']) ?></td>
                <td><a href="comprovativo.php?id=<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-secondary" title="Comprovativo">🖨️</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Edit form (admin can always edit non-completed/non-cancelled) -->
<?php if (in_array($res['status'], ['pending','active'])): ?>
<div class="detail-card" style="margin-top:1.25rem">
    <h3>✏️ Editar Reserva</h3>
    <form method="post" style="margin-top:.75rem">
        <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
        <input type="hidden" name="_action" value="edit">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Check-in</label><input type="date" name="check_in" class="form-control" value="<?= e($res['start_date']) ?>" required></div>
            <div class="form-group"><label class="form-label">Check-out</label><input type="date" name="check_out" class="form-control" value="<?= e($res['end_date']) ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Quartos</label><input type="number" name="num_rooms" class="form-control" value="<?= e($res['num_rooms']) ?>" min="1"></div>
            <div class="form-group"><label class="form-label">Hóspedes</label><input type="number" name="num_guests" class="form-control" value="<?= e($res['num_guests']) ?>" min="1"></div>
        </div>
        <div class="form-group">
            <label style="display:flex;gap:.5rem;cursor:pointer;align-items:center"><input type="checkbox" name="include_breakfast" value="1" <?= $res['include_breakfast']?'checked':'' ?>><span class="form-label" style="margin:0">Pequeno-Almoço</span></label>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">NIF</label><input type="text" name="nif" class="form-control" value="<?= e($res['nif']) ?>" maxlength="9"></div>
            <div class="form-group"><label class="form-label">Notas</label><textarea name="notes" class="form-control" rows="1"><?= e($res['notes']) ?></textarea></div>
        </div>
        <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
    </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

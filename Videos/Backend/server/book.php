<?php
$pageTitle = 'Fazer Reserva';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
requireRole('client');

$pdo = getDB();

// Load room types
$roomTypes = $pdo->query('SELECT * FROM room_types WHERE status = "active" ORDER BY base_daily_rate ASC')->fetchAll();
$rtMap = [];
foreach ($roomTypes as $rt) $rtMap[$rt['id']] = $rt;

$errors = [];
$step   = (int)(post('step') ?: 1);

// Step 1 → step 2: validate dates & type
$rtId       = (int)(post('room_type_id') ?: get('room_type_id'));
$checkIn    = post('check_in') ?: get('check_in');
$checkOut   = post('check_out') ?: get('check_out');
$numGuests  = max(1, (int)(post('num_guests') ?: get('guests', 1)));
$numChildren= max(0, (int)post('num_children'));
$numRooms   = max(1, (int)post('num_rooms', 1));
$breakfast  = (bool)post('include_breakfast');
$nif        = preg_replace('/\D/', '', post('nif'));
$notes      = trim(post('notes'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($step === 2) {
        // Validate step 2 fields
        if (!$rtId || !isset($rtMap[$rtId]))       $errors[] = 'Selecione um tipo de quarto.';
        if (!validateDate($checkIn))                $errors[] = 'Data de check-in inválida.';
        if (!validateDate($checkOut))               $errors[] = 'Data de check-out inválida.';
        if ($checkIn >= $checkOut)                  $errors[] = 'Check-out deve ser posterior ao check-in.';
        if ($checkIn < date('Y-m-d'))               $errors[] = 'A data de check-in não pode ser no passado.';
        if ($nif && !validateNIF($nif))             $errors[] = 'NIF inválido.';

        if (!$errors) {
            $rt = $rtMap[$rtId];
            if ($numGuests > $rt['max_capacity'] * $numRooms) {
                $errors[] = 'Número de hóspedes excede a capacidade máxima (' . ($rt['max_capacity'] * $numRooms) . ').';
            }
            $avail = availableRoomCount($rtId, $checkIn, $checkOut);
            if ($avail < $numRooms) {
                $errors[] = "Apenas {$avail} quarto(s) disponível(is) desse tipo nessas datas.";
            }
        }

        if (!$errors) {
            $step = 3; // Advance to confirmation
        } else {
            $step = 2;
        }
    }

    if ($step === 3 && post('confirm') === '1') {
        // Final: create reservation
        if (!$rtId || !validateDate($checkIn) || !validateDate($checkOut) || $checkIn >= $checkOut) {
            $errors[] = 'Dados inválidos. Tente novamente.';
            $step = 1;
        } else {
            $rt    = $rtMap[$rtId];
            $total = calculateTotal($rt, $numRooms, $numGuests, $breakfast, $checkIn, $checkOut, $numChildren);
            $userId = currentUser()['id'];

            $stmt = $pdo->prepare('
                INSERT INTO reservations (user_id, room_type_id, num_rooms, num_guests, num_children, start_date, end_date, include_breakfast, nif, total_estimated, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?)
            ');
            $stmt->execute([$userId, $rtId, $numRooms, $numGuests, $numChildren, $checkIn, $checkOut, $breakfast ? 1 : 0, $nif ?: null, $total, $notes ?: null]);
            $resId = (int)$pdo->lastInsertId();
            logAction('create_reservation', 'reservations', $resId, "Reservation created: {$checkIn} to {$checkOut}, type {$rt['name']}");

            flash("Reserva #{$resId} criada com sucesso! Aguarda confirmação do hotel.");
            redirect('/my-reservations.php');
        }
    }
}

if ($step < 2) $step = 1;

include __DIR__ . '/includes/header.php';

$rtSelected = ($rtId && isset($rtMap[$rtId])) ? $rtMap[$rtId] : null;
$nights     = ($checkIn && $checkOut && $checkIn < $checkOut) ? nightsBetween($checkIn, $checkOut) : 0;
$totalEst   = $rtSelected ? calculateTotal($rtSelected, $numRooms, $numGuests, $breakfast, $checkIn, $checkOut, $numChildren) : 0;
?>

<section class="section">
    <div class="container" style="max-width:720px">
        <h1 class="section-title">Fazer Reserva</h1>

        <div class="wizard-steps">
            <div class="wizard-step <?= $step >= 1 ? ($step > 1 ? 'done' : 'active') : '' ?>">1. Quarto & Datas</div>
            <div class="wizard-step <?= $step >= 2 ? ($step > 2 ? 'done' : 'active') : '' ?>">2. Detalhes</div>
            <div class="wizard-step <?= $step >= 3 ? 'active' : '' ?>">3. Confirmar</div>
        </div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <div class="policy-box">
            <strong>⚠️ Política de Cancelamento</strong>
            Pode editar ou cancelar a sua reserva até <strong>24 horas antes do check-in</strong>. Alterações tardias não são permitidas.
        </div>

        <?php if ($step === 1 || $step === 2): ?>
        <form method="post" action="book.php" id="booking-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <input type="hidden" name="step" value="2">

            <div class="card" style="padding:1.5rem">
                <div class="form-group">
                    <label class="form-label">Tipo de Quarto *</label>
                    <select name="room_type_id" class="form-control" required>
                        <option value="">-- Selecione --</option>
                        <?php foreach ($roomTypes as $rt): ?>
                            <option value="<?= $rt['id'] ?>" <?= $rtId == $rt['id'] ? 'selected' : '' ?>>
                                <?= e($rt['name']) ?> — <?= formatMoney($rt['base_daily_rate']) ?>/noite (até <?= $rt['max_capacity'] ?> hóspedes)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Check-in *</label>
                        <input type="date" name="check_in" class="form-control" value="<?= e($checkIn) ?>"
                            min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Check-out *</label>
                        <input type="date" name="check_out" class="form-control" value="<?= e($checkOut) ?>"
                            min="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Número de quartos *</label>
                        <input type="number" name="num_rooms" class="form-control" value="<?= e($numRooms) ?>" min="1" max="5" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hóspedes adultos *</label>
                        <input type="number" name="num_guests" class="form-control" value="<?= e($numGuests) ?>" min="1" max="14" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Crianças com menos de 3 anos
                        <span class="badge badge-green" style="margin-left:.4rem">Gratuitas</span>
                    </label>
                    <input type="number" name="num_children" class="form-control" value="<?= e($numChildren) ?>" min="0" max="10">
                    <p class="form-hint">Crianças com menos de 3 anos não pagam pequeno-almoço nem suplemento.</p>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:.5rem;cursor:pointer">
                        <input type="checkbox" name="include_breakfast" value="1" <?= $breakfast ? 'checked' : '' ?>>
                        <span class="form-label" style="margin:0">Incluir Pequeno-Almoço (10 € / adulto / noite)</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="form-label">NIF <span style="font-weight:400;color:#888">(opcional, para faturação portuguesa)</span></label>
                    <input type="text" name="nif" class="form-control" value="<?= e($nif) ?>" maxlength="9" placeholder="123456789">
                </div>
                <div class="form-group">
                    <label class="form-label">Notas / pedidos especiais</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Aniversário, cama extra, etc."><?= e($notes) ?></textarea>
                </div>
            </div>

            <?php if ($rtSelected && $nights > 0): ?>
            <div class="booking-summary mt-2">
                <h4 style="margin-bottom:.75rem">Estimativa de Preço</h4>
                <div class="summary-row"><span>Tipo</span><span><?= e($rtSelected['name']) ?></span></div>
                <div class="summary-row"><span>Noites</span><span><?= $nights ?></span></div>
                <div class="summary-row"><span><?= $numRooms ?> quarto(s) × <?= formatMoney($rtSelected['base_daily_rate']) ?> × <?= $nights ?> noites</span><span><?= formatMoney($rtSelected['base_daily_rate'] * $numRooms * $nights) ?></span></div>
                <?php $extra = max(0, $numGuests - $rtSelected['base_capacity'] * $numRooms); if ($extra > 0): ?>
                <div class="summary-row"><span>Suplemento extra (<?= $extra ?> hóspede<?= $extra > 1 ? 's' : '' ?>)</span><span><?= formatMoney($extra * $rtSelected['extra_guest_surcharge'] * $nights) ?></span></div>
                <?php endif; ?>
                <?php if ($breakfast): ?>
                <?php $payingAdults = max(0, $numGuests - $numChildren); ?>
                <div class="summary-row">
                    <span>Pequeno-almoço (<?= $payingAdults ?> adulto<?= $payingAdults>1?'s':'' ?> × <?= formatMoney($rtSelected['breakfast_cost_per_guest']) ?> × <?= $nights ?> noites)</span>
                    <span><?= formatMoney($rtSelected['breakfast_cost_per_guest'] * $payingAdults * $nights) ?></span>
                </div>
                <?php if ($numChildren > 0): ?>
                <div class="summary-row"><span>Crianças &lt;3 anos (<?= $numChildren ?>)</span><span class="badge badge-green">Gratuitas</span></div>
                <?php endif; ?>
                <?php endif; ?>
                <div class="summary-row"><span><strong>Total estimado</strong></span><span class="total"><?= formatMoney($totalEst) ?></span></div>
            </div>
            <?php endif; ?>

            <div style="display:flex;gap:1rem;margin-top:1.25rem">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary btn-lg" style="flex:1">Continuar →</button>
            </div>
        </form>

        <?php elseif ($step === 3): ?>
        <div class="card" style="padding:1.5rem">
            <h3 style="margin-bottom:1rem">Confirmar Reserva</h3>
            <div class="booking-summary">
                <div class="summary-row"><span>Tipo de quarto</span><span><?= e($rtSelected['name'] ?? '') ?></span></div>
                <div class="summary-row"><span>Check-in</span><span><?= e(formatDate($checkIn)) ?></span></div>
                <div class="summary-row"><span>Check-out</span><span><?= e(formatDate($checkOut)) ?></span></div>
                <div class="summary-row"><span>Noites</span><span><?= $nights ?></span></div>
                <div class="summary-row"><span>Quartos</span><span><?= $numRooms ?></span></div>
                <div class="summary-row"><span>Adultos</span><span><?= $numGuests ?></span></div>
                <?php if ($numChildren > 0): ?>
                <div class="summary-row"><span>Crianças &lt;3 anos</span><span><?= $numChildren ?> <span class="badge badge-green">Gratuitas</span></span></div>
                <?php endif; ?>
                <div class="summary-row"><span>Pequeno-almoço</span><span><?= $breakfast ? 'Sim' : 'Não' ?></span></div>
                <?php if ($nif): ?>
                <div class="summary-row"><span>NIF</span><span><?= e($nif) ?></span></div>
                <?php endif; ?>
                <div class="summary-row"><span><strong>Total estimado</strong></span><span class="total"><?= formatMoney($totalEst) ?></span></div>
            </div>
            <div class="alert alert-info" style="margin-top:1rem">
                O pagamento será registado pela receção no momento do check-in ou de acordo com as condições acordadas.
            </div>
        </div>

        <form method="post" action="book.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <input type="hidden" name="step" value="3">
            <input type="hidden" name="confirm" value="1">
            <input type="hidden" name="room_type_id" value="<?= e($rtId) ?>">
            <input type="hidden" name="check_in" value="<?= e($checkIn) ?>">
            <input type="hidden" name="check_out" value="<?= e($checkOut) ?>">
            <input type="hidden" name="num_rooms" value="<?= e($numRooms) ?>">
            <input type="hidden" name="num_guests" value="<?= e($numGuests) ?>">
            <input type="hidden" name="num_children" value="<?= e($numChildren) ?>">
            <input type="hidden" name="include_breakfast" value="<?= $breakfast ? '1' : '0' ?>">
            <input type="hidden" name="nif" value="<?= e($nif) ?>">
            <input type="hidden" name="notes" value="<?= e($notes) ?>">
            <div style="display:flex;gap:1rem;margin-top:1.25rem">
                <button type="button" onclick="history.back()" class="btn btn-secondary">← Voltar</button>
                <button type="submit" class="btn btn-success btn-lg" style="flex:1">✓ Confirmar Reserva</button>
            </div>
        </form>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

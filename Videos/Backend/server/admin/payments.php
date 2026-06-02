<?php
$pageTitle = 'Pagamentos';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB();

$reservationId = (int)get('reservation_id');
$errors = [];

// Register payment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $resId    = (int)post('reservation_id');
    $amount   = (float)post('amount');
    $date     = post('payment_date');
    $type     = post('payment_type');
    $method   = post('payment_method');
    $notes    = trim(post('notes'));

    if (!$resId)                              $errors[] = 'Reserva inválida.';
    if ($amount <= 0)                         $errors[] = 'Montante inválido.';
    if (!validateDate($date))                 $errors[] = 'Data inválida.';
    if (!in_array($type, ['partial','total'])) $errors[] = 'Tipo inválido.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT * FROM reservations WHERE id = ?');
        $stmt->execute([$resId]);
        $res = $stmt->fetch();
        if (!$res) { $errors[] = 'Reserva não encontrada.'; }
    }

    if (!$errors) {
        $opId = currentUser()['id'];
        $pdo->prepare('INSERT INTO payments (reservation_id, amount, payment_date, payment_type, payment_method, operator_id, notes) VALUES (?,?,?,?,?,?,?)')
            ->execute([$resId, $amount, $date, $type, $method, $opId, $notes ?: null]);

        // Update total_paid
        $pdo->prepare('UPDATE reservations SET total_paid = (SELECT COALESCE(SUM(amount),0) FROM payments WHERE reservation_id = ?) WHERE id = ?')
            ->execute([$resId, $resId]);

        logAction('payment', 'payments', (int)$pdo->lastInsertId(), "Payment {$amount} for reservation #{$resId}");
        flash("Pagamento de " . formatMoney($amount) . " registado para reserva #{$resId}.");
        redirect('/admin/reservation-detail.php?id=' . $resId);
    }
}

// Load reservations for the dropdown (only active/checked_in/pending)
$activeRes = $pdo->query('SELECT r.id, u.name AS client_name, r.start_date, r.total_estimated, r.total_paid FROM reservations r JOIN users u ON r.user_id = u.id WHERE r.status IN ("pending","active","checked_in") ORDER BY r.start_date ASC')->fetchAll();

// Recent payments
$search = trim(get('search'));
$sqlP = 'SELECT p.*, r.id AS res_id, u_c.name AS client_name, u_o.name AS op_name FROM payments p JOIN reservations r ON p.reservation_id = r.id JOIN users u_c ON r.user_id = u_c.id JOIN users u_o ON p.operator_id = u_o.id';
$paramsP = [];
if ($search) { $sqlP .= ' WHERE u_c.name LIKE ? OR r.id = ?'; $paramsP = ["%{$search}%", (int)$search]; }
$sqlP .= ' ORDER BY p.created_at DESC LIMIT 50';
$stmtP = $pdo->prepare($sqlP);
$stmtP->execute($paramsP);
$payments = $stmtP->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">💰 Pagamentos</div>

<div class="grid-2" style="gap:1.5rem">
    <div>
        <div class="detail-card">
            <h3>Registar Novo Pagamento</h3>
            <?php foreach ($errors as $e_): ?><div class="alert alert-error" style="margin:.5rem 0"><?= e($e_) ?></div><?php endforeach; ?>
            <form method="post" style="margin-top:.75rem">
                <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
                <div class="form-group">
                    <label class="form-label">Reserva *</label>
                    <select name="reservation_id" class="form-control" required>
                        <option value="">-- Selecionar reserva --</option>
                        <?php foreach ($activeRes as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $reservationId === $r['id'] ? 'selected' : '' ?>>
                                #<?= $r['id'] ?> — <?= e($r['client_name']) ?> (<?= e(formatDate($r['start_date'])) ?>) — Pendente: <?= formatMoney(max(0, $r['total_estimated'] - $r['total_paid'])) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Montante (€) *</label>
                        <input type="number" name="amount" class="form-control" min="0.01" step="0.01" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Data *</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tipo *</label>
                        <select name="payment_type" class="form-control" required>
                            <option value="partial">Parcial</option>
                            <option value="total">Total</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Método</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Numerário</option>
                            <option value="card">Cartão</option>
                            <option value="transfer">Transferência</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Observações sobre o pagamento"></textarea>
                </div>
                <button type="submit" class="btn btn-success">💳 Registar Pagamento</button>
            </form>
        </div>
    </div>

    <div>
        <div class="page-header">
            <h3 style="font-size:1rem;font-weight:700">Histórico de Pagamentos</h3>
            <form method="get" style="display:flex;gap:.5rem">
                <input type="text" name="search" class="form-control form-control-sm" value="<?= e($search) ?>" placeholder="Pesquisar...">
                <button class="btn btn-sm btn-secondary">🔍</button>
            </form>
        </div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Data</th><th>Reserva</th><th>Cliente</th><th>Montante</th><th>Tipo</th><th>Operador</th></tr></thead>
                <tbody>
                <?php if (empty($payments)): ?>
                    <tr><td colspan="6" class="text-center" style="padding:1.5rem;color:#888">Nenhum pagamento registado.</td></tr>
                <?php endif; ?>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e(formatDate($p['payment_date'])) ?></td>
                    <td><a href="reservation-detail.php?id=<?= $p['res_id'] ?>">#<?= $p['res_id'] ?></a></td>
                    <td><?= e($p['client_name']) ?></td>
                    <td><strong><?= formatMoney($p['amount']) ?></strong></td>
                    <td><?= $p['payment_type'] === 'total' ? 'Total' : 'Parcial' ?></td>
                    <td><?= e($p['op_name']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

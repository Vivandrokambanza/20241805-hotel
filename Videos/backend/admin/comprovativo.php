<?php
$pageTitle = 'Comprovativo de Pagamento';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('receptionist', 'manager');
$pdo = getDB();

$paymentId = (int)get('id');
if (!$paymentId) {
    flash('Comprovativo não encontrado.', 'error');
    redirect('/admin/payments.php');
}

$stmt = $pdo->prepare('
    SELECT p.*,
           r.id AS res_id, r.start_date, r.end_date, r.num_rooms, r.num_guests,
           r.include_breakfast, r.total_estimated, r.total_paid, r.status AS res_status,
           rt.name AS room_type,
           u_c.name AS client_name, u_c.email AS client_email, u_c.nif AS client_nif,
           u_c.document_type, u_c.document_number,
           u_o.name AS operator_name
    FROM payments p
    JOIN reservations r  ON p.reservation_id = r.id
    JOIN room_types rt   ON r.room_type_id = rt.id
    JOIN users u_c       ON r.user_id = u_c.id
    JOIN users u_o       ON p.operator_id = u_o.id
    WHERE p.id = ?
');
$stmt->execute([$paymentId]);
$p = $stmt->fetch();

if (!$p) {
    flash('Comprovativo não encontrado.', 'error');
    redirect('/admin/payments.php');
}

$nights = nightsBetween($p['start_date'], $p['end_date']);
$remaining = max(0, $p['total_estimated'] - $p['total_paid']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovativo #<?= e($paymentId) ?> — Hotel Vivandro</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #222; background: #f5f5f5; }
        .receipt-wrap { max-width: 680px; margin: 2rem auto; background: #fff; border: 1px solid #ddd; padding: 2.5rem; }
        .receipt-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0d1b2a; padding-bottom: 1.25rem; margin-bottom: 1.5rem; }
        .hotel-name { font-size: 1.5rem; font-weight: 700; color: #0d1b2a; }
        .hotel-sub { color: #666; font-size: .85rem; margin-top: .25rem; }
        .receipt-title { text-align: right; }
        .receipt-title h2 { font-size: 1.1rem; color: #0d1b2a; }
        .receipt-title .ref { font-size: .85rem; color: #888; margin-top: .2rem; }
        .section-title { font-weight: 700; font-size: .8rem; text-transform: uppercase; letter-spacing: .05em; color: #555; border-bottom: 1px solid #eee; padding-bottom: .35rem; margin: 1.25rem 0 .75rem; }
        .dl { display: grid; grid-template-columns: 1fr 1fr; gap: .3rem .75rem; }
        .dl dt { color: #666; }
        .dl dd { font-weight: 500; }
        .amount-box { background: #f0f4f8; border: 1px solid #c8d6e5; border-radius: 6px; padding: 1rem 1.25rem; margin: 1.25rem 0; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: .5rem; text-align: center; }
        .amount-box .val { font-size: 1.2rem; font-weight: 700; color: #0d1b2a; }
        .amount-box .lbl { font-size: .75rem; color: #666; margin-top: .2rem; }
        .amount-box .paid { color: #198754; }
        .amount-box .pending { color: <?= $remaining > 0 ? '#dc3545' : '#198754' ?>; }
        .footer-note { font-size: .78rem; color: #999; border-top: 1px solid #eee; padding-top: 1rem; margin-top: 1.5rem; }
        .actions { margin-bottom: 1.5rem; display: flex; gap: .75rem; }
        .btn { padding: .45rem 1rem; border: none; border-radius: 5px; cursor: pointer; font-size: .85rem; text-decoration: none; display: inline-block; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e5e7eb; color: #374151; }
        @media print {
            body { background: #fff; }
            .actions { display: none; }
            .receipt-wrap { border: none; margin: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

<div class="receipt-wrap">
    <div class="actions">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Imprimir</button>
        <a href="reservation-detail.php?id=<?= $p['res_id'] ?>" class="btn btn-secondary">← Voltar à Reserva</a>
    </div>

    <div class="receipt-header">
        <div>
            <div class="hotel-name">🏨 Hotel Vivandro</div>
            <div class="hotel-sub">Lisboa, Portugal &nbsp;|&nbsp; hotel@vivandro.pt &nbsp;|&nbsp; +351 21 000 0000</div>
        </div>
        <div class="receipt-title">
            <h2>Comprovativo de Pagamento</h2>
            <div class="ref">Ref. PAG-<?= str_pad($paymentId, 6, '0', STR_PAD_LEFT) ?></div>
            <div class="ref">Emitido em: <?= date('d/m/Y H:i') ?></div>
        </div>
    </div>

    <!-- Guest info -->
    <div class="section-title">Dados do Hóspede</div>
    <div class="dl">
        <dt>Nome</dt><dd><?= e($p['client_name']) ?></dd>
        <dt>Email</dt><dd><?= e($p['client_email']) ?></dd>
        <?php if ($p['document_type'] && $p['document_number']): ?>
        <dt>Documento</dt><dd><?= e(strtoupper($p['document_type'])) ?> <?= e($p['document_number']) ?></dd>
        <?php endif; ?>
        <?php if ($p['client_nif']): ?>
        <dt>NIF</dt><dd><?= e($p['client_nif']) ?></dd>
        <?php endif; ?>
    </div>

    <!-- Reservation info -->
    <div class="section-title">Reserva #<?= e($p['res_id']) ?></div>
    <div class="dl">
        <dt>Tipo de Quarto</dt><dd><?= e($p['room_type']) ?></dd>
        <dt>Quartos</dt><dd><?= e($p['num_rooms']) ?></dd>
        <dt>Check-in</dt><dd><?= e(formatDate($p['start_date'])) ?></dd>
        <dt>Check-out</dt><dd><?= e(formatDate($p['end_date'])) ?></dd>
        <dt>Noites</dt><dd><?= $nights ?></dd>
        <dt>Hóspedes</dt><dd><?= e($p['num_guests']) ?></dd>
        <dt>Pequeno-Almoço</dt><dd><?= $p['include_breakfast'] ? 'Incluído' : 'Não incluído' ?></dd>
        <dt>Estado da Reserva</dt><dd><?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in','completed'=>'Concluída','cancelled'=>'Cancelada'][$p['res_status']] ?? $p['res_status'] ?></dd>
    </div>

    <!-- Payment amounts -->
    <div class="amount-box">
        <div>
            <div class="val"><?= formatMoney($p['total_estimated']) ?></div>
            <div class="lbl">Total da Reserva</div>
        </div>
        <div>
            <div class="val paid"><?= formatMoney($p['amount']) ?></div>
            <div class="lbl">Pago Agora</div>
        </div>
        <div>
            <div class="val pending"><?= formatMoney($remaining) ?></div>
            <div class="lbl"><?= $remaining > 0 ? 'Saldo Pendente' : 'Liquidado ✓' ?></div>
        </div>
    </div>

    <!-- Payment detail -->
    <div class="section-title">Detalhe do Pagamento</div>
    <div class="dl">
        <dt>Data</dt><dd><?= e(formatDate($p['payment_date'])) ?></dd>
        <dt>Tipo</dt><dd><?= $p['payment_type'] === 'total' ? 'Total' : 'Parcial' ?></dd>
        <dt>Método</dt><dd><?= ['cash'=>'Numerário','card'=>'Cartão','transfer'=>'Transferência'][$p['payment_method']] ?? $p['payment_method'] ?></dd>
        <dt>Operador</dt><dd><?= e($p['operator_name']) ?></dd>
        <?php if ($p['notes']): ?>
        <dt>Notas</dt><dd><?= e($p['notes']) ?></dd>
        <?php endif; ?>
    </div>

    <div class="footer-note">
        Este documento é um comprovativo simulado emitido pelo sistema de gestão do Hotel Vivandro.
        Não tem validade fiscal. Para efeitos de faturação, solicite fatura à receção.
        &nbsp;|&nbsp; <?= date('d/m/Y H:i:s') ?>
    </div>
</div>

</body>
</html>

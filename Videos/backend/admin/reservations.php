<?php
$pageTitle = 'Reservas';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('receptionist', 'manager');
$pdo = getDB();

$status    = get('status', 'all');
$search    = trim(get('search'));
$dateFrom  = get('date_from');
$dateTo    = get('date_to');

$sql    = 'SELECT r.*, rt.name AS type_name, u.name AS client_name, u.email AS client_email FROM reservations r JOIN room_types rt ON r.room_type_id = rt.id JOIN users u ON r.user_id = u.id WHERE 1=1';
$params = [];

if ($status !== 'all') { $sql .= ' AND r.status = ?'; $params[] = $status; }
if ($search) { $sql .= ' AND (u.name LIKE ? OR u.email LIKE ? OR r.id LIKE ?)'; $like = "%{$search}%"; $params = array_merge($params, [$like, $like, $like]); }
if ($dateFrom && validateDate($dateFrom)) { $sql .= ' AND r.start_date >= ?'; $params[] = $dateFrom; }
if ($dateTo   && validateDate($dateTo))   { $sql .= ' AND r.start_date <= ?'; $params[] = $dateTo; }
$sql .= ' ORDER BY r.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">
    <span>📅 Reservas</span>
    <a href="reservation-detail.php?action=new" class="btn btn-primary">+ Nova Reserva</a>
</div>

<div class="filter-bar">
    <div class="form-group">
        <label class="form-label">Estado</label>
        <select class="form-control" onchange="applyFilter('status', this.value)">
            <?php foreach (['all'=>'Todos','pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in','completed'=>'Concluída','cancelled'=>'Cancelada'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Pesquisar</label>
        <form method="get" style="display:flex;gap:.5rem">
            <input type="hidden" name="status" value="<?= e($status) ?>">
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Nome, email ou #ID">
            <button type="submit" class="btn btn-primary btn-sm">🔍</button>
        </form>
    </div>
    <div class="form-group">
        <label class="form-label">De</label>
        <input type="date" class="form-control" value="<?= e($dateFrom) ?>" onchange="applyFilter('date_from', this.value)">
    </div>
    <div class="form-group">
        <label class="form-label">Até</label>
        <input type="date" class="form-control" value="<?= e($dateTo) ?>" onchange="applyFilter('date_to', this.value)">
    </div>
</div>

<div class="table-wrapper">
    <table>
        <thead>
            <tr>
                <th>#</th><th>Cliente</th><th>Tipo</th><th>Check-in</th><th>Check-out</th>
                <th>Quartos</th><th>Hóspedes</th><th>Total</th><th>Estado</th><th>Ações</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($reservations)): ?>
            <tr><td colspan="10" class="text-center" style="padding:2rem;color:#888">Nenhuma reserva encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($reservations as $r): ?>
            <tr>
                <td><strong>#<?= e($r['id']) ?></strong></td>
                <td>
                    <div><?= e($r['client_name']) ?></div>
                    <div style="font-size:.78rem;color:#888"><?= e($r['client_email']) ?></div>
                </td>
                <td><?= e($r['type_name']) ?></td>
                <td><?= e(formatDate($r['start_date'])) ?></td>
                <td><?= e(formatDate($r['end_date'])) ?></td>
                <td><?= e($r['num_rooms']) ?></td>
                <td><?= e($r['num_guests']) ?></td>
                <td><?= formatMoney($r['total_estimated']) ?></td>
                <td><span class="badge status-<?= e($r['status']) ?>"><?= ['pending'=>'Pendente','active'=>'Confirmada','checked_in'=>'Check-in','completed'=>'Concluída','cancelled'=>'Cancelada'][$r['status']] ?? $r['status'] ?></span></td>
                <td class="td-actions">
                    <a href="reservation-detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-secondary">Ver</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script>
function applyFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    window.location.href = url.toString();
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


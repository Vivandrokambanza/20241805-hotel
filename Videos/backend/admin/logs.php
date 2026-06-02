<?php
$pageTitle = 'Logs de Auditoria';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');
$pdo = getDB();

$page    = max(1,(int)get('page',1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;
$search  = trim(get('search'));
$action  = get('action_filter');

$sql    = 'SELECT l.*, u.name AS user_name FROM audit_logs l LEFT JOIN users u ON l.user_id = u.id WHERE 1=1';
$params = [];
if ($search) { $sql .= ' AND (l.action LIKE ? OR l.entity LIKE ? OR l.details LIKE ? OR u.name LIKE ?)'; $like="%{$search}%"; $params=[$like,$like,$like,$like]; }
if ($action) { $sql .= ' AND l.action = ?'; $params[] = $action; }
$countSql = str_replace('SELECT l.*, u.name AS user_name', 'SELECT COUNT(*)', $sql);
$total = (int)$pdo->prepare($countSql)->execute($params) ? 0 : 0;
$cStmt = $pdo->prepare($countSql); $cStmt->execute($params); $total = (int)$cStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));

$sql .= ' ORDER BY l.created_at DESC LIMIT ' . $perPage . ' OFFSET ' . $offset;
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$logs = $stmt->fetchAll();

$actionLabels = [
    'login'=>'Login','logout'=>'Logout','register'=>'Registo',
    'create_reservation'=>'Criar Reserva','edit_reservation'=>'Editar Reserva','cancel_reservation'=>'Cancelar Reserva',
    'create_reservation_admin'=>'Criar Reserva (Admin)','confirm_reservation'=>'Confirmar Reserva',
    'checkin'=>'Check-in','checkout'=>'Check-out',
    'payment'=>'Pagamento',
    'create_room_type'=>'Criar Tipo','edit_room_type'=>'Editar Tipo',
    'create_room'=>'Criar Quarto','edit_room'=>'Editar Quarto',
    'edit_guest'=>'Editar Hóspede','toggle_guest'=>'Toggle Hóspede',
    'create_user'=>'Criar Utilizador','toggle_user'=>'Toggle Utilizador',
];
$distinctActions = $pdo->query('SELECT DISTINCT action FROM audit_logs ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">📋 Logs de Auditoria</div>

<div class="filter-bar">
    <div class="form-group">
        <label class="form-label">Ação</label>
        <select class="form-control" onchange="applyFilter('action_filter', this.value)">
            <option value="">Todas</option>
            <?php foreach ($distinctActions as $a): ?>
                <option value="<?= e($a) ?>" <?= $action===$a?'selected':'' ?>><?= e($actionLabels[$a] ?? $a) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Pesquisar</label>
        <form method="get" style="display:flex;gap:.5rem">
            <input type="hidden" name="action_filter" value="<?= e($action) ?>">
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Pesquisar...">
            <button class="btn btn-primary btn-sm">🔍</button>
        </form>
    </div>
    <div style="margin-left:auto;color:#888;font-size:.85rem;align-self:flex-end">
        <?= $total ?> registos | Página <?= $page ?>/<?= $totalPages ?>
    </div>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>Data/Hora</th><th>Utilizador</th><th>Ação</th><th>Entidade</th><th>ID</th><th>Detalhes</th><th>IP</th></tr></thead>
        <tbody>
        <?php if (empty($logs)): ?>
            <tr><td colspan="7" class="text-center" style="padding:2rem;color:#888">Nenhum log encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($logs as $l): ?>
        <tr>
            <td style="white-space:nowrap"><?= e(formatDatetime($l['created_at'])) ?></td>
            <td><?= $l['user_name'] ? e($l['user_name']) : '<span style="color:#aaa">—</span>' ?></td>
            <td><span class="badge badge-blue"><?= e($actionLabels[$l['action']] ?? $l['action']) ?></span></td>
            <td><?= e($l['entity']) ?></td>
            <td><?= $l['entity_id'] ? '#' . e($l['entity_id']) : '—' ?></td>
            <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.82rem;color:#555" title="<?= e($l['details']) ?>"><?= e($l['details']) ?></td>
            <td style="font-size:.8rem;color:#999"><?= e($l['ip_address']) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex;gap:.5rem;margin-top:1rem;justify-content:center">
    <?php for ($p = max(1,$page-3); $p <= min($totalPages,$page+3); $p++): ?>
        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&action_filter=<?= urlencode($action) ?>"
            class="btn btn-sm <?= $p===$page?'btn-primary':'btn-secondary' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<script>
function applyFilter(key, val) {
    const url = new URL(window.location.href);
    url.searchParams.set(key, val);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

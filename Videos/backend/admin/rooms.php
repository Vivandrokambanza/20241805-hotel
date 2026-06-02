<?php
$pageTitle = 'Quartos';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');
$pdo = getDB();

$errors = [];
$editId = (int)get('edit');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $number  = strtoupper(trim(post('room_number')));
    $typeId  = (int)post('room_type_id');
    $floor   = max(1,(int)post('floor'));
    $status  = in_array(post('status'),['available','occupied','maintenance']) ? post('status') : 'available';
    $desc    = trim(post('description'));

    if (!$number) $errors[] = 'Número do quarto obrigatório.';
    if (!$typeId) $errors[] = 'Tipo de quarto obrigatório.';

    if (!$errors) {
        try {
            if ($editId) {
                $pdo->prepare('UPDATE rooms SET room_number=?,room_type_id=?,floor=?,status=?,description=? WHERE id=?')
                    ->execute([$number,$typeId,$floor,$status,$desc?:null,$editId]);
                logAction('edit_room','rooms',$editId,'Updated');
                flash('Quarto atualizado.');
            } else {
                $pdo->prepare('INSERT INTO rooms (room_number,room_type_id,floor,status,description) VALUES (?,?,?,?,?)')
                    ->execute([$number,$typeId,$floor,$status,$desc?:null]);
                logAction('create_room','rooms',(int)$pdo->lastInsertId(),'Created');
                flash('Quarto criado.');
            }
            redirect('/admin/rooms.php');
        } catch (PDOException $e) {
            $errors[] = 'Número de quarto já existe.';
        }
    }
}

$roomTypes = $pdo->query('SELECT * FROM room_types WHERE status="active" ORDER BY name')->fetchAll();
$rooms     = $pdo->query('SELECT r.*, rt.name AS type_name FROM rooms r JOIN room_types rt ON r.room_type_id=rt.id ORDER BY r.room_number')->fetchAll();
$editRoom  = null;
if ($editId) { $s=$pdo->prepare('SELECT * FROM rooms WHERE id=?');$s->execute([$editId]);$editRoom=$s->fetch(); }

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">🚪 Quartos</div>

<div class="grid-2" style="gap:1.5rem">
    <div class="detail-card">
        <h3><?= $editRoom ? 'Editar Quarto ' . e($editRoom['room_number']) : 'Novo Quarto' ?></h3>
        <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
        <?php $d = $editRoom ?: []; ?>
        <form method="post" style="margin-top:.75rem">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <div class="form-row">
                <div class="form-group"><label class="form-label">Número *</label><input type="text" name="room_number" class="form-control" value="<?= e($d['room_number']??'') ?>" required placeholder="ex: 101"></div>
                <div class="form-group"><label class="form-label">Piso</label><input type="number" name="floor" class="form-control" value="<?= e($d['floor']??1) ?>" min="1" max="20"></div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo *</label>
                <select name="room_type_id" class="form-control" required>
                    <option value="">-- Selecionar --</option>
                    <?php foreach ($roomTypes as $rt): ?>
                        <option value="<?= $rt['id'] ?>" <?= ($d['room_type_id']??0)==$rt['id']?'selected':'' ?>><?= e($rt['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="status" class="form-control">
                    <option value="available" <?= ($d['status']??'available')==='available'?'selected':'' ?>>Disponível</option>
                    <option value="occupied" <?= ($d['status']??'')==='occupied'?'selected':'' ?>>Ocupado</option>
                    <option value="maintenance" <?= ($d['status']??'')==='maintenance'?'selected':'' ?>>Manutenção</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="2"><?= e($d['description']??'') ?></textarea></div>
            <div style="display:flex;gap:.75rem">
                <?php if ($editRoom): ?><a href="rooms.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
                <button type="submit" class="btn btn-primary"><?= $editRoom ? 'Guardar' : 'Criar' ?></button>
            </div>
        </form>
    </div>

    <div class="table-wrapper">
        <table>
            <thead><tr><th>Quarto</th><th>Piso</th><th>Tipo</th><th>Estado</th><th>Ações</th></tr></thead>
            <tbody>
            <?php foreach ($rooms as $r): ?>
            <tr>
                <td><strong><?= e($r['room_number']) ?></strong></td>
                <td><?= e($r['floor']) ?></td>
                <td><?= e($r['type_name']) ?></td>
                <td><span class="badge status-<?= e($r['status']) ?>"><?= ['available'=>'Disponível','occupied'=>'Ocupado','maintenance'=>'Manutenção'][$r['status']] ?></span></td>
                <td><a href="?edit=<?= $r['id'] ?>" class="btn btn-sm btn-warning">Editar</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


<?php
$pageTitle = 'Tipos de Quarto';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');
$pdo = getDB();

$errors = [];
$editId   = (int)get('edit');
$deleteId = (int)get('delete');

// Delete
if ($deleteId) {
    $checkRooms = $pdo->prepare('SELECT COUNT(*) FROM rooms WHERE room_type_id = ?');
    $checkRooms->execute([$deleteId]);
    $checkRes = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE room_type_id = ? AND status NOT IN ("cancelled","completed")');
    $checkRes->execute([$deleteId]);
    if ((int)$checkRooms->fetchColumn() > 0) {
        flash('Não é possível apagar — existem quartos deste tipo. Apague primeiro os quartos.', 'error');
    } elseif ((int)$checkRes->fetchColumn() > 0) {
        flash('Não é possível apagar — existem reservas ativas neste tipo de quarto.', 'error');
    } else {
        $pdo->prepare('DELETE FROM room_types WHERE id=?')->execute([$deleteId]);
        logAction('delete_room_type', 'room_types', $deleteId, 'Deleted');
        flash('Tipo de quarto apagado.');
    }
    redirect('/admin/room-types.php');
}

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'name'                   => trim(post('name')),
        'base_capacity'          => max(1,(int)post('base_capacity')),
        'max_capacity'           => max(1,(int)post('max_capacity')),
        'base_daily_rate'        => (float)post('base_daily_rate'),
        'breakfast_cost_per_guest'=> (float)post('breakfast_cost_per_guest'),
        'extra_guest_surcharge'  => (float)post('extra_guest_surcharge'),
        'amenities'              => trim(post('amenities')),
        'description'            => trim(post('description')),
        'status'                 => post('status') === 'inactive' ? 'inactive' : 'active',
    ];
    if (!$data['name'])           $errors[] = 'Nome obrigatório.';
    if ($data['base_daily_rate'] <= 0) $errors[] = 'Taxa diária inválida.';
    if ($data['base_capacity'] > $data['max_capacity']) $errors[] = 'Capacidade base não pode exceder a máxima.';

    if (!$errors) {
        if ($editId) {
            $pdo->prepare('UPDATE room_types SET name=?,base_capacity=?,max_capacity=?,base_daily_rate=?,breakfast_cost_per_guest=?,extra_guest_surcharge=?,amenities=?,description=?,status=? WHERE id=?')
                ->execute([...array_values($data), $editId]);
            logAction('edit_room_type', 'room_types', $editId, 'Updated');
            flash('Tipo de quarto atualizado.');
        } else {
            $pdo->prepare('INSERT INTO room_types (name,base_capacity,max_capacity,base_daily_rate,breakfast_cost_per_guest,extra_guest_surcharge,amenities,description,status) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute(array_values($data));
            logAction('create_room_type', 'room_types', (int)$pdo->lastInsertId(), 'Created');
            flash('Tipo de quarto criado.');
        }
        redirect('/admin/room-types.php');
    }
}

$roomTypes = $pdo->query('SELECT rt.*, (SELECT COUNT(*) FROM rooms WHERE room_type_id = rt.id) AS room_count FROM room_types rt ORDER BY rt.base_daily_rate ASC')->fetchAll();
$editRt = null;
if ($editId) { $s = $pdo->prepare('SELECT * FROM room_types WHERE id=?'); $s->execute([$editId]); $editRt = $s->fetch(); }

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title"> Tipos de Quarto</div>

<div class="grid-2" style="gap:1.5rem">
    <div>
        <div class="detail-card">
            <h3><?= $editRt ? 'Editar: ' . e($editRt['name']) : 'Novo Tipo de Quarto' ?></h3>
            <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
            <?php $d = $editRt ?: []; ?>
            <form method="post" style="margin-top:.75rem">
                <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
                <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="name" class="form-control" value="<?= e($d['name']??'') ?>" required placeholder="ex: Duplo, Casal, Familiar"></div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Cap. Base</label><input type="number" name="base_capacity" class="form-control" value="<?= e($d['base_capacity']??2) ?>" min="1" max="10"></div>
                    <div class="form-group"><label class="form-label">Cap. Máxima</label><input type="number" name="max_capacity" class="form-control" value="<?= e($d['max_capacity']??4) ?>" min="1" max="10"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label class="form-label">Taxa Diária (€)</label><input type="number" name="base_daily_rate" class="form-control" value="<?= e($d['base_daily_rate']??80) ?>" min="0" step="0.01"></div>
                    <div class="form-group"><label class="form-label">P. Almoço / hóspede</label><input type="number" name="breakfast_cost_per_guest" class="form-control" value="<?= e($d['breakfast_cost_per_guest']??10) ?>" min="0" step="0.01"></div>
                </div>
                <div class="form-group"><label class="form-label">Suplemento Hóspede Extra</label><input type="number" name="extra_guest_surcharge" class="form-control" value="<?= e($d['extra_guest_surcharge']??20) ?>" min="0" step="0.01"></div>
                <div class="form-group"><label class="form-label">Comodidades (separadas por vírgula)</label><input type="text" name="amenities" class="form-control" value="<?= e($d['amenities']??'') ?>" placeholder="Wi-Fi,TV,Ar Condicionado"></div>
                <div class="form-group"><label class="form-label">Descrição</label><textarea name="description" class="form-control" rows="2"><?= e($d['description']??'') ?></textarea></div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="status" class="form-control">
                        <option value="active" <?= ($d['status']??'active')==='active'?'selected':'' ?>>Ativo</option>
                        <option value="inactive" <?= ($d['status']??'')==='inactive'?'selected':'' ?>>Inativo</option>
                    </select>
                </div>
                <div style="display:flex;gap:.75rem">
                    <?php if ($editRt): ?><a href="room-types.php" class="btn btn-secondary">Cancelar</a><?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?= $editRt ? 'Guardar' : 'Criar' ?></button>
                </div>
            </form>
        </div>
    </div>
    <div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nome</th><th>Cap.</th><th>Taxa/noite</th><th>Quartos</th><th>Estado</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($roomTypes as $rt): ?>
                <tr>
                    <td><strong><?= e($rt['name']) ?></strong></td>
                    <td><?= e($rt['base_capacity']) ?>–<?= e($rt['max_capacity']) ?></td>
                    <td><?= formatMoney($rt['base_daily_rate']) ?></td>
                    <td><?= e($rt['room_count']) ?></td>
                    <td><span class="badge <?= $rt['status']==='active'?'badge-green':'badge-red' ?>"><?= $rt['status']==='active'?'Ativo':'Inativo' ?></span></td>
                    <td style="display:flex;gap:.4rem">
                        <a href="?edit=<?= $rt['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="?delete=<?= $rt['id'] ?>" class="btn btn-sm btn-danger"
                           data-confirm="Apagar tipo '<?= e($rt['name']) ?>'? Esta ação é irreversível.">Apagar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


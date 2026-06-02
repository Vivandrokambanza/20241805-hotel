<?php
$pageTitle = 'Hóspedes';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
requireRole('receptionist', 'manager');
$pdo = getDB();
$_isManager = (currentUser()['role'] ?? '') === 'manager';

$errors = [];
$editId   = (int)get('edit');
$deleteId = (int)get('delete');
$showNew  = get('action') === 'new';

// Create new guest
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'create') {
    verifyCsrf();
    $name    = trim(post('name'));
    $email   = trim(post('email'));
    $phone   = trim(post('phone'));
    $docType = post('document_type') ?: null;
    $docNum  = trim(post('document_number')) ?: null;
    $nif     = preg_replace('/\D/', '', post('nif'));
    $pwd     = post('password');

    if (!$name)                         $errors[] = 'Nome obrigatório.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($pwd) < 6)               $errors[] = 'Password deve ter pelo menos 6 caracteres.';
    if ($nif && !validateNIF($nif))     $errors[] = 'NIF inválido.';

    if (!$errors) {
        $exists = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $exists->execute([$email]);
        if ($exists->fetch()) $errors[] = 'Já existe um utilizador com este email.';
    }

    if (!$errors) {
        $hash = password_hash($pwd, PASSWORD_BCRYPT);
        $pdo->prepare('INSERT INTO users (name, email, password_hash, role, phone, document_type, document_number, nif) VALUES (?,?,?,"client",?,?,?,?)')
            ->execute([$name, $email, $hash, $phone ?: null, $docType, $docNum, $nif ?: null]);
        $newId = (int)$pdo->lastInsertId();
        logAction('create_guest', 'users', $newId, "Receptionist created guest: {$email}");
        flash("Hóspede {$name} registado com sucesso.");
        redirect('/admin/guests.php');
    }
    $showNew = true;
}

// Toggle status
if ($deleteId && $_isManager) {
    $pdo->prepare('UPDATE users SET status = IF(status="active","inactive","active") WHERE id = ? AND role = "client"')->execute([$deleteId]);
    logAction('toggle_guest', 'users', $deleteId, 'Manager toggled guest status');
    flash('Estado do hóspede atualizado.');
    redirect('/admin/guests.php');
}

// Save edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $editId) {
    verifyCsrf();
    $name   = trim(post('name'));
    $phone  = trim(post('phone'));
    $docType= post('document_type') ?: null;
    $docNum = trim(post('document_number')) ?: null;
    $nif    = preg_replace('/\D/', '', post('nif'));

    if (!$name) $errors[] = 'Nome obrigatório.';
    if ($nif && !validateNIF($nif)) $errors[] = 'NIF inválido.';

    if (!$errors) {
        $pdo->prepare('UPDATE users SET name=?, phone=?, document_type=?, document_number=?, nif=? WHERE id=? AND role="client"')
            ->execute([$name, $phone ?: null, $docType, $docNum, $nif ?: null, $editId]);
        logAction('edit_guest', 'users', $editId, 'Admin edited guest');
        flash('Hóspede atualizado.');
        redirect('/admin/guests.php');
    }
}

$search = trim(get('search'));
$statusF = get('status', 'all');
$sql = 'SELECT u.*, (SELECT COUNT(*) FROM reservations WHERE user_id = u.id) AS total_res FROM users u WHERE u.role = "client"';
$params = [];
if ($search) { $sql .= ' AND (u.name LIKE ? OR u.email LIKE ?)'; $like = "%{$search}%"; $params = [$like, $like]; }
if ($statusF !== 'all') { $sql .= ' AND u.status = ?'; $params[] = $statusF; }
$sql .= ' ORDER BY u.name ASC';
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$guests = $stmt->fetchAll();

$editGuest = null;
if ($editId) {
    $s = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "client"'); $s->execute([$editId]); $editGuest = $s->fetch();
}

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">
    <span>👥 Hóspedes</span>
    <a href="?action=new" class="btn btn-primary btn-sm">+ Registar Hóspede</a>
</div>

<?php if ($showNew && !$editId): ?>
<div class="detail-card" style="margin-bottom:1.5rem;max-width:640px">
    <h3>Registar Novo Hóspede</h3>
    <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
    <form method="post" style="margin-top:.75rem">
        <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
        <input type="hidden" name="_action" value="create">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="name" class="form-control" value="<?= e(post('name')) ?>" required></div>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" value="<?= e(post('email')) ?>" required></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label class="form-label">Password *</label><input type="password" name="password" class="form-control" minlength="6" required></div>
            <div class="form-group"><label class="form-label">Telefone</label><input type="tel" name="phone" class="form-control" value="<?= e(post('phone')) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo de Documento</label>
                <select name="document_type" class="form-control">
                    <option value="">—</option>
                    <option value="cc" <?= post('document_type')==='cc'?'selected':'' ?>>Cartão de Cidadão</option>
                    <option value="passport" <?= post('document_type')==='passport'?'selected':'' ?>>Passaporte</option>
                    <option value="other" <?= post('document_type')==='other'?'selected':'' ?>>Outro</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Número de Documento</label><input type="text" name="document_number" class="form-control" value="<?= e(post('document_number')) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">NIF <span style="font-weight:400;color:#888">(opcional)</span></label><input type="text" name="nif" class="form-control" value="<?= e(post('nif')) ?>" maxlength="9"></div>
        <div style="display:flex;gap:.75rem">
            <a href="guests.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-success">✓ Registar Hóspede</button>
        </div>
    </form>
</div>
<?php endif; ?>

<?php if ($editGuest): ?>
<div class="detail-card" style="margin-bottom:1.5rem;max-width:640px">
    <h3>Editar Hóspede: <?= e($editGuest['name']) ?></h3>
    <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
    <form method="post" style="margin-top:.75rem">
        <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
        <div class="form-row">
            <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="name" class="form-control" value="<?= e($editGuest['name']) ?>" required></div>
            <div class="form-group"><label class="form-label">Telefone</label><input type="tel" name="phone" class="form-control" value="<?= e($editGuest['phone']) ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Tipo de Documento</label>
                <select name="document_type" class="form-control">
                    <option value="">—</option>
                    <option value="cc" <?= $editGuest['document_type']==='cc'?'selected':'' ?>>Cartão de Cidadão</option>
                    <option value="passport" <?= $editGuest['document_type']==='passport'?'selected':'' ?>>Passaporte</option>
                    <option value="other" <?= $editGuest['document_type']==='other'?'selected':'' ?>>Outro</option>
                </select>
            </div>
            <div class="form-group"><label class="form-label">Número de Documento</label><input type="text" name="document_number" class="form-control" value="<?= e($editGuest['document_number']) ?>"></div>
        </div>
        <div class="form-group"><label class="form-label">NIF</label><input type="text" name="nif" class="form-control" value="<?= e($editGuest['nif']) ?>" maxlength="9"></div>
        <div style="display:flex;gap:.75rem">
            <a href="guests.php" class="btn btn-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="filter-bar">
    <div class="form-group">
        <label class="form-label">Estado</label>
        <select class="form-control" onchange="location.href='?status='+this.value+'&search=<?= urlencode($search) ?>'">
            <option value="all" <?= $statusF==='all'?'selected':'' ?>>Todos</option>
            <option value="active" <?= $statusF==='active'?'selected':'' ?>>Ativos</option>
            <option value="inactive" <?= $statusF==='inactive'?'selected':'' ?>>Inativos</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Pesquisar</label>
        <form method="get" style="display:flex;gap:.5rem">
            <input type="hidden" name="status" value="<?= e($statusF) ?>">
            <input type="text" name="search" class="form-control" value="<?= e($search) ?>" placeholder="Nome ou email">
            <button class="btn btn-primary btn-sm">🔍</button>
        </form>
    </div>
</div>

<div class="table-wrapper">
    <table>
        <thead><tr><th>#</th><th>Nome</th><th>Email</th><th>Documento</th><th>NIF</th><th>Reservas</th><th>Estado</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if (empty($guests)): ?>
            <tr><td colspan="8" class="text-center" style="padding:2rem;color:#888">Nenhum hóspede encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($guests as $g): ?>
        <tr>
            <td><?= e($g['id']) ?></td>
            <td><?= e($g['name']) ?></td>
            <td><?= e($g['email']) ?></td>
            <td><?= $g['document_type'] ? e(strtoupper($g['document_type'])) . ' ' . e($g['document_number']) : '—' ?></td>
            <td><?= $g['nif'] ?: '—' ?></td>
            <td><?= e($g['total_res']) ?></td>
            <td><span class="badge status-<?= e($g['status']) ?>"><?= $g['status'] === 'active' ? 'Ativo' : 'Inativo' ?></span></td>
            <td class="td-actions">
                <a href="?edit=<?= $g['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                <?php if ($_isManager): ?>
                <a href="?delete=<?= $g['id'] ?>" class="btn btn-sm <?= $g['status']==='active'?'btn-danger':'btn-success' ?>"
                    data-confirm="<?= $g['status']==='active'?'Desativar este hóspede?':'Reativar este hóspede?' ?>"><?= $g['status']==='active'?'Desativar':'Reativar' ?></a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>


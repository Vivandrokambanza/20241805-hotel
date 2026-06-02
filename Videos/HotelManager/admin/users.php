<?php
$pageTitle = 'Utilizadores';
require_once __DIR__ . '/../includes/db.php';
requireRole('manager');
$pdo = getDB();

$errors = [];

// Create staff user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('_action') === 'create') {
    verifyCsrf();
    $name  = trim(post('name'));
    $email = strtolower(trim(post('email')));
    $pass  = post('password');
    $role  = in_array(post('role'), ['receptionist','manager']) ? post('role') : 'receptionist';

    if (!$name)  $errors[] = 'Nome obrigatório.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($pass) < 6) $errors[] = 'Palavra-passe: mínimo 6 caracteres.';

    if (!$errors) {
        try {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')->execute([$name,$email,$hash,$role]);
            logAction('create_user','users',(int)$pdo->lastInsertId(),"Created {$role}");
            flash('Utilizador criado.');
            redirect('/admin/users.php');
        } catch (PDOException) {
            $errors[] = 'Email já registado.';
        }
    }
}

// Toggle status
$toggleId = (int)get('toggle');
if ($toggleId) {
    $pdo->prepare('UPDATE users SET status = IF(status="active","inactive","active") WHERE id = ? AND id != ?')->execute([$toggleId, currentUser()['id']]);
    flash('Estado do utilizador atualizado.');
    redirect('/admin/users.php');
}

$users = $pdo->query('SELECT * FROM users WHERE role != "client" ORDER BY role, name')->fetchAll();

include __DIR__ . '/../includes/admin_header.php';
?>
<div class="admin-page-title">👤 Utilizadores do Sistema</div>

<div class="grid-2" style="gap:1.5rem">
    <div class="detail-card">
        <h3>Criar Utilizador</h3>
        <?php foreach ($errors as $e_): ?><div class="alert alert-error"><?= e($e_) ?></div><?php endforeach; ?>
        <form method="post" style="margin-top:.75rem">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <input type="hidden" name="_action" value="create">
            <div class="form-group"><label class="form-label">Nome *</label><input type="text" name="name" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Email *</label><input type="email" name="email" class="form-control" required></div>
            <div class="form-group"><label class="form-label">Palavra-passe *</label><input type="password" name="password" class="form-control" required></div>
            <div class="form-group">
                <label class="form-label">Perfil</label>
                <select name="role" class="form-control">
                    <option value="receptionist">Rececionista</option>
                    <option value="manager">Gestor</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Criar Utilizador</button>
        </form>
    </div>

    <div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Nome</th><th>Email</th><th>Perfil</th><th>Estado</th><th>Criado</th><th>Ações</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><span class="badge status-<?= e($u['role']) ?>"><?= ['manager'=>'Gestor','receptionist'=>'Rececionista'][$u['role']] ?? $u['role'] ?></span></td>
                    <td><span class="badge <?= $u['status']==='active'?'badge-green':'badge-red' ?>"><?= $u['status']==='active'?'Ativo':'Inativo' ?></span></td>
                    <td><?= e(formatDate($u['created_at'])) ?></td>
                    <td>
                        <?php if ($u['id'] !== currentUser()['id']): ?>
                        <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['status']==='active'?'btn-danger':'btn-success' ?>"
                            data-confirm="<?= $u['status']==='active'?'Desativar utilizador?':'Reativar utilizador?' ?>"><?= $u['status']==='active'?'Desativar':'Reativar' ?></a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>

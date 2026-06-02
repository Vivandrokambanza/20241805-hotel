<?php
$pageTitle = 'Registar';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $data = [
        'name'            => trim(post('name')),
        'email'           => strtolower(trim(post('email'))),
        'password'        => post('password'),
        'password_confirm'=> post('password_confirm'),
        'document_type'   => post('document_type') ?: null,
        'document_number' => trim(post('document_number')) ?: null,
        'nif'             => preg_replace('/\D/', '', post('nif')),
        'phone'           => trim(post('phone')) ?: null,
    ];

    if (!$data['name'])  $errors[] = 'O nome é obrigatório.';
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email inválido.';
    if (strlen($data['password']) < 6) $errors[] = 'A palavra-passe deve ter pelo menos 6 caracteres.';
    if ($data['password'] !== $data['password_confirm']) $errors[] = 'As palavras-passe não coincidem.';
    if ($data['nif'] && !validateNIF($data['nif'])) $errors[] = 'NIF inválido (deve ter 9 dígitos válidos).';

    if (!$errors) {
        $id = registerClient($data);
        if ($id === false) {
            $errors[] = 'Este email já está registado. <a href="login.php">Entrar</a>.';
        } else {
            loginUser($data['email'], $data['password']);
            flash('Registo efetuado com sucesso. Bem-vindo ao Hotel Vivandro!');
            redirect('/index.php');
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page" style="padding:2rem 1rem">
    <div class="auth-card" style="max-width:560px">
        <div class="auth-title">📝 Criar conta</div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= $err ?></div>
        <?php endforeach; ?>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <div class="form-group">
                <label class="form-label">Nome completo *</label>
                <input type="text" name="name" class="form-control" value="<?= e($data['name'] ?? '') ?>" required placeholder="João Silva">
            </div>
            <div class="form-group">
                <label class="form-label">Email *</label>
                <input type="email" name="email" class="form-control" value="<?= e($data['email'] ?? '') ?>" required placeholder="nome@exemplo.pt">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Palavra-passe *</label>
                    <input type="password" name="password" class="form-control" required placeholder="Mínimo 6 caracteres">
                </div>
                <div class="form-group">
                    <label class="form-label">Confirmar palavra-passe *</label>
                    <input type="password" name="password_confirm" class="form-control" required placeholder="Repetir palavra-passe">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Tipo de documento</label>
                    <select name="document_type" class="form-control">
                        <option value="">-- Selecionar --</option>
                        <option value="cc" <?= ($data['document_type'] ?? '') === 'cc' ? 'selected' : '' ?>>Cartão de Cidadão</option>
                        <option value="passport" <?= ($data['document_type'] ?? '') === 'passport' ? 'selected' : '' ?>>Passaporte</option>
                        <option value="other" <?= ($data['document_type'] ?? '') === 'other' ? 'selected' : '' ?>>Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Número do documento</label>
                    <input type="text" name="document_number" class="form-control" value="<?= e($data['document_number'] ?? '') ?>" placeholder="Ex: 12345678 9ZZ0">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">NIF <span style="font-weight:400;color:#888">(opcional, para faturação PT)</span></label>
                    <input type="text" name="nif" class="form-control" value="<?= e($data['nif'] ?? '') ?>" maxlength="9" placeholder="123456789">
                </div>
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="tel" name="phone" class="form-control" value="<?= e($data['phone'] ?? '') ?>" placeholder="+351 9XX XXX XXX">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:.5rem">Criar conta</button>
        </form>

        <div class="auth-link">
            Já tem conta? <a href="login.php">Entrar</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

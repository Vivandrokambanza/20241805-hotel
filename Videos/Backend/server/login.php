<?php
$pageTitle = 'Entrar';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirect('/index.php');
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email    = trim(post('email'));
    $password = post('password');

    if (!$email || !$password) {
        $errors[] = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    } elseif (!loginUser($email, $password)) {
        $errors[] = 'Email ou palavra-passe incorretos.';
    } else {
        $redirect = get('redirect', '/index.php');
        // Safety: only allow relative redirects
        if (!str_starts_with($redirect, '/')) $redirect = '/index.php';
        redirect($redirect);
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-page">
    <div class="auth-card">
        <div class="auth-title">🔐 Entrar na sua conta</div>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-error"><?= e($err) ?></div>
        <?php endforeach; ?>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e(csrf()) ?>">
            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input type="email" name="email" id="email" class="form-control <?= $errors ? 'is-invalid' : '' ?>"
                    value="<?= e($email) ?>" required autofocus placeholder="nome@exemplo.pt">
            </div>
            <div class="form-group">
                <label class="form-label" for="password">Palavra-passe</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:.5rem">Entrar</button>
        </form>

        <div class="auth-link">
            Não tem conta? <a href="register.php">Registar-se</a>
        </div>
        <div style="margin-top:1rem;padding:1rem;background:#f8fafc;border-radius:8px;font-size:.82rem;color:#555">
            <strong>Credenciais de demonstração:</strong><br>
            Gestor: admin@hotel.pt / admin123<br>
            Rececionista: rececionista@hotel.pt / recep123
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

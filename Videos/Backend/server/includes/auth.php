<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user === null) {
        $pdo = getDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND status = "active"');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireRole(string ...$roles): void {
    requireLogin();
    $user = currentUser();
    if (!in_array($user['role'], $roles)) {
        http_response_code(403);
        include __DIR__ . '/../includes/header.php';
        echo '<div class="container"><div class="alert alert-error">Acesso negado. Não tem permissão para aceder a esta página.</div></div>';
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

function loginUser(string $email, string $password): bool {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = "active"');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        startSession();
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        logAction('login', 'users', $user['id'], 'User logged in');
        return true;
    }
    return false;
}

function logoutUser(): void {
    startSession();
    if (isset($_SESSION['user_id'])) {
        logAction('logout', 'users', $_SESSION['user_id'], 'User logged out');
    }
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

function registerClient(array $data): int|false {
    $pdo = getDB();
    $hash = password_hash($data['password'], PASSWORD_BCRYPT);
    try {
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role, document_type, document_number, nif, phone) VALUES (?,?,?,?,?,?,?,?)');
        $stmt->execute([
            trim($data['name']),
            strtolower(trim($data['email'])),
            $hash,
            'client',
            $data['document_type'] ?? null,
            $data['document_number'] ?? null,
            $data['nif'] ?? null,
            $data['phone'] ?? null,
        ]);
        $id = (int)$pdo->lastInsertId();
        logAction('register', 'users', $id, 'New client registered');
        return $id;
    } catch (PDOException $e) {
        return false;
    }
}

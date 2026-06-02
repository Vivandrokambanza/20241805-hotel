<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
$_currentUser = currentUser();
$_flash = getFlash();
$_role = $_currentUser['role'] ?? null;
$_isAdmin = in_array($_role, ['manager', 'receptionist']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Hotel Vivandro') ?> — Hotel Vivandro</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>/index.php" class="logo">
            <span class="logo-icon">🏨</span>
            <span>Hotel Vivandro</span>
        </a>
        <nav class="main-nav">
            <a href="<?= BASE_URL ?>/index.php">Início</a>
            <a href="<?= BASE_URL ?>/about.php">Sobre Nós</a>
            <?php if ($_isAdmin): ?>
                <a href="<?= BASE_URL ?>/admin/index.php">Backoffice</a>
            <?php endif; ?>
            <?php if (isLoggedIn()): ?>
                <?php if ($_role === 'client'): ?>
                    <a href="<?= BASE_URL ?>/book.php">Reservar</a>
                    <a href="<?= BASE_URL ?>/my-reservations.php">As Minhas Reservas</a>
                <?php endif; ?>
                <div class="nav-user">
                    <span>Olá, <?= e($_currentUser['name']) ?></span>
                    <a href="<?= BASE_URL ?>/logout.php" class="btn btn-sm btn-outline">Sair</a>
                </div>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login.php" class="btn btn-sm">Entrar</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-sm btn-outline">Registar</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main>
<?php if ($_flash): ?>
<div class="container" style="padding-top:1rem">
    <div class="alert alert-<?= e($_flash['type']) ?>"><?= e($_flash['msg']) ?></div>
</div>
<?php endif; ?>

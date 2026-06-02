<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
requireRole('receptionist', 'manager');
$_currentUser = currentUser();
$_role = $_currentUser['role'];
$_isManager = $_role === 'manager';
$_flash = getFlash();

$currentFile = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Backoffice') ?> — Hotel Vivandro Admin</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <a href="<?= BASE_URL ?>/admin/index.php" class="logo">
            <span class="logo-icon">🏨</span>
            <span>Hotel Vivandro <small style="font-weight:400;color:#aaa">Admin</small></span>
        </a>
        <div class="nav-user">
            <span class="badge badge-<?= $_isManager ? 'purple' : 'blue' ?>"><?= $_isManager ? 'Gestor' : 'Rececionista' ?></span>
            <span><?= e($_currentUser['name']) ?></span>
            <a href="<?= BASE_URL ?>/index.php" class="btn btn-sm btn-outline">Site</a>
            <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-outline">Sair</a>
        </div>
    </div>
</header>
<div class="admin-layout">
    <aside class="admin-sidebar">
        <h3>Principal</h3>
        <a href="index.php" class="<?= $currentFile === 'index.php' ? 'active' : '' ?>">
            <span class="nav-icon">📊</span> Dashboard
        </a>
        <a href="reservations.php" class="<?= $currentFile === 'reservations.php' ? 'active' : '' ?>">
            <span class="nav-icon">📅</span> Reservas
        </a>
        <a href="checkin.php" class="<?= $currentFile === 'checkin.php' ? 'active' : '' ?>">
            <span class="nav-icon">🔑</span> Check-in / Check-out
        </a>
        <a href="payments.php" class="<?= $currentFile === 'payments.php' ? 'active' : '' ?>">
            <span class="nav-icon">💰</span> Pagamentos
        </a>
        <a href="guests.php" class="<?= $currentFile === 'guests.php' ? 'active' : '' ?>">
            <span class="nav-icon">👥</span> Hóspedes
        </a>
        <?php if ($_isManager): ?>
        <h3 style="margin-top:1rem">Configuração</h3>
        <a href="room-types.php" class="<?= $currentFile === 'room-types.php' ? 'active' : '' ?>">
            <span class="nav-icon">🏷️</span> Tipos de Quarto
        </a>
        <a href="rooms.php" class="<?= $currentFile === 'rooms.php' ? 'active' : '' ?>">
            <span class="nav-icon">🚪</span> Quartos
        </a>
        <a href="users.php" class="<?= $currentFile === 'users.php' ? 'active' : '' ?>">
            <span class="nav-icon">👤</span> Utilizadores
        </a>
        <h3 style="margin-top:1rem">Relatórios</h3>
        <a href="reports.php" class="<?= $currentFile === 'reports.php' ? 'active' : '' ?>">
            <span class="nav-icon">📈</span> Relatórios
        </a>
        <a href="logs.php" class="<?= $currentFile === 'logs.php' ? 'active' : '' ?>">
            <span class="nav-icon">📋</span> Logs de Auditoria
        </a>
        <?php else: ?>
        <h3 style="margin-top:1rem">Relatórios</h3>
        <a href="reports.php" class="<?= $currentFile === 'reports.php' ? 'active' : '' ?>">
            <span class="nav-icon">📈</span> Relatórios
        </a>
        <?php endif; ?>
    </aside>
    <div class="admin-content">
        <?php if ($_flash): ?>
            <div class="alert alert-<?= e($_flash['type']) ?>"><?= e($_flash['msg']) ?></div>
        <?php endif; ?>

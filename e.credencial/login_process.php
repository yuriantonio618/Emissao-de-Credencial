<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('/index.php');
}

$utilizador = trim($_POST['utilizador'] ?? '');
$senha      = (string) ($_POST['senha'] ?? '');

if ($utilizador === '' || $senha === '') {
    redirecionar('/index.php?erro=1');
}

$pdo = getPDO();

// 1) Tenta autenticar como administrador
$stmt = $pdo->prepare('SELECT * FROM administradores WHERE usuario = ?');
$stmt->execute([$utilizador]);
$admin = $stmt->fetch();

if ($admin && password_verify($senha, $admin['senha_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_type']     = 'admin';
    $_SESSION['user_id']       = $admin['id'];
    $_SESSION['ultimo_acesso'] = time();
    registarEvento('admin', (int) $admin['id'], 'Início de sessão');
    redirecionar('/admin/dashboard.php');
}

// 2) Tenta autenticar como estudante
$stmt = $pdo->prepare('SELECT * FROM estudantes WHERE numero_estudante = ?');
$stmt->execute([$utilizador]);
$estudante = $stmt->fetch();

if ($estudante && password_verify($senha, $estudante['senha_hash'])) {
    session_regenerate_id(true);
    $_SESSION['user_type']     = 'estudante';
    $_SESSION['user_id']       = $estudante['id'];
    $_SESSION['ultimo_acesso'] = time();
    registarEvento('estudante', (int) $estudante['id'], 'Início de sessão');
    redirecionar('/student/inicio.php');
}

redirecionar('/index.php?erro=1');

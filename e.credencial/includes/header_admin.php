<?php
$admin = adminActual();
if (!$admin) redirecionar('/index.php');

$menuAdmin = [
    ['slug' => 'dashboard',       'icone' => '📊', 'label' => 'Dashboard Estatístico'],
    ['slug' => 'estudantes',      'icone' => '🧑‍🎓', 'label' => 'Gestão de Estudantes'],
    ['slug' => 'cursos',          'icone' => '🏛️', 'label' => 'Gestão de Cursos'],
    ['slug' => 'disciplinas',     'icone' => '📚', 'label' => 'Gestão de Disciplinas'],
    ['slug' => 'notas',           'icone' => '📝', 'label' => 'Lançamento de Notas'],
    ['slug' => 'pedidos_estagio', 'icone' => '🎓', 'label' => 'Pedidos de Estágio'],
    ['slug' => 'documentos',      'icone' => '📎', 'label' => 'Documentos Submetidos'],
    ['slug' => 'notificacoes',    'icone' => '🔔', 'label' => 'Notificações'],
    ['slug' => 'emails',          'icone' => '✉️', 'label' => 'Caixa de Saída de E-mails'],
];
$paginaActivaAdmin = $paginaActivaAdmin ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($menuTitulo ?? 'Painel do Administrador') ?> — <?= APP_NOME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <div class="brand">
      <img class="brand-logo" src="<?= BASE_URL ?>/assets/img/logo_ul.png" alt="UL">
      <?= APP_NOME ?><span class="v"><?= APP_VERSAO ?></span>
    </div>
    <div class="user">
      <div>
        <div>Administrador:</div>
        <div class="name"><?= e($admin['nome']) ?></div>
      </div>
      <a class="btn-logout" href="<?= BASE_URL ?>/logout.php">⏻ Sair</a>
    </div>
  </div>
  <div class="layout">
    <div class="sidebar">
      <h4>Administração</h4>
      <div class="menu-group">
        <?php foreach ($menuAdmin as $item): ?>
          <a class="menu-item <?= $paginaActivaAdmin === $item['slug'] ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/<?= $item['slug'] ?>.php">
            <?= $item['icone'] ?> <?= e($item['label']) ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="content">

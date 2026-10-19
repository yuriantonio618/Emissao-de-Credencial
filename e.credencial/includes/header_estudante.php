<?php
/**
 * SIGEUL — Cabeçalho + barra lateral do Portal do Estudante
 * Espera que a variável $paginaActiva esteja definida antes do include,
 * com o nome do ficheiro activo (ex: 'notas', 'financeira', ...).
 */
$est = estudanteActual();
if (!$est) redirecionar('/index.php');

$pdoNotif = getPDO();
$stmtNotif = $pdoNotif->prepare(
    "SELECT COUNT(*) FROM notificacoes n
     WHERE (n.alvo_tipo = 'Todos' OR (n.alvo_tipo = 'Curso' AND n.alvo_id = ?) OR (n.alvo_tipo = 'Estudante' AND n.alvo_id = ?))
       AND NOT EXISTS (SELECT 1 FROM notificacoes_leituras l WHERE l.notificacao_id = n.id AND l.estudante_id = ?)"
);
$stmtNotif->execute([$est['curso_id'], $est['id'], $est['id']]);
$notificacoesPorLer = (int) $stmtNotif->fetchColumn();

$menuEstudante = [
    'Portal do Estudante' => [
        ['slug' => 'inicio',      'icone' => '🏠', 'label' => 'Início'],
        ['slug' => 'notas',       'icone' => '📊', 'label' => 'Notas de Frequência'],
        ['slug' => 'exames',      'icone' => '📝', 'label' => 'Exames'],
        ['slug' => 'financeira',  'icone' => '💰', 'label' => 'Situação Financeira'],
        ['slug' => 'avaliacao',   'icone' => '⭐', 'label' => 'Avaliação do Corpo Docente'],
        ['slug' => 'inscricoes',  'icone' => '🗂️', 'label' => 'Inscrições'],
        ['slug' => 'horarios',    'icone' => '🕒', 'label' => 'Horários'],
        ['slug' => 'material',    'icone' => '📚', 'label' => 'Material Académico'],
        ['slug' => 'emolumentos', 'icone' => '🧾', 'label' => 'Pagamento de Emolumentos'],
        ['slug' => 'curricular',  'icone' => '📈', 'label' => 'Ver Plano Curricular'],
        ['slug' => 'estagio',     'icone' => '🎓', 'label' => 'Credencial de Estágio'],
        ['slug' => 'documentos',  'icone' => '📎', 'label' => 'Documentos'],
        ['slug' => 'notificacoes','icone' => '🔔', 'label' => 'Notificações'],
    ],
    'Personalizações' => [
        ['slug' => 'perfil',   'icone' => '', 'label' => 'Dados Pessoais'],
        ['slug' => 'senha',    'icone' => '🔑', 'label' => 'Alterar Senha'],
        ['slug' => 'sessoes',  'icone' => '🕓', 'label' => 'Histórico de Sessões'],
    ],
];
?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($menuTitulo ?? 'Portal do Estudante') ?> — <?= APP_NOME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <div class="topbar">
    <div class="brand">
      <button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>
      <img class="brand-logo" src="<?= BASE_URL ?>/assets/img/logo_ul.png" alt="UL">
      <?= APP_NOME ?><span class="v"><?= APP_VERSAO ?></span>
    </div>
    <div class="user">
      <a href="<?= BASE_URL ?>/student/notificacoes.php" style="position:relative;text-decoration:none;color:#fff;font-size:19px;margin-right:4px;" title="Notificações">
        🔔
        <?php if ($notificacoesPorLer > 0): ?>
          <span style="position:absolute;top:-6px;right:-10px;background:var(--ul-gold);color:#fff;font-size:10px;font-weight:700;padding:1px 5px;border-radius:20px;"><?= $notificacoesPorLer ?></span>
        <?php endif; ?>
      </a>
      <div>
        <div>Número de estudante:</div>
        <div class="name"><?= e($est['numero_estudante']) ?> — <?= e(mb_strtoupper($est['nome'])) ?></div>
      </div>
      <a class="btn-logout" href="<?= BASE_URL ?>/logout.php">⏻ Sair</a>
    </div>
  </div>

  <div class="layout">
    <div class="sidebar">
      <?php foreach ($menuEstudante as $grupo => $itens): ?>
        <h4><?= e($grupo) ?></h4>
        <div class="menu-group">
          <?php foreach ($itens as $item): ?>
            <a class="menu-item <?= ($paginaActiva === $item['slug']) ? 'active' : '' ?> <?= !empty($item['novo']) ? 'menu-item-novo' : '' ?>"
               href="<?= BASE_URL ?>/student/<?= $item['slug'] ?>.php">
              <?= $item['icone'] ?> <?= e($item['label']) ?>
              <?php if (!empty($item['novo'])): ?><span class="tag-novo">NOVO</span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="content">

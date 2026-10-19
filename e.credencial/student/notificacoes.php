<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

// Marcar como lida ao abrir
if (isset($_GET['ler'])) {
    $id = (int) $_GET['ler'];
    $stmt = $pdo->prepare(
        "SELECT n.id FROM notificacoes n
         WHERE n.id = ? AND (n.alvo_tipo = 'Todos' OR (n.alvo_tipo = 'Curso' AND n.alvo_id = ?) OR (n.alvo_tipo = 'Estudante' AND n.alvo_id = ?))"
    );
    $stmt->execute([$id, $est['curso_id'], $est['id']]);
    if ($stmt->fetch()) {
        $ins = $pdo->prepare('INSERT IGNORE INTO notificacoes_leituras (notificacao_id, estudante_id) VALUES (?, ?)');
        $ins->execute([$id, $est['id']]);
    }
    redirecionar('/student/notificacoes.php');
}

$stmt = $pdo->prepare(
    "SELECT n.*, (l.id IS NOT NULL) AS lida
     FROM notificacoes n
     LEFT JOIN notificacoes_leituras l ON l.notificacao_id = n.id AND l.estudante_id = ?
     WHERE n.alvo_tipo = 'Todos' OR (n.alvo_tipo = 'Curso' AND n.alvo_id = ?) OR (n.alvo_tipo = 'Estudante' AND n.alvo_id = ?)
     ORDER BY lida ASC, n.criado_em DESC"
);
$stmt->execute([$est['id'], $est['curso_id'], $est['id']]);
$notificacoes = $stmt->fetchAll();

$corTipo = ['Informação' => 'var(--ul-blue)', 'Aviso' => 'var(--ul-gold)', 'Urgente' => 'var(--danger)'];

$menuTitulo = 'Notificações';
$paginaActiva = 'notificacoes';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🔔 Notificações</h2>
  <p class="sub">Avisos e comunicados da secretaria académica e do seu curso</p>

  <?php foreach ($notificacoes as $n): ?>
    <div style="border:1px solid var(--line);border-left:4px solid <?= $corTipo[$n['tipo']] ?? 'var(--ul-blue)' ?>;
                border-radius:10px;padding:14px 18px;margin-bottom:12px;background:<?= $n['lida'] ? '#fff' : 'var(--ul-blue-light)' ?>;">
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;">
        <div>
          <span class="badge" style="background:<?= $corTipo[$n['tipo']] ?? 'var(--ul-blue)' ?>;color:#fff;"><?= e($n['tipo']) ?></span>
          <b style="margin-left:8px;"><?= e($n['titulo']) ?></b>
        </div>
        <span style="font-size:11px;color:var(--muted);white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></span>
      </div>
      <p style="margin:10px 0 6px;font-size:13.5px;color:#3a4657;"><?= nl2br(e($n['mensagem'])) ?></p>
      <?php if (!$n['lida']): ?>
        <a href="<?= BASE_URL ?>/student/notificacoes.php?ler=<?= (int) $n['id'] ?>" style="font-size:12px;color:var(--ul-blue);text-decoration:none;font-weight:600;">Marcar como lida</a>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if (empty($notificacoes)): ?>
    <p>Não existem notificações no momento.</p>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

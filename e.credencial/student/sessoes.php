<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    "SELECT evento, ip, criado_em FROM sessoes_log
     WHERE utilizador_tipo = 'estudante' AND utilizador_id = ?
     ORDER BY criado_em DESC LIMIT 30"
);
$stmt->execute([$est['id']]);
$log = $stmt->fetchAll();

$menuTitulo = 'Histórico de Sessões';
$paginaActiva = 'sessoes';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🕓 Histórico de Sessões</h2>
  <p class="sub">Últimos acessos registados nesta conta (base de dados)</p>

  <table class="data">
    <tr><th>Evento</th><th>Endereço IP</th><th>Data/Hora</th></tr>
    <?php foreach ($log as $l): ?>
      <tr>
        <td><?= e($l['evento']) ?></td>
        <td><?= e($l['ip'] ?? '—') ?></td>
        <td><?= date('d/m/Y H:i:s', strtotime($l['criado_em'])) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($log)): ?>
      <tr><td colspan="3">Ainda não existem eventos registados.</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

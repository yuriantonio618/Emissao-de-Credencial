<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();

$filtro = $_GET['estado'] ?? '';
$sql = "SELECT p.*, e.nome AS estudante_nome, e.numero_estudante, c.nome AS curso_nome
        FROM pedidos_estagio p
        JOIN estudantes e ON e.id = p.estudante_id
        JOIN cursos c ON c.id = e.curso_id";
$params = [];
if (in_array($filtro, ['Aprovado', 'Reprovado'], true)) {
    $sql .= ' WHERE p.estado = ?';
    $params[] = $filtro;
}
$sql .= ' ORDER BY p.criado_em DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$total      = (int) $pdo->query('SELECT COUNT(*) FROM pedidos_estagio')->fetchColumn();
$aprovados  = (int) $pdo->query("SELECT COUNT(*) FROM pedidos_estagio WHERE estado='Aprovado'")->fetchColumn();
$reprovados = $total - $aprovados;

$menuTitulo = 'Pedidos de Estágio';
$paginaActivaAdmin = 'pedidos_estagio';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1150px;">
  <h2>🎓 Pedidos de Credencial de Estágio</h2>
  <p class="sub">Todos os pedidos submetidos pelos estudantes através do portal</p>

  <div class="stat-cards">
    <div class="stat-card"><div class="sc-num"><?= $total ?></div><div class="sc-label">Pedidos totais</div></div>
    <div class="stat-card"><div class="sc-num" style="color:var(--ok);"><?= $aprovados ?></div><div class="sc-label">Credenciais emitidas</div></div>
    <div class="stat-card"><div class="sc-num" style="color:var(--danger);"><?= $reprovados ?></div><div class="sc-label">Pedidos reprovados</div></div>
  </div>

  <div class="pill-row">
    <a class="pill" style="<?= $filtro === '' ? 'background:var(--ul-blue);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/pedidos_estagio.php">Todos</a>
    <a class="pill" style="<?= $filtro === 'Aprovado' ? 'background:var(--ok);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/pedidos_estagio.php?estado=Aprovado">Aprovados</a>
    <a class="pill" style="<?= $filtro === 'Reprovado' ? 'background:var(--danger);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/pedidos_estagio.php?estado=Reprovado">Reprovados</a>
    <a class="btn btn-gold btn-sm" style="margin-left:auto;" href="<?= BASE_URL ?>/admin/exportar_pedidos.php">⬇ Exportar (Excel/CSV)</a>
  </div>

  <div style="overflow-x:auto;">
    <table class="data">
      <tr><th>Estudante</th><th>Curso</th><th>Local de Estágio</th><th>Sector/Núcleo</th><th>Período</th><th>Data do Pedido</th><th>Estado</th><th>Motivo (se reprovado)</th></tr>
      <?php foreach ($pedidos as $p): ?>
        <tr>
          <td><?= e($p['numero_estudante']) ?> — <?= e($p['estudante_nome']) ?></td>
          <td><?= e($p['curso_nome']) ?></td>
          <td><?= e($p['local_estagio']) ?></td>
          <td><?= e($p['sector'] ?? '—') ?></td>
          <td><?= e($p['periodo']) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($p['criado_em'])) ?></td>
          <td><span class="badge <?= $p['estado'] === 'Aprovado' ? 'ok' : 'bad' ?>"><?= e($p['estado']) ?></span></td>
          <td style="font-size:11.5px;color:var(--muted);max-width:260px;"><?= e($p['motivo'] ?? '—') ?></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($pedidos)): ?><tr><td colspan="7">Não existem pedidos registados.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

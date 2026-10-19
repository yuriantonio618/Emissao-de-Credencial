<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    "SELECT descricao, tipo, valor, data_referencia, estado
     FROM financeiro WHERE estudante_id = ?
     ORDER BY data_referencia DESC"
);
$stmt->execute([$est['id']]);
$lancamentos = $stmt->fetchAll();

$totalPago = 0.0;
$totalPendente = 0.0;
foreach ($lancamentos as $l) {
    if ($l['estado'] === 'Pago') $totalPago += (float) $l['valor'];
    else $totalPendente += (float) $l['valor'];
}

$menuTitulo = 'Situação Financeira';
$paginaActiva = 'financeira';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>💰 Situação Financeira</h2>
  <p class="sub">Estado geral de propinas, taxas e emolumentos</p>

  <div class="stat-cards">
    <div class="stat-card">
      <div class="sc-num" style="color:var(--ok);"><?= formatarMoeda($totalPago) ?></div>
      <div class="sc-label">Total pago</div>
    </div>
    <div class="stat-card">
      <div class="sc-num" style="color:<?= $totalPendente > 0 ? 'var(--danger)' : 'var(--ok)' ?>;"><?= formatarMoeda($totalPendente) ?></div>
      <div class="sc-label">Total pendente</div>
    </div>
    <div class="stat-card">
      <div class="sc-num"><?= e($est['situacao_financeira']) ?></div>
      <div class="sc-label">Estado geral</div>
    </div>
  </div>

  <table class="data">
    <tr><th>Referência</th><th>Tipo</th><th>Data</th><th>Valor</th><th>Estado</th></tr>
    <?php foreach ($lancamentos as $l): ?>
      <tr>
        <td><?= e($l['descricao']) ?></td>
        <td><?= e($l['tipo']) ?></td>
        <td><?= formatarDataPt($l['data_referencia']) ?></td>
        <td><?= formatarMoeda((float) $l['valor']) ?></td>
        <td><span class="badge <?= $l['estado'] === 'Pago' ? 'ok' : 'bad' ?>"><?= e($l['estado']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($lancamentos)): ?>
      <tr><td colspan="5">Não existem lançamentos financeiros registados.</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

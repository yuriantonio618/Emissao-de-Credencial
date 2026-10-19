<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$mensagem = null;

// Simulação de pagamento: permite ao estudante regularizar um lançamento pendente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pagar_id'])) {
    $idLancamento = (int) $_POST['pagar_id'];
    $stmt = $pdo->prepare(
        "UPDATE financeiro SET estado = 'Pago' WHERE id = ? AND estudante_id = ? AND estado = 'Pendente'"
    );
    $stmt->execute([$idLancamento, $est['id']]);
    if ($stmt->rowCount() > 0) {
        $mensagem = 'Pagamento registado com sucesso. Obrigado!';
        // Se já não existirem lançamentos pendentes, regulariza a situação financeira geral
        $restam = $pdo->prepare("SELECT COUNT(*) FROM financeiro WHERE estudante_id = ? AND estado = 'Pendente'");
        $restam->execute([$est['id']]);
        if ((int) $restam->fetchColumn() === 0) {
            $upd = $pdo->prepare("UPDATE estudantes SET situacao_financeira = 'Regularizada' WHERE id = ?");
            $upd->execute([$est['id']]);
        }
        registarEvento('estudante', (int) $est['id'], 'Pagamento de emolumento/taxa registado (ref. #' . $idLancamento . ')');
    }
}

$stmt = $pdo->prepare(
    "SELECT id, descricao, tipo, valor, data_referencia, estado
     FROM financeiro WHERE estudante_id = ? AND tipo IN ('Emolumento','Taxa')
     ORDER BY data_referencia DESC"
);
$stmt->execute([$est['id']]);
$emolumentos = $stmt->fetchAll();

$menuTitulo = 'Pagamento de Emolumentos';
$paginaActiva = 'emolumentos';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🧾 Pagamento de Emolumentos</h2>
  <p class="sub">Taxas e emolumentos associados ao seu processo académico</p>

  <?php if ($mensagem): ?>
    <div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div>
  <?php endif; ?>

  <table class="data">
    <tr><th>Descrição</th><th>Data</th><th>Valor</th><th>Estado</th><th></th></tr>
    <?php foreach ($emolumentos as $em): ?>
      <tr>
        <td><?= e($em['descricao']) ?></td>
        <td><?= formatarDataPt($em['data_referencia']) ?></td>
        <td><?= formatarMoeda((float) $em['valor']) ?></td>
        <td><span class="badge <?= $em['estado'] === 'Pago' ? 'ok' : 'bad' ?>"><?= e($em['estado']) ?></span></td>
        <td>
          <?php if ($em['estado'] === 'Pendente'): ?>
            <form method="post" onsubmit="return confirm('Confirma o pagamento simulado desta taxa/emolumento?');">
              <input type="hidden" name="pagar_id" value="<?= (int) $em['id'] ?>">
              <button class="btn btn-gold btn-sm" type="submit">Pagar agora</button>
            </form>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($emolumentos)): ?>
      <tr><td colspan="5">Não existem emolumentos ou taxas registados.</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

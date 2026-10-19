<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/estagio_helpers.php';

exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$resultado = null; // ['ok' => bool, 'pendentes' => [], 'pedido_id' => int|null]

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $local       = trim($_POST['local_estagio'] ?? '');
    $sector      = trim($_POST['sector'] ?? '');
    $periodo     = trim($_POST['periodo'] ?? '');
    $dataInicio  = trim($_POST['data_inicio'] ?? '');

    if ($local === '' || $periodo === '' || $dataInicio === '') {
        $resultado = ['ok' => false, 'erroFormulario' => 'Preencha todos os campos obrigatórios do formulário antes de prosseguir.'];
    } else {
        $verificacao = verificarElegibilidadeEstagio($pdo, (int) $est['id'], (int) $est['curso_id']);

        if ($verificacao['elegivel']) {
            $stmt = $pdo->prepare(
                'INSERT INTO pedidos_estagio (estudante_id, local_estagio, sector, periodo, data_inicio, estado)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$est['id'], $local, $sector ?: null, $periodo, $dataInicio, 'Aprovado']);
            $pedidoId = (int) $pdo->lastInsertId();
            registarEvento('estudante', (int) $est['id'], 'Pedido de credencial de estágio aprovado (#' . $pedidoId . ')');
            $resultado = ['ok' => true, 'pedido_id' => $pedidoId];
        } else {
            $motivo = implode(' | ', $verificacao['pendentes']);
            $stmt = $pdo->prepare(
                'INSERT INTO pedidos_estagio (estudante_id, local_estagio, sector, periodo, data_inicio, estado, motivo)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$est['id'], $local, $sector ?: null, $periodo, $dataInicio, 'Reprovado', $motivo]);
            registarEvento('estudante', (int) $est['id'], 'Pedido de credencial de estágio reprovado (plano curricular incompleto)');
            $resultado = ['ok' => false, 'pendentes' => $verificacao['pendentes']];
        }
    }
}

// Histórico de pedidos deste estudante
$stmt = $pdo->prepare('SELECT * FROM pedidos_estagio WHERE estudante_id = ? ORDER BY criado_em DESC');
$stmt->execute([$est['id']]);
$historico = $stmt->fetchAll();

$menuTitulo = 'Emissão de Credencial de Estágio';
$paginaActiva = 'estagio';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🎓 Emissão de Credencial de Estágio</h2>
  <p class="sub">Preencha os dados do estágio. O sistema verifica automaticamente, no plano curricular
     registado na base de dados, se aprovou todas as cadeiras do 1º ao 3º ano antes de autorizar a emissão.</p>

  <?php if ($resultado && !empty($resultado['erroFormulario'])): ?>
    <div class="result-box bad"><h3>⚠ Campos em falta</h3><p><?= e($resultado['erroFormulario']) ?></p></div>
  <?php elseif ($resultado && $resultado['ok']): ?>
    <div class="result-box ok">
      <h3>✔ Estudante apto — pedido gerado com sucesso</h3>
      <p>Todas as cadeiras do 1º ao 3º ano encontram-se aprovadas. O requerimento foi preenchido
         automaticamente com os seus dados. Descarregue o documento em PDF abaixo.</p>
      <a class="btn btn-gold" style="margin-top:12px;" href="<?= BASE_URL ?>/student/estagio_pdf.php?id=<?= (int) $resultado['pedido_id'] ?>">
        ⬇ Descarregar Credencial em PDF
      </a>
    </div>
  <?php elseif ($resultado && !$resultado['ok']): ?>
    <div class="result-box bad">
      <h3>✖ Pedido não autorizado</h3>
      <p>Ainda não concluiu com aproveitamento todas as cadeiras do 1º ao 3º ano, requisito
         obrigatório para a emissão da Credencial de Estágio. Cadeiras pendentes:</p>
      <ul><?php foreach ($resultado['pendentes'] as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post" style="margin-top:22px;">
    <div class="form-grid">
      <div class="full">
        <label>Local de estágio</label>
        <input type="text" name="local_estagio" placeholder="Ex: Instituto Nacional de Estatística — Delegação da Beira" required>
      </div>
      <div class="full">
        <label>Sector / Núcleo de colocação (opcional)</label>
        <input type="text" name="sector" placeholder="Ex: Núcleo de Estatística e Planificação (NEP)">
      </div>
      <div>
        <label>Período do estágio</label>
        <select name="periodo" required>
          <option value="3 (três) meses">3 meses</option>
          <option value="4 (quatro) meses">4 meses</option>
          <option value="5 (cinco) meses">5 meses</option>
          <option value="6 (seis) meses">6 meses</option>
        </select>
      </div>
      <div>
        <label>Data de início prevista</label>
        <input type="date" name="data_inicio" required>
      </div>
    </div>
    <button class="btn" type="submit">🔍 Verificar elegibilidade e gerar pedido</button>
  </form>
</div>

<?php if (!empty($historico)): ?>
<div class="panel" style="margin-top:22px;">
  <h2>Histórico de pedidos</h2>
  <p class="sub">Pedidos de credencial de estágio já submetidos por si</p>
  <table class="data">
    <tr><th>Local de estágio</th><th>Sector/Núcleo</th><th>Período</th><th>Início</th><th>Data do pedido</th><th>Estado</th><th></th></tr>
    <?php foreach ($historico as $h): ?>
      <tr>
        <td><?= e($h['local_estagio']) ?></td>
        <td><?= e($h['sector'] ?? '—') ?></td>
        <td><?= e($h['periodo']) ?></td>
        <td><?= formatarDataPt($h['data_inicio']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></td>
        <td><span class="badge <?= $h['estado'] === 'Aprovado' ? 'ok' : 'bad' ?>"><?= e($h['estado']) ?></span></td>
        <td>
          <?php if ($h['estado'] === 'Aprovado'): ?>
            <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/student/estagio_pdf.php?id=<?= (int) $h['id'] ?>">⬇ PDF</a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

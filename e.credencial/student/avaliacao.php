<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$mensagem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['avaliacao_id'])) {
    $id = (int) $_POST['avaliacao_id'];
    $pontualidade    = max(1, min(5, (int) ($_POST['pontualidade'] ?? 0)));
    $clareza         = max(1, min(5, (int) ($_POST['clareza'] ?? 0)));
    $disponibilidade = max(1, min(5, (int) ($_POST['disponibilidade'] ?? 0)));
    $comentario      = trim($_POST['comentario'] ?? '');

    $stmt = $pdo->prepare(
        "UPDATE avaliacoes_docentes
         SET estado = 'Concluída', pontualidade = ?, clareza = ?, disponibilidade = ?, comentario = ?, respondido_em = NOW()
         WHERE id = ? AND estudante_id = ? AND estado = 'Pendente'"
    );
    $stmt->execute([$pontualidade, $clareza, $disponibilidade, $comentario, $id, $est['id']]);
    if ($stmt->rowCount() > 0) {
        $mensagem = 'Avaliação submetida. Obrigado pelo seu contributo.';
        registarEvento('estudante', (int) $est['id'], 'Avaliação docente submetida (#' . $id . ')');
    }
}

$stmt = $pdo->prepare('SELECT * FROM avaliacoes_docentes WHERE estudante_id = ? ORDER BY estado, docente');
$stmt->execute([$est['id']]);
$avaliacoes = $stmt->fetchAll();

$pendentes = array_filter($avaliacoes, fn($a) => $a['estado'] === 'Pendente');
$concluidas = array_filter($avaliacoes, fn($a) => $a['estado'] === 'Concluída');

$menuTitulo = 'Avaliação do Corpo Docente';
$paginaActiva = 'avaliacao';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>⭐ Avaliação do Corpo Docente</h2>
  <p class="sub">Questionário semestral e confidencial de avaliação dos docentes</p>

  <?php if ($mensagem): ?>
    <div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div>
  <?php endif; ?>

  <?php if (empty($pendentes)): ?>
    <div class="result-box ok">
      <h3>✔ Não existem avaliações pendentes</h3>
      <p>Todas as avaliações disponíveis para este período já foram submetidas.</p>
    </div>
  <?php endif; ?>

  <?php foreach ($pendentes as $a): ?>
    <div class="year-block" style="border:1px solid var(--line);border-radius:12px;padding:18px 20px;margin-bottom:16px;">
      <h3 style="margin-top:0;"><?= e($a['docente']) ?> — <?= e($a['disciplina']) ?></h3>
      <p class="sub" style="margin-bottom:14px;">Período: <?= e($a['periodo']) ?></p>
      <form method="post">
        <input type="hidden" name="avaliacao_id" value="<?= (int) $a['id'] ?>">
        <div class="form-grid">
          <div>
            <label>Pontualidade (1–5)</label>
            <select name="pontualidade" required>
              <option value="">Seleccione</option>
              <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div>
            <label>Clareza na exposição (1–5)</label>
            <select name="clareza" required>
              <option value="">Seleccione</option>
              <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div>
            <label>Disponibilidade para dúvidas (1–5)</label>
            <select name="disponibilidade" required>
              <option value="">Seleccione</option>
              <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"><?= $i ?></option><?php endfor; ?>
            </select>
          </div>
          <div class="full">
            <label>Comentário (opcional)</label>
            <textarea name="comentario" rows="3" placeholder="Sugestões ou observações..."></textarea>
          </div>
        </div>
        <button class="btn" type="submit">Submeter avaliação</button>
      </form>
    </div>
  <?php endforeach; ?>

  <?php if (!empty($concluidas)): ?>
    <h3 style="color:var(--ul-blue-dark);font-size:15px;">Avaliações já submetidas</h3>
    <table class="data">
      <tr><th>Docente</th><th>Disciplina</th><th>Período</th><th>Submetida em</th></tr>
      <?php foreach ($concluidas as $c): ?>
        <tr>
          <td><?= e($c['docente']) ?></td>
          <td><?= e($c['disciplina']) ?></td>
          <td><?= e($c['periodo']) ?></td>
          <td><?= $c['respondido_em'] ? date('d/m/Y H:i', strtotime($c['respondido_em'])) : '—' ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

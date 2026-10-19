<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;

$estudantes = $pdo->query(
    'SELECT e.id, e.numero_estudante, e.nome, e.curso_id, e.ano_frequencia, c.nome AS curso_nome
     FROM estudantes e JOIN cursos c ON c.id = e.curso_id
     ORDER BY c.nome, e.nome'
)->fetchAll();

$estudanteId = (int) ($_GET['estudante_id'] ?? ($_POST['estudante_id'] ?? ($estudantes[0]['id'] ?? 0)));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['notas']) && is_array($_POST['notas'])) {
    $estudanteId = (int) $_POST['estudante_id'];
    $stmtSel = $pdo->prepare('SELECT id FROM notas WHERE estudante_id = ? AND disciplina_id = ?');
    $stmtUpd = $pdo->prepare('UPDATE notas SET nota_frequencia = ?, nota_exame = ? WHERE id = ?');
    $stmtIns = $pdo->prepare('INSERT INTO notas (estudante_id, disciplina_id, nota_frequencia, nota_exame) VALUES (?,?,?,?)');

    $guardadas = 0;
    foreach ($_POST['notas'] as $disciplinaId => $par) {
        $disciplinaId = (int) $disciplinaId;
        $freqRaw  = trim($par['frequencia'] ?? '');
        $exameRaw = trim($par['exame'] ?? '');
        if ($freqRaw === '' && $exameRaw === '') continue; // nada a guardar nesta linha

        $freq  = $freqRaw !== '' ? max(0, min(20, (float) $freqRaw)) : null;
        $exame = $exameRaw !== '' ? max(0, min(20, (float) $exameRaw)) : null;

        $stmtSel->execute([$estudanteId, $disciplinaId]);
        $notaId = $stmtSel->fetchColumn();
        if ($notaId) {
            $stmtUpd->execute([$freq, $exame, $notaId]);
        } else {
            $stmtIns->execute([$estudanteId, $disciplinaId, $freq, $exame]);
        }
        $guardadas++;
    }
    $mensagem = $guardadas . ' nota(s) guardada(s) com sucesso.';
    registarEvento('admin', (int) $_SESSION['user_id'], 'Lançou notas para o estudante #' . $estudanteId);
}

$estudanteSel = null;
foreach ($estudantes as $s) {
    if ((int) $s['id'] === $estudanteId) { $estudanteSel = $s; break; }
}

$disciplinas = [];
if ($estudanteSel) {
    $stmt = $pdo->prepare(
        'SELECT d.id, d.ano, d.nome, n.nota_frequencia, n.nota_exame, n.nota_final
         FROM disciplinas d
         LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ?
         WHERE d.curso_id = ?
         ORDER BY d.ano, d.nome'
    );
    $stmt->execute([$estudanteId, $estudanteSel['curso_id']]);
    $disciplinas = $stmt->fetchAll();
}

$menuTitulo = 'Lançamento de Notas';
$paginaActivaAdmin = 'notas';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1000px;">
  <h2>📝 Lançamento de Notas</h2>
  <p class="sub">Seleccione um estudante para lançar ou corrigir as notas de frequência e exame de todas as disciplinas do seu curso</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>

  <form method="get" class="pill-row">
    <label style="font-size:12.5px;font-weight:600;color:var(--muted);align-self:center;">Estudante:</label>
    <select name="estudante_id" onchange="this.form.submit()" style="padding:8px 12px;border:1.5px solid var(--line);border-radius:9px;min-width:320px;">
      <?php foreach ($estudantes as $s): ?>
        <option value="<?= (int) $s['id'] ?>" <?= $estudanteId === (int) $s['id'] ? 'selected' : '' ?>>
          <?= e($s['numero_estudante']) ?> — <?= e($s['nome']) ?> (<?= e($s['curso_nome']) ?>, <?= (int) $s['ano_frequencia'] ?>º)
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($estudanteSel): ?>
    <form method="post" style="margin-top:18px;">
      <input type="hidden" name="estudante_id" value="<?= (int) $estudanteId ?>">
      <table class="data">
        <tr><th>Ano</th><th>Disciplina</th><th>Frequência</th><th>Exame</th><th>Final (calculado)</th><th>Situação</th></tr>
        <?php foreach ($disciplinas as $d): ?>
          <tr>
            <td><?= (int) $d['ano'] ?>º</td>
            <td><?= e($d['nome']) ?></td>
            <td><input type="number" step="0.1" min="0" max="20" name="notas[<?= (int) $d['id'] ?>][frequencia]"
                       value="<?= $d['nota_frequencia'] !== null ? e((string) $d['nota_frequencia']) : '' ?>"
                       style="width:72px;padding:6px 8px;border:1.5px solid var(--line);border-radius:7px;"></td>
            <td><input type="number" step="0.1" min="0" max="20" name="notas[<?= (int) $d['id'] ?>][exame]"
                       value="<?= $d['nota_exame'] !== null ? e((string) $d['nota_exame']) : '' ?>"
                       style="width:72px;padding:6px 8px;border:1.5px solid var(--line);border-radius:7px;"></td>
            <td><b><?= $d['nota_final'] !== null ? number_format((float) $d['nota_final'], 1) : '—' ?></b></td>
            <td>
              <?php if ($d['nota_final'] !== null): ?>
                <span class="badge <?= badgeEstadoNota((float) $d['nota_final']) ?>"><?= textoEstadoNota((float) $d['nota_final']) ?></span>
              <?php else: ?>—<?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($disciplinas)): ?><tr><td colspan="6">Este curso ainda não tem disciplinas registadas.</td></tr><?php endif; ?>
      </table>
      <?php if (!empty($disciplinas)): ?>
        <button class="btn" type="submit" style="margin-top:16px;">💾 Guardar todas as notas alteradas</button>
      <?php endif; ?>
    </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

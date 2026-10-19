<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT d.ano, d.nome, n.nota_frequencia, n.nota_exame, n.nota_final, n.ano_lectivo
     FROM notas n
     JOIN disciplinas d ON d.id = n.disciplina_id
     WHERE n.estudante_id = ?
     ORDER BY d.ano, d.nome'
);
$stmt->execute([$est['id']]);
$notas = $stmt->fetchAll();

$porAno = [];
foreach ($notas as $n) {
    $porAno[$n['ano']][] = $n;
}
ksort($porAno);

$menuTitulo = 'Notas de Frequência';
$paginaActiva = 'notas';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>📊 Notas de Frequência</h2>
  <p class="sub">Registo académico extraído da base de dados do <?= APP_NOME ?> — <?= e($est['curso_nome']) ?></p>

  <?php if (empty($porAno)): ?>
    <p>Ainda não existem notas lançadas para este estudante.</p>
  <?php endif; ?>

  <?php foreach ($porAno as $ano => $lista): ?>
    <div class="year-block">
      <h3><?= (int) $ano ?>º Ano</h3>
      <table class="data">
        <tr><th>Disciplina</th><th>Nota de Frequência</th><th>Nota de Exame</th><th>Nota Final</th><th>Situação</th></tr>
        <?php foreach ($lista as $d): ?>
          <tr>
            <td><?= e($d['nome']) ?></td>
            <td><?= $d['nota_frequencia'] !== null ? number_format((float) $d['nota_frequencia'], 1) : '—' ?></td>
            <td><?= $d['nota_exame'] !== null ? number_format((float) $d['nota_exame'], 1) : '—' ?></td>
            <td><b><?= number_format((float) $d['nota_final'], 1) ?></b></td>
            <td><span class="badge <?= badgeEstadoNota((float) $d['nota_final']) ?>"><?= textoEstadoNota((float) $d['nota_final']) ?></span></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

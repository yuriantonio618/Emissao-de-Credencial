<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT d.ano, d.nome, n.nota_exame, n.nota_final
     FROM notas n
     JOIN disciplinas d ON d.id = n.disciplina_id
     WHERE n.estudante_id = ? AND n.nota_exame IS NOT NULL
     ORDER BY d.ano, d.nome'
);
$stmt->execute([$est['id']]);
$notas = $stmt->fetchAll();

$porAno = [];
foreach ($notas as $n) {
    $porAno[$n['ano']][] = $n;
}
ksort($porAno);

$menuTitulo = 'Exames';
$paginaActiva = 'exames';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>📝 Exames</h2>
  <p class="sub">Resultados oficiais de exames por ano lectivo — Época Normal</p>

  <?php foreach ($porAno as $ano => $lista): ?>
    <div class="year-block">
      <h3><?= (int) $ano ?>º Ano</h3>
      <table class="data">
        <tr><th>Disciplina</th><th>Nota de Exame</th><th>Resultado</th></tr>
        <?php foreach ($lista as $d): ?>
          <tr>
            <td><?= e($d['nome']) ?></td>
            <td><?= number_format((float) $d['nota_exame'], 1) ?></td>
            <td><span class="badge <?= badgeEstadoNota((float) $d['nota_final']) ?>">
              <?= $d['nota_final'] >= 10 ? 'Aprovado' : 'Por Regularizar' ?>
            </span></td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

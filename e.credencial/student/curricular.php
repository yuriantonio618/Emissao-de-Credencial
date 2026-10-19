<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

// Todas as disciplinas do curso, com a nota do estudante (se existir)
$stmt = $pdo->prepare(
    'SELECT d.id, d.ano, d.nome, n.nota_final
     FROM disciplinas d
     LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ?
     WHERE d.curso_id = ?
     ORDER BY d.ano, d.nome'
);
$stmt->execute([$est['id'], $est['curso_id']]);
$disciplinas = $stmt->fetchAll();

$porAno = [];
$pendentes = [];
foreach ($disciplinas as $d) {
    $porAno[$d['ano']][] = $d;
    $temNota = $d['nota_final'] !== null;
    $aprovado = $temNota && (float) $d['nota_final'] >= 10;
    if ((!$temNota || !$aprovado) && (int) $d['ano'] <= 3) {
        $pendentes[] = $d['ano'] . 'º Ano — ' . $d['nome'];
    }
}
ksort($porAno);
$elegivelEstagio = empty($pendentes);

$menuTitulo = 'Plano Curricular';
$paginaActiva = 'curricular';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>📈 Plano Curricular</h2>
  <p class="sub">Situação académica completa do curso de <?= e($est['curso_nome']) ?> (base de elegibilidade para estágio)</p>

  <a class="btn btn-outline btn-sm" style="margin-bottom:16px;" href="<?= BASE_URL ?>/student/exportar_ficha.php">⬇ Exportar Ficha Académica (PDF)</a>

  <div class="result-box <?= $elegivelEstagio ? 'ok' : 'bad' ?>">
    <h3><?= $elegivelEstagio ? '✔ Todas as cadeiras do 1º ao 3º ano estão aprovadas' : '✖ Existem cadeiras pendentes até ao 3º ano' ?></h3>
    <?php if ($elegivelEstagio): ?>
      <p>O(a) estudante reúne os requisitos curriculares para solicitar estágio profissional.</p>
    <?php else: ?>
      <ul><?php foreach ($pendentes as $p): ?><li><?= e($p) ?></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>

  <?php foreach ($porAno as $ano => $lista): ?>
    <div class="year-block">
      <h3><?= (int) $ano ?>º Ano</h3>
      <table class="data">
        <tr><th>Disciplina</th><th>Nota Final</th><th>Situação</th></tr>
        <?php foreach ($lista as $d): ?>
          <?php $temNota = $d['nota_final'] !== null; ?>
          <tr>
            <td><?= e($d['nome']) ?></td>
            <td><?= $temNota ? number_format((float) $d['nota_final'], 1) : '—' ?></td>
            <td>
              <?php if ($temNota): ?>
                <span class="badge <?= badgeEstadoNota((float) $d['nota_final']) ?>"><?= textoEstadoNota((float) $d['nota_final']) ?></span>
              <?php else: ?>
                <span class="badge bad">Por leccionar</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

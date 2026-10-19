<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT i.*, c.nome AS curso_nome FROM inscricoes i
     JOIN cursos c ON c.id = i.curso_id
     WHERE i.estudante_id = ? ORDER BY i.ano_lectivo DESC'
);
$stmt->execute([$est['id']]);
$inscricoes = $stmt->fetchAll();

$menuTitulo = 'Inscrições';
$paginaActiva = 'inscricoes';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🗂️ Inscrições</h2>
  <p class="sub">Estado de inscrição por ano lectivo</p>

  <table class="data">
    <tr><th>Ano Lectivo</th><th>Curso</th><th>Ano de Frequência</th><th>Data de Inscrição</th><th>Estado</th></tr>
    <?php foreach ($inscricoes as $i): ?>
      <tr>
        <td><?= e($i['ano_lectivo']) ?></td>
        <td><?= e($i['curso_nome']) ?></td>
        <td><?= (int) $i['ano'] ?>º Ano</td>
        <td><?= formatarDataPt($i['data_inscricao']) ?></td>
        <td>
          <span class="badge <?= $i['estado'] === 'Confirmada' ? 'ok' : 'bad' ?>"><?= e($i['estado']) ?></span>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($inscricoes)): ?>
      <tr><td colspan="5">Não existem inscrições registadas.</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

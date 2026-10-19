<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT disciplina, titulo, tipo, data_publicacao
     FROM materiais WHERE curso_id = ?
     ORDER BY data_publicacao DESC'
);
$stmt->execute([$est['curso_id']]);
$materiais = $stmt->fetchAll();

$iconesTipo = [
    'Manual' => '📘', 'Slides' => '📽️', 'Exercícios' => '📝', 'Guia' => '🧭', 'Artigo' => '📄',
];

$menuTitulo = 'Material Académico';
$paginaActiva = 'material';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>📚 Material Académico</h2>
  <p class="sub">Documentos e recursos disponibilizados pelo departamento para <?= e($est['curso_nome']) ?></p>

  <table class="data">
    <tr><th>Tipo</th><th>Documento</th><th>Disciplina</th><th>Publicado em</th></tr>
    <?php foreach ($materiais as $m): ?>
      <tr>
        <td><?= $iconesTipo[$m['tipo']] ?? '📄' ?> <?= e($m['tipo']) ?></td>
        <td><?= e($m['titulo']) ?></td>
        <td><?= e($m['disciplina']) ?></td>
        <td><?= formatarDataPt($m['data_publicacao']) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($materiais)): ?>
      <tr><td colspan="4">Ainda não existe material publicado para este curso.</td></tr>
    <?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();

$menuTitulo = 'Dados Pessoais';
$paginaActiva = 'perfil';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🪪 Dados do Estudante</h2>
  <p class="sub">Informação pessoal registada no <?= APP_NOME ?></p>

  <table class="data">
    <tr><td style="width:220px;"><b>Nome completo</b></td><td><?= e($est['nome']) ?></td></tr>
    <tr><td><b>Filho de</b></td><td><?= e($est['nome_pai']) ?> e de <?= e($est['nome_mae']) ?></td></tr>
    <tr><td><b>Bilhete de Identidade</b></td><td><?= e($est['bi']) ?></td></tr>
    <tr><td><b>Emitido</b></td><td><?= e($est['bi_emissao']) ?></td></tr>
    <tr><td><b>Contacto</b></td><td><?= e($est['contacto']) ?></td></tr>
    <tr><td><b>E-mail</b></td><td><?= e($est['email']) ?></td></tr>
    <tr><td><b>Número de estudante</b></td><td><?= e($est['numero_estudante']) ?></td></tr>
    <tr><td><b>Curso</b></td><td><?= e($est['curso_nome']) ?></td></tr>
    <tr><td><b>Faculdade</b></td><td><?= e($est['faculdade']) ?></td></tr>
    <tr><td><b>Departamento</b></td><td><?= e($est['departamento']) ?></td></tr>
    <tr><td><b>Regime</b></td><td><?= e($est['regime']) ?></td></tr>
    <tr><td><b>Ano de frequência</b></td><td><?= (int) $est['ano_frequencia'] ?>º Ano</td></tr>
    <tr><td><b>Sexo</b></td><td><?= e($est['sexo']) ?></td></tr>
    <tr><td><b>Idade</b></td><td><?= (int) $est['idade'] ?> anos</td></tr>
    <tr><td><b>Ano lectivo</b></td><td><?= e($est['ano_lectivo']) ?></td></tr>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

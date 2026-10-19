<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();

$totalEstudantes = (int) $pdo->query('SELECT COUNT(*) FROM estudantes')->fetchColumn();

$porCurso = $pdo->query(
    'SELECT c.nome, COUNT(e.id) AS total
     FROM cursos c LEFT JOIN estudantes e ON e.curso_id = c.id
     GROUP BY c.id ORDER BY c.nome'
)->fetchAll();

$porRegime = $pdo->query(
    'SELECT regime, COUNT(*) AS total FROM estudantes GROUP BY regime'
)->fetchAll();

$porSexo = $pdo->query(
    'SELECT sexo, COUNT(*) AS total FROM estudantes GROUP BY sexo'
)->fetchAll();

$mediaIdades = (float) $pdo->query('SELECT AVG(idade) FROM estudantes')->fetchColumn();

$pendentesFinanceiro = (int) $pdo->query(
    "SELECT COUNT(DISTINCT estudante_id) FROM financeiro WHERE estado = 'Pendente'"
)->fetchColumn();

$avaliacoesPendentes = (int) $pdo->query(
    "SELECT COUNT(*) FROM avaliacoes_docentes WHERE estado = 'Pendente'"
)->fetchColumn();

$listaEstudantes = $pdo->query(
    'SELECT e.numero_estudante, e.nome, c.nome AS curso, e.regime, e.sexo, e.idade, e.ano_frequencia, e.situacao_financeira
     FROM estudantes e JOIN cursos c ON c.id = e.curso_id
     ORDER BY c.nome, e.nome'
)->fetchAll();

function barra(array $dados, int $total): string
{
    $html = '';
    foreach ($dados as $linha) {
        $label = $linha['nome'] ?? $linha['regime'] ?? $linha['sexo'] ?? '';
        $valor = (int) $linha['total'];
        $pct = $total ? round($valor / $total * 100) : 0;
        $html .= '<div class="stat-bar-row" style="display:grid;grid-template-columns:1fr 2fr auto;align-items:center;gap:10px;margin-bottom:9px;font-size:12px;">'
               . '<div>' . e($label) . '</div>'
               . '<div style="height:9px;border-radius:20px;background:var(--ul-blue-light);overflow:hidden;">'
               . '<div style="height:100%;border-radius:20px;background:linear-gradient(90deg,var(--ul-blue),var(--ul-blue-dark));width:' . $pct . '%;"></div></div>'
               . '<div style="color:var(--muted);font-weight:600;">' . $valor . ' &middot; ' . $pct . '%</div>'
               . '</div>';
    }
    return $html;
}

$totalPedidosEstagio = (int) $pdo->query('SELECT COUNT(*) FROM pedidos_estagio')->fetchColumn();
$pedidosAprovados    = (int) $pdo->query("SELECT COUNT(*) FROM pedidos_estagio WHERE estado = 'Aprovado'")->fetchColumn();
$pedidosReprovados   = $totalPedidosEstagio - $pedidosAprovados;

$menuTitulo = 'Dashboard do Administrador';
$paginaActivaAdmin = 'dashboard';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1150px;">
  <h2>📊 Dashboard Estatístico — <?= APP_NOME ?></h2>
  <p class="sub">Visão geral de todos os estudantes registados no sistema, por curso, regime e situação.</p>

  <div class="stat-cards">
    <div class="stat-card"><div class="sc-num"><?= $totalEstudantes ?></div><div class="sc-label">Estudantes registados</div></div>
    <div class="stat-card"><div class="sc-num"><?= count($porCurso) ?></div><div class="sc-label">Cursos activos</div></div>
    <div class="stat-card"><div class="sc-num" style="color:<?= $pendentesFinanceiro ? 'var(--danger)' : 'var(--ok)' ?>;"><?= $pendentesFinanceiro ?></div><div class="sc-label">Estudantes com dívidas</div></div>
    <div class="stat-card"><div class="sc-num"><?= $avaliacoesPendentes ?></div><div class="sc-label">Avaliações docentes pendentes</div></div>
    <div class="stat-card"><div class="sc-num" style="color:var(--ok);"><?= $pedidosAprovados ?></div><div class="sc-label">Credenciais de estágio emitidas</div></div>
    <div class="stat-card"><div class="sc-num" style="color:var(--danger);"><?= $pedidosReprovados ?></div><div class="sc-label">Pedidos de estágio reprovados</div></div>
    <div class="stat-card"><div class="sc-num"><?= number_format($mediaIdades, 1) ?></div><div class="sc-label">Média de idades</div></div>
  </div>

  <div class="quick-cards" style="margin-bottom:24px;">
    <a class="qcard" href="<?= BASE_URL ?>/admin/estudantes.php"><div class="ic">🧑‍🎓</div><h4>Gestão de Estudantes</h4><p>Adicionar, editar e remover estudantes</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/admin/cursos.php"><div class="ic">🏛️</div><h4>Gestão de Cursos</h4><p>Faculdades, departamentos e cursos</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/admin/disciplinas.php"><div class="ic">📚</div><h4>Gestão de Disciplinas</h4><p>Plano curricular por curso e ano</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/admin/notas.php"><div class="ic">📝</div><h4>Lançamento de Notas</h4><p>Frequência e exame por estudante</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/admin/pedidos_estagio.php"><div class="ic">🎓</div><h4>Pedidos de Estágio</h4><p>Ver credenciais emitidas e reprovadas</p></a>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px;margin-bottom:8px;">
    <div style="border:1px solid var(--line);border-radius:12px;padding:16px 18px;background:#fff;">
      <h3 style="margin:0 0 12px;font-size:13.5px;color:var(--ul-blue-dark);">Estudantes por Curso</h3>
      <?= barra($porCurso, $totalEstudantes) ?>
    </div>
    <div style="border:1px solid var(--line);border-radius:12px;padding:16px 18px;background:#fff;">
      <h3 style="margin:0 0 12px;font-size:13.5px;color:var(--ul-blue-dark);">Estudantes por Regime</h3>
      <?= barra($porRegime, $totalEstudantes) ?>
    </div>
    <div style="border:1px solid var(--line);border-radius:12px;padding:16px 18px;background:#fff;">
      <h3 style="margin:0 0 12px;font-size:13.5px;color:var(--ul-blue-dark);">Estudantes por Sexo</h3>
      <?= barra($porSexo, $totalEstudantes) ?>
    </div>
  </div>

  <h3 style="margin-top:28px;color:var(--ul-blue-dark);font-size:15px;">Lista de estudantes</h3>
  <div style="overflow-x:auto;">
    <table class="data">
      <tr><th>Nº Estudante</th><th>Nome</th><th>Curso</th><th>Ano</th><th>Regime</th><th>Sexo</th><th>Idade</th><th>Situação Financeira</th></tr>
      <?php foreach ($listaEstudantes as $s): ?>
        <tr>
          <td><?= e($s['numero_estudante']) ?></td>
          <td><?= e($s['nome']) ?></td>
          <td><?= e($s['curso']) ?></td>
          <td><?= (int) $s['ano_frequencia'] ?>º</td>
          <td><?= e($s['regime']) ?></td>
          <td><?= e($s['sexo']) ?></td>
          <td><?= (int) $s['idade'] ?></td>
          <td><span class="badge <?= $s['situacao_financeira'] === 'Regularizada' ? 'ok' : 'bad' ?>"><?= e($s['situacao_financeira']) ?></span></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

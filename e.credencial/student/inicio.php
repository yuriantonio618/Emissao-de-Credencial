<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

// Indicadores rápidos para o painel inicial
$totalDisciplinas = $pdo->prepare(
    'SELECT COUNT(*) FROM notas n JOIN disciplinas d ON d.id = n.disciplina_id WHERE n.estudante_id = ?'
);
$totalDisciplinas->execute([$est['id']]);
$nTotal = (int) $totalDisciplinas->fetchColumn();

$aprovadasStmt = $pdo->prepare('SELECT COUNT(*) FROM notas WHERE estudante_id = ? AND nota_final >= 10');
$aprovadasStmt->execute([$est['id']]);
$nAprovadas = (int) $aprovadasStmt->fetchColumn();

$pendenteFinStmt = $pdo->prepare("SELECT COUNT(*) FROM financeiro WHERE estudante_id = ? AND estado = 'Pendente'");
$pendenteFinStmt->execute([$est['id']]);
$nPendenteFin = (int) $pendenteFinStmt->fetchColumn();

$menuTitulo = 'Início';
$paginaActiva = 'inicio';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="welcome-hero">
  <div class="emblem-sm"><?= e(iniciais($est['nome'])) ?></div>
  <div>
    <h2>Bem-vindo(a), <?= e($est['nome']) ?></h2>
    <p><?= e($est['curso_nome']) ?> · <?= (int) $est['ano_frequencia'] ?>º Ano · Regime <?= e($est['regime']) ?> · Ano lectivo <?= e($est['ano_lectivo']) ?></p>
  </div>
</div>

<div class="stat-cards">
  <div class="stat-card">
    <div class="sc-num"><?= $nTotal ?></div>
    <div class="sc-label">Disciplinas com nota lançada</div>
  </div>
  <div class="stat-card">
    <div class="sc-num" style="color:var(--ok);"><?= $nAprovadas ?></div>
    <div class="sc-label">Disciplinas aprovadas</div>
  </div>
  <div class="stat-card">
    <div class="sc-num" style="color:<?= $nPendenteFin ? 'var(--danger)' : 'var(--ok)' ?>;"><?= $nPendenteFin ?></div>
    <div class="sc-label">Lançamentos financeiros pendentes</div>
  </div>
  <div class="stat-card">
    <div class="sc-num"><?= e($est['situacao_financeira']) ?></div>
    <div class="sc-label">Situação financeira geral</div>
  </div>
</div>

<div class="panel">
  <h2>Acesso rápido</h2>
  <p class="sub">Os módulos mais usados do seu portal académico</p>
  <div class="quick-cards">
    <a class="qcard" href="<?= BASE_URL ?>/student/notas.php"><div class="ic">📊</div><h4>Notas de Frequência</h4><p>Consultar notas por ano lectivo</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/curricular.php"><div class="ic">📈</div><h4>Plano Curricular</h4><p>Ver o estado de todas as cadeiras</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/financeira.php"><div class="ic">💰</div><h4>Situação Financeira</h4><p>Verificar pagamentos e dívidas</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/horarios.php"><div class="ic">🕒</div><h4>Horários</h4><p>Consultar o horário semanal da turma</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/avaliacao.php"><div class="ic">⭐</div><h4>Avaliação do Corpo Docente</h4><p>Responda aos questionários pendentes</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/emolumentos.php"><div class="ic">🧾</div><h4>Emolumentos</h4><p>Histórico de pagamentos e taxas</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/estagio.php"><div class="ic">🎓</div><h4>Credencial de Estágio</h4><p>Solicitar e emitir em PDF</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/documentos.php"><div class="ic">📎</div><h4>Documentos</h4><p>Enviar documentos para a secretaria</p></a>
    <a class="qcard" href="<?= BASE_URL ?>/student/notificacoes.php"><div class="ic">🔔</div><h4>Notificações</h4><p>Avisos e comunicados<?= $notificacoesPorLer ? ' — ' . $notificacoesPorLer . ' novo(s)' : '' ?></p></a>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/includes/auth.php';

// Se já existir sessão válida, avança directamente para o painel correcto
if (utilizadorAutenticado()) {
    redirecionar($_SESSION['user_type'] === 'admin' ? '/admin/dashboard.php' : '/student/inicio.php');
}

$pdo = getPDO();
$estudantesDemo = $pdo->query(
    'SELECT e.numero_estudante, e.nome, c.nome AS curso_nome
     FROM estudantes e JOIN cursos c ON c.id = e.curso_id
     ORDER BY c.nome, e.nome'
)->fetchAll();

$expirada = isset($_GET['expirada']);
$erro = isset($_GET['erro']);
?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SIGEUL — Autenticação</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-screen">
  <div class="login-card">
    <div class="login-head">
      <img class="emblem" src="<?= BASE_URL ?>/assets/img/logo_ul.png" alt="Universidade Licungo">
      <h1>Sistema de Gestão Universitária</h1>
      <p><?= APP_NOME ?> &middot; <?= APP_INSTITUICAO ?></p>
    </div>
    <div class="login-body">
      <?php if ($expirada): ?>
        <div class="login-info">A sua sessão anterior expirou por inactividade. Autentique-se novamente.</div>
      <?php endif; ?>
      <?php if ($erro): ?>
        <div class="login-error">Número de utilizador ou senha incorrectos.</div>
      <?php endif; ?>

      <form method="post" action="<?= BASE_URL ?>/login_process.php">
        <div class="field">
          <label>Número de estudante / utilizador</label>
          <input type="text" name="utilizador" id="campoUtilizador" placeholder="ex: 04.0021.2023" autocomplete="off" required>
        </div>
        <div class="field">
          <label>Senha</label>
          <input type="password" name="senha" id="campoSenha" placeholder="••••••••" required>
        </div>
        <button class="btn-primary" type="submit">Autenticar</button>
      </form>
      <div style="margin-top:12px;text-align:center;">
        <a href="<?= BASE_URL ?>/esqueci_senha.php" style="font-size:12.5px;color:var(--ul-blue);text-decoration:none;">Esqueceu-se da senha?</a>
      </div>

      <div class="demo-box">
        <b>Contas de demonstração</b> (clique para preencher — senha comum: <code>estagio123</code>):
        <?php $cursoAnterior = null; foreach ($estudantesDemo as $s): ?>
          <?php if ($s['curso_nome'] !== $cursoAnterior): $cursoAnterior = $s['curso_nome']; ?>
            <div style="margin-top:8px;font-weight:700;color:var(--ul-blue-dark);"><?= e($s['curso_nome']) ?></div>
          <?php endif; ?>
          <a href="#" class="demo-row" onclick="preencher('<?= e($s['numero_estudante']) ?>','estagio123');return false;">
            <span><?= e($s['numero_estudante']) ?> — <?= e(explode(' ', $s['nome'])[0]) ?></span>
            <span class="demo-tag tag-course">estudante</span>
          </a>
        <?php endforeach; ?>
        <div style="margin-top:8px;border-top:1px dashed var(--line);padding-top:8px;">
          <a href="#" class="demo-row" onclick="preencher('admin','admin2026');return false;">
            <span>admin — Administrador</span>
            <span class="demo-tag tag-admin">admin</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
function preencher(u, p){
  document.getElementById('campoUtilizador').value = u;
  document.getElementById('campoSenha').value = p;
}
</script>
</body>
</html>

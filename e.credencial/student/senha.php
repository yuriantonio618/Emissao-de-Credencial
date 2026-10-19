<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$mensagem = null;
$erro = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $actual = (string) ($_POST['senha_actual'] ?? '');
    $nova1  = (string) ($_POST['senha_nova'] ?? '');
    $nova2  = (string) ($_POST['senha_nova2'] ?? '');

    if (!password_verify($actual, $est['senha_hash'])) {
        $erro = 'Senha actual incorrecta.';
    } elseif (mb_strlen($nova1) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($nova1 !== $nova2) {
        $erro = 'As senhas não coincidem.';
    } else {
        $novoHash = password_hash($nova1, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE estudantes SET senha_hash = ? WHERE id = ?');
        $stmt->execute([$novoHash, $est['id']]);
        registarEvento('estudante', (int) $est['id'], 'Senha alterada com sucesso');
        $mensagem = 'Senha actualizada com sucesso.';
    }
}

$menuTitulo = 'Alterar Senha';
$paginaActiva = 'senha';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🔑 Alterar Senha</h2>
  <p class="sub">Actualize a sua senha de acesso ao sistema</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>
  <?php if ($erro): ?><div class="result-box bad"><h3>✖ <?= e($erro) ?></h3></div><?php endif; ?>

  <form method="post">
    <div class="form-grid">
      <div class="full"><label>Senha actual</label><input type="password" name="senha_actual" required></div>
      <div><label>Nova senha</label><input type="password" name="senha_nova" required minlength="6"></div>
      <div><label>Confirmar nova senha</label><input type="password" name="senha_nova2" required minlength="6"></div>
    </div>
    <button class="btn" type="submit">Guardar alterações</button>
  </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

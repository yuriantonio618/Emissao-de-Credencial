<?php
require_once __DIR__ . '/includes/auth.php';

$pdo = getPDO();
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$mensagem = null;
$erro = null;
$tokenValido = null;

if ($token !== '') {
    $stmt = $pdo->prepare(
        "SELECT t.*, e.nome, e.numero_estudante FROM reset_senha_tokens t
         JOIN estudantes e ON e.id = t.estudante_id
         WHERE t.token = ? AND t.usado = 0 AND t.expira_em > NOW()"
    );
    $stmt->execute([$token]);
    $tokenValido = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenValido) {
    $nova1 = (string) ($_POST['senha_nova'] ?? '');
    $nova2 = (string) ($_POST['senha_nova2'] ?? '');

    if (mb_strlen($nova1) < 6) {
        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($nova1 !== $nova2) {
        $erro = 'As senhas não coincidem.';
    } else {
        $novoHash = password_hash($nova1, PASSWORD_DEFAULT);
        $pdo->prepare('UPDATE estudantes SET senha_hash = ? WHERE id = ?')->execute([$novoHash, $tokenValido['estudante_id']]);
        $pdo->prepare('UPDATE reset_senha_tokens SET usado = 1 WHERE id = ?')->execute([$tokenValido['id']]);
        registarEvento('estudante', (int) $tokenValido['estudante_id'], 'Senha redefinida via recuperação por e-mail');
        $mensagem = 'Senha redefinida com sucesso. Já pode entrar com a nova senha.';
        $tokenValido = null; // esconde o formulário
    }
}
?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Redefinir Senha — <?= APP_NOME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-screen">
  <div class="login-card">
    <div class="login-head">
      <img class="emblem" src="<?= BASE_URL ?>/assets/img/logo_ul.png" alt="Universidade Licungo">
      <h1>Definir Nova Senha</h1>
      <p><?= APP_NOME ?> &middot; <?= APP_INSTITUICAO ?></p>
    </div>
    <div class="login-body">
      <?php if ($mensagem): ?>
        <div class="login-info"><?= e($mensagem) ?></div>
        <a class="btn-primary" style="display:block;text-align:center;text-decoration:none;" href="<?= BASE_URL ?>/index.php">Ir para a autenticação</a>
      <?php elseif (!$token || !$tokenValido): ?>
        <div class="login-error">Este link é inválido ou já expirou. Solicite uma nova recuperação de senha.</div>
        <a class="btn-primary" style="display:block;text-align:center;text-decoration:none;" href="<?= BASE_URL ?>/esqueci_senha.php">Solicitar novo link</a>
      <?php else: ?>
        <p style="font-size:12.5px;color:var(--muted);margin-top:0;">
          Olá <b><?= e($tokenValido['nome']) ?></b> (<?= e($tokenValido['numero_estudante']) ?>). Defina a sua nova senha.
        </p>
        <?php if ($erro): ?><div class="login-error"><?= e($erro) ?></div><?php endif; ?>
        <form method="post">
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="field"><label>Nova senha</label><input type="password" name="senha_nova" required minlength="6"></div>
          <div class="field"><label>Confirmar nova senha</label><input type="password" name="senha_nova2" required minlength="6"></div>
          <button class="btn-primary" type="submit">Guardar nova senha</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>

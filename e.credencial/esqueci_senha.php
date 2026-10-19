<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (utilizadorAutenticado()) {
    redirecionar($_SESSION['user_type'] === 'admin' ? '/admin/dashboard.php' : '/student/inicio.php');
}

$pdo = getPDO();
$mensagem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = trim($_POST['identificador'] ?? '');

    if ($identificador !== '') {
        $stmt = $pdo->prepare('SELECT * FROM estudantes WHERE numero_estudante = ? OR email = ?');
        $stmt->execute([$identificador, $identificador]);
        $est = $stmt->fetch();

        if ($est) {
            $token = gerarTokenSeguro();
            $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $ins = $pdo->prepare('INSERT INTO reset_senha_tokens (estudante_id, token, expira_em) VALUES (?, ?, ?)');
            $ins->execute([$est['id'], $token, $expira]);

            $link = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/redefinir_senha.php?token=' . $token;
            $corpo = '<p>Olá ' . e($est['nome']) . ',</p>' .
                     '<p>Recebemos um pedido de recuperação de senha para a sua conta ' . APP_NOME . '.</p>' .
                     '<p><a href="' . e($link) . '">Clique aqui para definir uma nova senha</a> (válido por 1 hora).</p>' .
                     '<p>Se não foi você a solicitar, ignore esta mensagem.</p>';
            enviarEmail($est['email'] ?: ($est['numero_estudante'] . '@ul.ac.mz'), 'Recuperação de senha — ' . APP_NOME, $corpo);
        }
        // Mensagem genérica sempre igual, para não revelar se a conta existe
        $mensagem = 'Se os dados corresponderem a uma conta existente, enviámos instruções de recuperação para o e-mail associado.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-MZ">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Recuperar Senha — <?= APP_NOME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-screen">
  <div class="login-card">
    <div class="login-head">
      <img class="emblem" src="<?= BASE_URL ?>/assets/img/logo_ul.png" alt="Universidade Licungo">
      <h1>Recuperação de Senha</h1>
      <p><?= APP_NOME ?> &middot; <?= APP_INSTITUICAO ?></p>
    </div>
    <div class="login-body">
      <?php if ($mensagem): ?>
        <div class="login-info"><?= e($mensagem) ?></div>
      <?php else: ?>
        <p style="font-size:12.5px;color:var(--muted);margin-top:0;">
          Indique o seu número de estudante ou o e-mail registado. Enviaremos um link para
          definir uma nova senha.
        </p>
        <form method="post">
          <div class="field">
            <label>Número de estudante ou e-mail</label>
            <input type="text" name="identificador" required autofocus>
          </div>
          <button class="btn-primary" type="submit">Enviar instruções</button>
        </form>
      <?php endif; ?>
      <div style="margin-top:16px;text-align:center;">
        <a href="<?= BASE_URL ?>/index.php" style="font-size:12.5px;color:var(--ul-blue);text-decoration:none;">← Voltar à autenticação</a>
      </div>
    </div>
  </div>
</div>
</body>
</html>

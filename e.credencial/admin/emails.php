<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$emails = $pdo->query('SELECT * FROM emails_log ORDER BY criado_em DESC LIMIT 100')->fetchAll();

$verId = isset($_GET['ver']) ? (int) $_GET['ver'] : null;
$emailAberto = null;
if ($verId) {
    $stmt = $pdo->prepare('SELECT * FROM emails_log WHERE id = ?');
    $stmt->execute([$verId]);
    $emailAberto = $stmt->fetch();
}

$menuTitulo = 'Caixa de Saída de E-mails';
$paginaActivaAdmin = 'emails';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1100px;">
  <h2>✉️ Caixa de Saída de E-mails (modo de desenvolvimento)</h2>
  <p class="sub">Todas as mensagens do sistema (ex: recuperação de senha) ficam aqui registadas,
     mesmo quando não existe um servidor SMTP real configurado — útil para testar o fluxo em
     ambiente de desenvolvimento.</p>

  <?php if ($emailAberto): ?>
    <div class="result-box ok" style="margin-bottom:20px;">
      <h3>Assunto: <?= e($emailAberto['assunto']) ?></h3>
      <p><b>Para:</b> <?= e($emailAberto['destinatario']) ?> &middot; <b>Estado:</b> <?= e($emailAberto['estado']) ?> &middot; <?= date('d/m/Y H:i', strtotime($emailAberto['criado_em'])) ?></p>
      <div style="background:#fff;border:1px solid var(--line);border-radius:8px;padding:14px 16px;margin-top:10px;font-size:13px;">
        <?= $emailAberto['corpo'] /* corpo HTML gerado internamente pelo sistema */ ?>
      </div>
    </div>
    <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/emails.php">← Voltar à lista</a>
  <?php else: ?>
    <table class="data">
      <tr><th>Destinatário</th><th>Assunto</th><th>Estado</th><th>Data</th><th></th></tr>
      <?php foreach ($emails as $em): ?>
        <tr>
          <td><?= e($em['destinatario']) ?></td>
          <td><?= e($em['assunto']) ?></td>
          <td><span class="badge <?= $em['estado'] === 'Enviado' ? 'ok' : ($em['estado'] === 'Falhou' ? 'bad' : '') ?>" style="<?= $em['estado'] === 'Simulado' ? 'background:var(--ul-blue-light);color:var(--ul-blue-dark);' : '' ?>"><?= e($em['estado']) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($em['criado_em'])) ?></td>
          <td><a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/emails.php?ver=<?= (int) $em['id'] ?>">Ver</a></td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($emails)): ?><tr><td colspan="5">Ainda não foi registado nenhum e-mail.</td></tr><?php endif; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

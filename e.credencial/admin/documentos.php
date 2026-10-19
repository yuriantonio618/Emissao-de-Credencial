<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload_helpers.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accao'])) {
    $id = (int) $_POST['id'];
    if (in_array($_POST['accao'], ['aprovar', 'rejeitar'], true)) {
        $estado = $_POST['accao'] === 'aprovar' ? 'Aprovado' : 'Rejeitado';
        $obs = trim($_POST['observacao'] ?? '');
        $stmt = $pdo->prepare('UPDATE documentos SET estado = ?, observacao = ?, revisto_em = NOW() WHERE id = ?');
        $stmt->execute([$estado, $obs ?: null, $id]);
        $mensagem = 'Documento marcado como "' . $estado . '".';
    }
}

$filtro = $_GET['estado'] ?? '';
$sql = "SELECT d.*, e.nome AS estudante_nome, e.numero_estudante, c.nome AS curso_nome
        FROM documentos d JOIN estudantes e ON e.id = d.estudante_id JOIN cursos c ON c.id = e.curso_id";
$params = [];
if (in_array($filtro, ['Pendente', 'Aprovado', 'Rejeitado'], true)) {
    $sql .= ' WHERE d.estado = ?';
    $params[] = $filtro;
}
$sql .= ' ORDER BY d.enviado_em DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$documentos = $stmt->fetchAll();

$pendentesTotal = (int) $pdo->query("SELECT COUNT(*) FROM documentos WHERE estado='Pendente'")->fetchColumn();

$menuTitulo = 'Documentos Submetidos';
$paginaActivaAdmin = 'documentos';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1150px;">
  <h2>📎 Documentos Submetidos pelos Estudantes</h2>
  <p class="sub"><?= $pendentesTotal ?> documento(s) aguardam revisão</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>

  <div class="pill-row">
    <a class="pill" style="<?= $filtro === '' ? 'background:var(--ul-blue);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/documentos.php">Todos</a>
    <a class="pill" style="<?= $filtro === 'Pendente' ? 'background:var(--ul-blue-dark);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/documentos.php?estado=Pendente">Pendentes</a>
    <a class="pill" style="<?= $filtro === 'Aprovado' ? 'background:var(--ok);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/documentos.php?estado=Aprovado">Aprovados</a>
    <a class="pill" style="<?= $filtro === 'Rejeitado' ? 'background:var(--danger);color:#fff;' : '' ?>" href="<?= BASE_URL ?>/admin/documentos.php?estado=Rejeitado">Rejeitados</a>
  </div>

  <div style="overflow-x:auto;">
    <table class="data">
      <tr><th>Estudante</th><th>Curso</th><th>Tipo</th><th>Ficheiro</th><th>Enviado</th><th>Estado</th><th>Acção</th></tr>
      <?php foreach ($documentos as $d): ?>
        <tr>
          <td><?= e($d['numero_estudante']) ?> — <?= e($d['estudante_nome']) ?></td>
          <td><?= e($d['curso_nome']) ?></td>
          <td><?= e($d['tipo_documento']) ?></td>
          <td><a href="<?= e(caminhoPublicoDocumento($d['nome_ficheiro'])) ?>" target="_blank" rel="noopener">📄 Ver</a></td>
          <td><?= date('d/m/Y', strtotime($d['enviado_em'])) ?></td>
          <td><span class="badge <?= $d['estado'] === 'Aprovado' ? 'ok' : ($d['estado'] === 'Rejeitado' ? 'bad' : '') ?>" style="<?= $d['estado'] === 'Pendente' ? 'background:var(--ul-blue-light);color:var(--ul-blue-dark);' : '' ?>"><?= e($d['estado']) ?></span></td>
          <td style="min-width:220px;">
            <?php if ($d['estado'] === 'Pendente'): ?>
              <form method="post" style="display:flex;gap:6px;flex-wrap:wrap;">
                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                <input type="text" name="observacao" placeholder="Observação (opcional)" style="flex:1;min-width:140px;padding:6px 8px;border:1.5px solid var(--line);border-radius:7px;font-size:12px;">
                <button class="btn btn-sm" style="background:var(--ok);box-shadow:none;" type="submit" name="accao" value="aprovar">✔ Aprovar</button>
                <button class="btn btn-sm btn-outline" style="color:var(--danger);border-color:#f3c6c2;" type="submit" name="accao" value="rejeitar">✖ Rejeitar</button>
              </form>
            <?php else: ?>
              <span style="font-size:11.5px;color:var(--muted);"><?= e($d['observacao'] ?? '—') ?></span>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($documentos)): ?><tr><td colspan="7">Nenhum documento encontrado.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/upload_helpers.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();
$mensagem = null;
$erro = null;

$tiposDocumento = [
    'Bilhete de Identidade', 'Certificado de Habilitações', 'Comprovativo de Pagamento',
    'Fotografia Tipo Passe', 'Declaração de Estágio', 'Outro',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accao'])) {
    if ($_POST['accao'] === 'enviar') {
        $tipo = trim($_POST['tipo_documento'] ?? '');
        if ($tipo === '' || empty($_FILES['ficheiro'])) {
            $erro = 'Seleccione o tipo de documento e o ficheiro a enviar.';
        } else {
            [$ok, $resultado] = processarUploadDocumento($_FILES['ficheiro'], (int) $est['id']);
            if ($ok) {
                $stmt = $pdo->prepare(
                    'INSERT INTO documentos (estudante_id, tipo_documento, nome_original, nome_ficheiro, tamanho)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$est['id'], $tipo, $_FILES['ficheiro']['name'], $resultado, $_FILES['ficheiro']['size']]);
                registarEvento('estudante', (int) $est['id'], 'Enviou documento: ' . $tipo);
                $mensagem = 'Documento enviado com sucesso. Aguarda revisão pela secretaria.';
            } else {
                $erro = $resultado;
            }
        }
    } elseif ($_POST['accao'] === 'apagar') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare("SELECT * FROM documentos WHERE id = ? AND estudante_id = ? AND estado = 'Pendente'");
        $stmt->execute([$id, $est['id']]);
        $doc = $stmt->fetch();
        if ($doc) {
            @unlink(__DIR__ . '/../uploads/documentos/' . $doc['nome_ficheiro']);
            $pdo->prepare('DELETE FROM documentos WHERE id = ?')->execute([$id]);
            $mensagem = 'Documento removido.';
        }
    }
}

$stmt = $pdo->prepare('SELECT * FROM documentos WHERE estudante_id = ? ORDER BY enviado_em DESC');
$stmt->execute([$est['id']]);
$documentos = $stmt->fetchAll();

$menuTitulo = 'Documentos';
$paginaActiva = 'documentos';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>📎 Os Meus Documentos</h2>
  <p class="sub">Envie documentos digitalizados para a secretaria académica (PDF, JPG ou PNG — máx. 5 MB)</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>
  <?php if ($erro): ?><div class="result-box bad"><h3>✖ <?= e($erro) ?></h3></div><?php endif; ?>

  <form method="post" enctype="multipart/form-data" style="margin-bottom:26px;">
    <input type="hidden" name="accao" value="enviar">
    <div class="form-grid">
      <div>
        <label>Tipo de documento</label>
        <select name="tipo_documento" required>
          <?php foreach ($tiposDocumento as $t): ?><option><?= e($t) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Ficheiro (PDF, JPG ou PNG)</label>
        <input type="file" name="ficheiro" accept=".pdf,.jpg,.jpeg,.png" required>
      </div>
    </div>
    <button class="btn" type="submit">⬆ Enviar documento</button>
  </form>

  <table class="data">
    <tr><th>Tipo</th><th>Ficheiro</th><th>Tamanho</th><th>Enviado em</th><th>Estado</th><th>Observação</th><th></th></tr>
    <?php foreach ($documentos as $d): ?>
      <tr>
        <td><?= e($d['tipo_documento']) ?></td>
        <td><a href="<?= e(caminhoPublicoDocumento($d['nome_ficheiro'])) ?>" target="_blank" rel="noopener">📄 <?= e($d['nome_original']) ?></a></td>
        <td><?= formatarTamanhoFicheiro((int) $d['tamanho']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($d['enviado_em'])) ?></td>
        <td><span class="badge <?= $d['estado'] === 'Aprovado' ? 'ok' : ($d['estado'] === 'Rejeitado' ? 'bad' : '') ?>" style="<?= $d['estado'] === 'Pendente' ? 'background:var(--ul-blue-light);color:var(--ul-blue-dark);' : '' ?>"><?= e($d['estado']) ?></span></td>
        <td style="font-size:11.5px;color:var(--muted);"><?= e($d['observacao'] ?? '—') ?></td>
        <td>
          <?php if ($d['estado'] === 'Pendente'): ?>
            <form method="post" onsubmit="return confirm('Remover este documento?');">
              <input type="hidden" name="accao" value="apagar">
              <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
              <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#f3c6c2;" type="submit">Remover</button>
            </form>
          <?php else: ?>—<?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($documentos)): ?><tr><td colspan="7">Ainda não enviou nenhum documento.</td></tr><?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

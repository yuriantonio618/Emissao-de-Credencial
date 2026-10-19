<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;
$cursos = $pdo->query('SELECT * FROM cursos ORDER BY nome')->fetchAll();
$estudantes = $pdo->query('SELECT id, numero_estudante, nome FROM estudantes ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accao = $_POST['accao'] ?? '';

    if ($accao === 'criar') {
        $titulo   = trim($_POST['titulo'] ?? '');
        $mensagemTexto = trim($_POST['mensagem'] ?? '');
        $tipo     = in_array($_POST['tipo'] ?? '', ['Informação', 'Aviso', 'Urgente'], true) ? $_POST['tipo'] : 'Informação';
        $alvoTipo = in_array($_POST['alvo_tipo'] ?? '', ['Todos', 'Curso', 'Estudante'], true) ? $_POST['alvo_tipo'] : 'Todos';
        $alvoId = null;
        if ($alvoTipo === 'Curso') {
            $alvoId = (int) ($_POST['alvo_id_curso'] ?? 0);
        } elseif ($alvoTipo === 'Estudante') {
            $alvoId = (int) ($_POST['alvo_id_estudante'] ?? 0);
        }

        if ($titulo === '' || $mensagemTexto === '') {
            $mensagem = null;
        } else {
            $stmt = $pdo->prepare('INSERT INTO notificacoes (titulo, mensagem, tipo, alvo_tipo, alvo_id) VALUES (?,?,?,?,?)');
            $stmt->execute([$titulo, $mensagemTexto, $tipo, $alvoTipo, $alvoId]);
            $mensagem = 'Notificação publicada com sucesso.';
        }
    } elseif ($accao === 'apagar') {
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM notificacoes WHERE id = ?')->execute([$id]);
        $mensagem = 'Notificação removida.';
    }
}

$notificacoes = $pdo->query(
    "SELECT n.*, c.nome AS curso_nome, e.nome AS estudante_nome,
            (SELECT COUNT(*) FROM notificacoes_leituras l WHERE l.notificacao_id = n.id) AS total_lidas
     FROM notificacoes n
     LEFT JOIN cursos c ON n.alvo_tipo = 'Curso' AND c.id = n.alvo_id
     LEFT JOIN estudantes e ON n.alvo_tipo = 'Estudante' AND e.id = n.alvo_id
     ORDER BY n.criado_em DESC"
)->fetchAll();

$menuTitulo = 'Notificações';
$paginaActivaAdmin = 'notificacoes';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1050px;">
  <h2>🔔 Gestão de Notificações</h2>
  <p class="sub">Publique avisos para todos os estudantes, para um curso específico ou para um estudante individual</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>

  <form method="post" style="margin-bottom:28px;border:1px solid var(--line);border-radius:12px;padding:18px 20px;">
    <input type="hidden" name="accao" value="criar">
    <div class="form-grid">
      <div class="full"><label>Título</label><input type="text" name="titulo" required></div>
      <div class="full"><label>Mensagem</label><textarea name="mensagem" rows="3" required></textarea></div>
      <div>
        <label>Tipo</label>
        <select name="tipo">
          <option>Informação</option><option>Aviso</option><option>Urgente</option>
        </select>
      </div>
      <div>
        <label>Destinatário</label>
        <select name="alvo_tipo" onchange="document.getElementById('alvoCurso').style.display=this.value==='Curso'?'block':'none';document.getElementById('alvoEstudante').style.display=this.value==='Estudante'?'block':'none';">
          <option value="Todos">Todos os estudantes</option>
          <option value="Curso">Um curso específico</option>
          <option value="Estudante">Um estudante específico</option>
        </select>
      </div>
      <div id="alvoCurso" style="display:none;">
        <label>Curso</label>
        <select name="alvo_id_curso">
          <?php foreach ($cursos as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['nome']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div id="alvoEstudante" style="display:none;">
        <label>Estudante</label>
        <select name="alvo_id_estudante">
          <?php foreach ($estudantes as $s): ?><option value="<?= (int) $s['id'] ?>"><?= e($s['numero_estudante']) ?> — <?= e($s['nome']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <button class="btn" type="submit">📣 Publicar notificação</button>
  </form>

  <table class="data">
    <tr><th>Título</th><th>Tipo</th><th>Destinatário</th><th>Leituras</th><th>Publicada em</th><th></th></tr>
    <?php foreach ($notificacoes as $n): ?>
      <?php
        $destino = 'Todos os estudantes';
        if ($n['alvo_tipo'] === 'Curso') $destino = 'Curso: ' . $n['curso_nome'];
        if ($n['alvo_tipo'] === 'Estudante') $destino = 'Estudante: ' . $n['estudante_nome'];
      ?>
      <tr>
        <td><?= e($n['titulo']) ?></td>
        <td><?= e($n['tipo']) ?></td>
        <td><?= e($destino) ?></td>
        <td><?= (int) $n['total_lidas'] ?></td>
        <td><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Remover esta notificação?');">
            <input type="hidden" name="accao" value="apagar">
            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#f3c6c2;" type="submit">Remover</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($notificacoes)): ?><tr><td colspan="6">Nenhuma notificação publicada.</td></tr><?php endif; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

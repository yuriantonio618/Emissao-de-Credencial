<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;
$erro = null;

$cursos = $pdo->query('SELECT * FROM cursos ORDER BY nome')->fetchAll();
$cursoFiltro = (int) ($_GET['curso_id'] ?? ($cursos[0]['id'] ?? 0));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accao = $_POST['accao'] ?? '';

    if ($accao === 'criar') {
        $cursoId = (int) $_POST['curso_id'];
        $ano     = max(1, min(6, (int) $_POST['ano']));
        $nome    = trim($_POST['nome'] ?? '');
        $creditos = max(1, min(20, (int) ($_POST['creditos'] ?? 5)));

        if ($nome === '') {
            $erro = 'Indique o nome da disciplina.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO disciplinas (curso_id, ano, nome, creditos) VALUES (?,?,?,?)');
            $stmt->execute([$cursoId, $ano, $nome, $creditos]);
            $mensagem = 'Disciplina adicionada com sucesso.';
            $cursoFiltro = $cursoId;
        }
    } elseif ($accao === 'apagar') {
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM notas WHERE disciplina_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM disciplinas WHERE id = ?')->execute([$id]);
        $mensagem = 'Disciplina removida com sucesso (e respectivas notas associadas).';
        $cursoFiltro = (int) ($_POST['curso_id_actual'] ?? $cursoFiltro);
    }
}

$stmt = $pdo->prepare('SELECT * FROM disciplinas WHERE curso_id = ? ORDER BY ano, nome');
$stmt->execute([$cursoFiltro]);
$disciplinas = $stmt->fetchAll();
$porAno = [];
foreach ($disciplinas as $d) $porAno[$d['ano']][] = $d;
ksort($porAno);

$menuTitulo = 'Gestão de Disciplinas';
$paginaActivaAdmin = 'disciplinas';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1000px;">
  <h2>📚 Gestão de Disciplinas</h2>
  <p class="sub">Plano curricular por curso e ano de frequência</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>
  <?php if ($erro): ?><div class="result-box bad"><h3>✖ <?= e($erro) ?></h3></div><?php endif; ?>

  <form method="get" class="pill-row" style="margin-bottom:6px;">
    <label style="font-size:12.5px;font-weight:600;color:var(--muted);align-self:center;">Curso:</label>
    <select name="curso_id" onchange="this.form.submit()" style="padding:8px 12px;border:1.5px solid var(--line);border-radius:9px;">
      <?php foreach ($cursos as $c): ?>
        <option value="<?= (int) $c['id'] ?>" <?= $cursoFiltro === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>

  <div class="pill-row" style="align-items:center;">
    <label style="font-size:12.5px;font-weight:600;color:var(--muted);">Exportar pauta (PDF) do ano:</label>
    <?php for ($a = 1; $a <= 4; $a++): ?>
      <a class="btn btn-outline btn-sm" target="_blank" href="<?= BASE_URL ?>/admin/exportar_pauta.php?curso_id=<?= (int) $cursoFiltro ?>&ano=<?= $a ?>"><?= $a ?>º Ano</a>
    <?php endfor; ?>
  </div>

  <form method="post" style="margin:20px 0 26px;">
    <input type="hidden" name="accao" value="criar">
    <h3 style="font-size:14px;color:var(--ul-blue-dark);">Adicionar disciplina</h3>
    <div class="form-grid">
      <div><label>Curso</label>
        <select name="curso_id">
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $cursoFiltro === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Ano</label>
        <select name="ano"><?php for ($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>º Ano</option><?php endfor; ?></select>
      </div>
      <div class="full"><label>Nome da disciplina</label><input type="text" name="nome" required></div>
      <div><label>Créditos</label><input type="number" name="creditos" value="5" min="1" max="20"></div>
    </div>
    <button class="btn" type="submit">+ Adicionar disciplina</button>
  </form>

  <?php foreach ($porAno as $ano => $lista): ?>
    <div class="year-block">
      <h3><?= (int) $ano ?>º Ano</h3>
      <table class="data">
        <tr><th>Disciplina</th><th>Créditos</th><th></th></tr>
        <?php foreach ($lista as $d): ?>
          <tr>
            <td><?= e($d['nome']) ?></td>
            <td><?= (int) $d['creditos'] ?></td>
            <td>
              <form method="post" onsubmit="return confirm('Remover esta disciplina? As notas associadas também serão apagadas.');">
                <input type="hidden" name="accao" value="apagar">
                <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                <input type="hidden" name="curso_id_actual" value="<?= (int) $cursoFiltro ?>">
                <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#f3c6c2;" type="submit">Remover</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </table>
    </div>
  <?php endforeach; ?>
  <?php if (empty($porAno)): ?><p>Este curso ainda não tem disciplinas registadas.</p><?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

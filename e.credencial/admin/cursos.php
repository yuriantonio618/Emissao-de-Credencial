<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;
$erro = null;

// --- Processamento de acções ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accao = $_POST['accao'] ?? '';

    if ($accao === 'criar' || $accao === 'editar') {
        $nome         = trim($_POST['nome'] ?? '');
        $faculdade    = trim($_POST['faculdade'] ?? '');
        $departamento = trim($_POST['departamento'] ?? '');
        $duracao      = max(1, min(6, (int) ($_POST['duracao_anos'] ?? 4)));

        if ($nome === '' || $faculdade === '' || $departamento === '') {
            $erro = 'Preencha todos os campos obrigatórios.';
        } elseif ($accao === 'criar') {
            $stmt = $pdo->prepare('INSERT INTO cursos (nome, faculdade, departamento, duracao_anos) VALUES (?,?,?,?)');
            $stmt->execute([$nome, $faculdade, $departamento, $duracao]);
            $mensagem = 'Curso criado com sucesso.';
        } else {
            $id = (int) $_POST['id'];
            $stmt = $pdo->prepare('UPDATE cursos SET nome=?, faculdade=?, departamento=?, duracao_anos=? WHERE id=?');
            $stmt->execute([$nome, $faculdade, $departamento, $duracao, $id]);
            $mensagem = 'Curso actualizado com sucesso.';
        }
    } elseif ($accao === 'apagar') {
        $id = (int) $_POST['id'];
        $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM estudantes WHERE curso_id = ?');
        $stmtCount->execute([$id]);
        if ((int) $stmtCount->fetchColumn() > 0) {
            $erro = 'Não é possível remover: existem estudantes associados a este curso.';
        } else {
            $pdo->prepare('DELETE FROM disciplinas WHERE curso_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM cursos WHERE id = ?')->execute([$id]);
            $mensagem = 'Curso removido com sucesso.';
        }
    }
}

$cursos = $pdo->query(
    'SELECT c.*, COUNT(e.id) AS total_estudantes
     FROM cursos c LEFT JOIN estudantes e ON e.curso_id = c.id
     GROUP BY c.id ORDER BY c.nome'
)->fetchAll();

$emEdicao = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM cursos WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $emEdicao = $stmt->fetch();
}

$menuTitulo = 'Gestão de Cursos';
$paginaActivaAdmin = 'cursos';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1000px;">
  <h2>🏛️ Gestão de Cursos</h2>
  <p class="sub">Faculdades, departamentos e cursos oferecidos pela instituição</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>
  <?php if ($erro): ?><div class="result-box bad"><h3>✖ <?= e($erro) ?></h3></div><?php endif; ?>

  <form method="post" style="margin-bottom:26px;">
    <input type="hidden" name="accao" value="<?= $emEdicao ? 'editar' : 'criar' ?>">
    <?php if ($emEdicao): ?><input type="hidden" name="id" value="<?= (int) $emEdicao['id'] ?>"><?php endif; ?>
    <h3 style="font-size:14px;color:var(--ul-blue-dark);margin-top:0;">
      <?= $emEdicao ? 'A editar: ' . e($emEdicao['nome']) : 'Adicionar novo curso' ?>
    </h3>
    <div class="form-grid">
      <div class="full"><label>Nome do curso</label><input type="text" name="nome" required value="<?= e($emEdicao['nome'] ?? '') ?>"></div>
      <div><label>Faculdade</label><input type="text" name="faculdade" required value="<?= e($emEdicao['faculdade'] ?? 'Faculdade de Ciências e Tecnologia') ?>"></div>
      <div><label>Departamento</label><input type="text" name="departamento" required value="<?= e($emEdicao['departamento'] ?? '') ?>"></div>
      <div><label>Duração (anos)</label>
        <select name="duracao_anos">
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <option value="<?= $i ?>" <?= ($emEdicao && (int) $emEdicao['duracao_anos'] === $i) || (!$emEdicao && $i === 4) ? 'selected' : '' ?>><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
    <button class="btn" type="submit"><?= $emEdicao ? 'Guardar alterações' : '+ Criar curso' ?></button>
    <?php if ($emEdicao): ?><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/cursos.php">Cancelar</a><?php endif; ?>
  </form>

  <table class="data">
    <tr><th>Curso</th><th>Faculdade</th><th>Departamento</th><th>Duração</th><th>Estudantes</th><th></th></tr>
    <?php foreach ($cursos as $c): ?>
      <tr>
        <td><?= e($c['nome']) ?></td>
        <td><?= e($c['faculdade']) ?></td>
        <td><?= e($c['departamento']) ?></td>
        <td><?= (int) $c['duracao_anos'] ?> anos</td>
        <td><?= (int) $c['total_estudantes'] ?></td>
        <td style="white-space:nowrap;">
          <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/cursos.php?editar=<?= (int) $c['id'] ?>">Editar</a>
          <form method="post" style="display:inline;" onsubmit="return confirm('Remover este curso? Esta acção não pode ser revertida.');">
            <input type="hidden" name="accao" value="apagar">
            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
            <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#f3c6c2;" type="submit">Remover</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

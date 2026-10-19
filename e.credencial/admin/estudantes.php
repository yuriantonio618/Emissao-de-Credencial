<?php
require_once __DIR__ . '/../includes/auth.php';
exigirAdmin();

$pdo = getPDO();
$mensagem = null;
$erro = null;
$cursos = $pdo->query('SELECT * FROM cursos ORDER BY nome')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accao = $_POST['accao'] ?? '';

    if ($accao === 'criar' || $accao === 'editar') {
        $dados = [
            'numero_estudante' => trim($_POST['numero_estudante'] ?? ''),
            'nome'             => trim($_POST['nome'] ?? ''),
            'nome_pai'         => trim($_POST['nome_pai'] ?? ''),
            'nome_mae'         => trim($_POST['nome_mae'] ?? ''),
            'bi'               => trim($_POST['bi'] ?? ''),
            'bi_emissao'       => trim($_POST['bi_emissao'] ?? ''),
            'contacto'         => trim($_POST['contacto'] ?? ''),
            'email'            => trim($_POST['email'] ?? ''),
            'curso_id'         => (int) $_POST['curso_id'],
            'ano_frequencia'   => max(1, min(6, (int) $_POST['ano_frequencia'])),
            'regime'           => $_POST['regime'] === 'Pós-laboral' ? 'Pós-laboral' : 'Laboral',
            'sexo'             => $_POST['sexo'] === 'Feminino' ? 'Feminino' : 'Masculino',
            'idade'            => max(15, min(70, (int) $_POST['idade'])),
            'situacao_financeira' => $_POST['situacao_financeira'] === 'Em dívida' ? 'Em dívida' : 'Regularizada',
        ];

        if ($dados['numero_estudante'] === '' || $dados['nome'] === '') {
            $erro = 'O número de estudante e o nome são obrigatórios.';
        } elseif ($accao === 'criar') {
            $existe = $pdo->prepare('SELECT COUNT(*) FROM estudantes WHERE numero_estudante = ?');
            $existe->execute([$dados['numero_estudante']]);
            if ((int) $existe->fetchColumn() > 0) {
                $erro = 'Já existe um estudante com esse número.';
            } else {
                $senhaInicial = 'estagio123';
                $stmt = $pdo->prepare(
                    'INSERT INTO estudantes
                     (numero_estudante, senha_hash, nome, nome_pai, nome_mae, bi, bi_emissao, contacto, email,
                      curso_id, ano_frequencia, regime, sexo, idade, situacao_financeira)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $dados['numero_estudante'], password_hash($senhaInicial, PASSWORD_DEFAULT), $dados['nome'],
                    $dados['nome_pai'], $dados['nome_mae'], $dados['bi'], $dados['bi_emissao'], $dados['contacto'],
                    $dados['email'], $dados['curso_id'], $dados['ano_frequencia'], $dados['regime'], $dados['sexo'],
                    $dados['idade'], $dados['situacao_financeira'],
                ]);
                $mensagem = 'Estudante criado com sucesso. Senha inicial: ' . $senhaInicial;
            }
        } else {
            $id = (int) $_POST['id'];
            $stmt = $pdo->prepare(
                'UPDATE estudantes SET numero_estudante=?, nome=?, nome_pai=?, nome_mae=?, bi=?, bi_emissao=?,
                 contacto=?, email=?, curso_id=?, ano_frequencia=?, regime=?, sexo=?, idade=?, situacao_financeira=?
                 WHERE id=?'
            );
            $stmt->execute([
                $dados['numero_estudante'], $dados['nome'], $dados['nome_pai'], $dados['nome_mae'], $dados['bi'],
                $dados['bi_emissao'], $dados['contacto'], $dados['email'], $dados['curso_id'], $dados['ano_frequencia'],
                $dados['regime'], $dados['sexo'], $dados['idade'], $dados['situacao_financeira'], $id,
            ]);
            $mensagem = 'Dados do estudante actualizados com sucesso.';
        }
    } elseif ($accao === 'apagar') {
        $id = (int) $_POST['id'];
        $pdo->prepare('DELETE FROM estudantes WHERE id = ?')->execute([$id]);
        $mensagem = 'Estudante removido com sucesso.';
    } elseif ($accao === 'reset_senha') {
        $id = (int) $_POST['id'];
        $stmt = $pdo->prepare('UPDATE estudantes SET senha_hash = ? WHERE id = ?');
        $stmt->execute([password_hash('estagio123', PASSWORD_DEFAULT), $id]);
        $mensagem = 'Senha reposta para o valor por omissão: estagio123';
    }
}

$busca = trim($_GET['q'] ?? '');
$sql = 'SELECT e.*, c.nome AS curso_nome FROM estudantes e JOIN cursos c ON c.id = e.curso_id';
$params = [];
if ($busca !== '') {
    $sql .= ' WHERE e.nome LIKE ? OR e.numero_estudante LIKE ?';
    $params = ["%$busca%", "%$busca%"];
}
$sql .= ' ORDER BY c.nome, e.nome';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$estudantes = $stmt->fetchAll();

$emEdicao = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare('SELECT * FROM estudantes WHERE id = ?');
    $stmt->execute([(int) $_GET['editar']]);
    $emEdicao = $stmt->fetch();
}

$menuTitulo = 'Gestão de Estudantes';
$paginaActivaAdmin = 'estudantes';
require_once __DIR__ . '/../includes/header_admin.php';
?>

<div class="panel" style="max-width:1150px;">
  <h2>🧑‍🎓 Gestão de Estudantes</h2>
  <p class="sub">Criar, editar e remover contas de estudantes</p>

  <?php if ($mensagem): ?><div class="result-box ok"><h3>✔ <?= e($mensagem) ?></h3></div><?php endif; ?>
  <?php if ($erro): ?><div class="result-box bad"><h3>✖ <?= e($erro) ?></h3></div><?php endif; ?>

  <form method="post" style="margin-bottom:28px;border:1px solid var(--line);border-radius:12px;padding:18px 20px;">
    <input type="hidden" name="accao" value="<?= $emEdicao ? 'editar' : 'criar' ?>">
    <?php if ($emEdicao): ?><input type="hidden" name="id" value="<?= (int) $emEdicao['id'] ?>"><?php endif; ?>
    <h3 style="font-size:14px;color:var(--ul-blue-dark);margin-top:0;">
      <?= $emEdicao ? 'A editar: ' . e($emEdicao['nome']) : 'Adicionar novo estudante' ?>
    </h3>
    <div class="form-grid">
      <div><label>Número de estudante</label><input type="text" name="numero_estudante" required value="<?= e($emEdicao['numero_estudante'] ?? '') ?>" <?= $emEdicao ? '' : 'placeholder="ex: 04.0099.2026"' ?>></div>
      <div><label>Nome completo</label><input type="text" name="nome" required value="<?= e($emEdicao['nome'] ?? '') ?>"></div>
      <div><label>Nome do pai</label><input type="text" name="nome_pai" value="<?= e($emEdicao['nome_pai'] ?? '') ?>"></div>
      <div><label>Nome da mãe</label><input type="text" name="nome_mae" value="<?= e($emEdicao['nome_mae'] ?? '') ?>"></div>
      <div><label>Bilhete de Identidade</label><input type="text" name="bi" value="<?= e($emEdicao['bi'] ?? '') ?>"></div>
      <div><label>BI emitido (data — local)</label><input type="text" name="bi_emissao" value="<?= e($emEdicao['bi_emissao'] ?? '') ?>"></div>
      <div><label>Contacto</label><input type="text" name="contacto" value="<?= e($emEdicao['contacto'] ?? '') ?>"></div>
      <div><label>E-mail</label><input type="email" name="email" value="<?= e($emEdicao['email'] ?? '') ?>"></div>
      <div><label>Curso</label>
        <select name="curso_id">
          <?php foreach ($cursos as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= ($emEdicao && (int) $emEdicao['curso_id'] === (int) $c['id']) ? 'selected' : '' ?>><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Ano de frequência</label>
        <select name="ano_frequencia">
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <option value="<?= $i ?>" <?= ($emEdicao && (int) $emEdicao['ano_frequencia'] === $i) ? 'selected' : '' ?>><?= $i ?>º Ano</option>
          <?php endfor; ?>
        </select>
      </div>
      <div><label>Regime</label>
        <select name="regime">
          <option <?= ($emEdicao['regime'] ?? '') === 'Laboral' ? 'selected' : '' ?>>Laboral</option>
          <option <?= ($emEdicao['regime'] ?? '') === 'Pós-laboral' ? 'selected' : '' ?>>Pós-laboral</option>
        </select>
      </div>
      <div><label>Sexo</label>
        <select name="sexo">
          <option <?= ($emEdicao['sexo'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
          <option <?= ($emEdicao['sexo'] ?? '') === 'Feminino' ? 'selected' : '' ?>>Feminino</option>
        </select>
      </div>
      <div><label>Idade</label><input type="number" name="idade" min="15" max="70" value="<?= (int) ($emEdicao['idade'] ?? 20) ?>"></div>
      <div><label>Situação financeira</label>
        <select name="situacao_financeira">
          <option <?= ($emEdicao['situacao_financeira'] ?? '') === 'Regularizada' ? 'selected' : '' ?>>Regularizada</option>
          <option <?= ($emEdicao['situacao_financeira'] ?? '') === 'Em dívida' ? 'selected' : '' ?>>Em dívida</option>
        </select>
      </div>
    </div>
    <button class="btn" type="submit"><?= $emEdicao ? 'Guardar alterações' : '+ Criar estudante (senha inicial: estagio123)' ?></button>
    <?php if ($emEdicao): ?><a class="btn btn-outline" href="<?= BASE_URL ?>/admin/estudantes.php">Cancelar</a><?php endif; ?>
  </form>

  <form method="get" style="margin-bottom:14px;display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <input type="text" name="q" placeholder="Pesquisar por nome ou número..." value="<?= e($busca) ?>"
           style="padding:9px 12px;border:1.5px solid var(--line);border-radius:9px;width:280px;font-size:13px;">
    <button class="btn btn-outline btn-sm" type="submit">Pesquisar</button>
    <a class="btn btn-gold btn-sm" href="<?= BASE_URL ?>/admin/exportar_estudantes.php">⬇ Exportar lista (Excel/CSV)</a>
  </form>

  <div style="overflow-x:auto;">
    <table class="data">
      <tr><th>Nº Estudante</th><th>Nome</th><th>Curso</th><th>Ano</th><th>Regime</th><th>Financeiro</th><th></th></tr>
      <?php foreach ($estudantes as $s): ?>
        <tr>
          <td><?= e($s['numero_estudante']) ?></td>
          <td><?= e($s['nome']) ?></td>
          <td><?= e($s['curso_nome']) ?></td>
          <td><?= (int) $s['ano_frequencia'] ?>º</td>
          <td><?= e($s['regime']) ?></td>
          <td><span class="badge <?= $s['situacao_financeira'] === 'Regularizada' ? 'ok' : 'bad' ?>"><?= e($s['situacao_financeira']) ?></span></td>
          <td style="white-space:nowrap;">
            <a class="btn btn-outline btn-sm" href="<?= BASE_URL ?>/admin/estudantes.php?editar=<?= (int) $s['id'] ?>">Editar</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Repor a senha deste estudante para estagio123?');">
              <input type="hidden" name="accao" value="reset_senha">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn btn-outline btn-sm" type="submit">Repor senha</button>
            </form>
            <form method="post" style="display:inline;" onsubmit="return confirm('Remover definitivamente este estudante e todos os seus dados (notas, financeiro, pedidos)?');">
              <input type="hidden" name="accao" value="apagar">
              <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
              <button class="btn btn-outline btn-sm" style="color:var(--danger);border-color:#f3c6c2;" type="submit">Remover</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($estudantes)): ?><tr><td colspan="7">Nenhum estudante encontrado.</td></tr><?php endif; ?>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
require_once __DIR__ . '/../includes/auth.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT dia_semana, hora_inicio, hora_fim, disciplina, sala
     FROM horarios WHERE curso_id = ? AND ano = ?
     ORDER BY hora_inicio'
);
$stmt->execute([$est['curso_id'], $est['ano_frequencia']]);
$aulas = $stmt->fetchAll();

$dias = ['Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta'];
$blocos = [];
foreach ($aulas as $a) {
    $chave = substr($a['hora_inicio'], 0, 5) . '–' . substr($a['hora_fim'], 0, 5);
    $blocos[$chave][$a['dia_semana']][] = $a;
}
ksort($blocos);

$menuTitulo = 'Horários';
$paginaActiva = 'horarios';
require_once __DIR__ . '/../includes/header_estudante.php';
?>

<div class="panel">
  <h2>🕒 Horários</h2>
  <p class="sub">Horário semanal — <?= e($est['curso_nome']) ?>, <?= (int) $est['ano_frequencia'] ?>º ano</p>

  <?php if (empty($blocos)): ?>
    <p>O horário para esta turma ainda não foi publicado.</p>
  <?php else: ?>
    <table class="timetable">
      <tr>
        <th style="width:110px;">Hora</th>
        <?php foreach ($dias as $d): ?><th><?= e($d) ?></th><?php endforeach; ?>
      </tr>
      <?php foreach ($blocos as $hora => $porDia): ?>
        <tr>
          <td style="font-weight:600;color:var(--ul-blue-dark);"><?= e($hora) ?></td>
          <?php foreach ($dias as $d): ?>
            <td>
              <?php if (!empty($porDia[$d])): foreach ($porDia[$d] as $aula): ?>
                <div class="slot">
                  <?= e($aula['disciplina']) ?>
                  <div class="room"><?= e($aula['sala'] ?? '') ?></div>
                </div>
              <?php endforeach; endif; ?>
            </td>
          <?php endforeach; ?>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

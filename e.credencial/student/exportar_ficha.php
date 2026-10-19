<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/RelatorioPDF.php';
exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$stmt = $pdo->prepare(
    'SELECT d.ano, d.nome, n.nota_frequencia, n.nota_exame, n.nota_final
     FROM notas n JOIN disciplinas d ON d.id = n.disciplina_id
     WHERE n.estudante_id = ? ORDER BY d.ano, d.nome'
);
$stmt->execute([$est['id']]);
$notas = $stmt->fetchAll();

$porAno = [];
foreach ($notas as $n) $porAno[$n['ano']][] = $n;
ksort($porAno);

registarEvento('estudante', (int) $est['id'], 'Exportou a ficha académica em PDF');

$pdf = gerarFichaAcademicaPDF($est, $porAno);
$pdf->Output('D', 'Ficha_Academica_' . preg_replace('/\s+/', '_', $est['nome']) . '.pdf');

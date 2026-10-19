<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/RelatorioPDF.php';
exigirAdmin();

$pdo = getPDO();

$cursoId = (int) ($_GET['curso_id'] ?? 0);
$ano     = (int) ($_GET['ano'] ?? 1);

$stmtCurso = $pdo->prepare('SELECT * FROM cursos WHERE id = ?');
$stmtCurso->execute([$cursoId]);
$curso = $stmtCurso->fetch();

if (!$curso) {
    http_response_code(404);
    die('Curso não encontrado.');
}

$stmt = $pdo->prepare(
    "SELECT e.numero_estudante, e.nome, d.nome AS disciplina,
            n.nota_frequencia AS frequencia, n.nota_exame AS exame, n.nota_final AS final
     FROM estudantes e
     JOIN disciplinas d ON d.curso_id = e.curso_id AND d.ano = ?
     LEFT JOIN notas n ON n.estudante_id = e.id AND n.disciplina_id = d.id
     WHERE e.curso_id = ? AND e.ano_frequencia = ?
     ORDER BY e.nome, d.nome"
);
$stmt->execute([$ano, $cursoId, $ano]);
$linhas = $stmt->fetchAll();

registarEvento('admin', (int) $_SESSION['user_id'], 'Exportou a pauta em PDF (' . $curso['nome'] . ', ' . $ano . 'º ano)');

$pdf = gerarPautaPDF($curso['nome'], $ano, $linhas);
$pdf->Output('D', 'Pauta_' . preg_replace('/\s+/', '_', $curso['nome']) . '_' . $ano . 'ano.pdf');

<?php
/**
 * SIGEUL — Verificação de elegibilidade para Estágio Profissional
 * Regra de negócio: o estudante tem de ter aprovado TODAS as
 * disciplinas do 1º ao 3º ano do seu curso.
 */

function verificarElegibilidadeEstagio(PDO $pdo, int $estudanteId, int $cursoId): array
{
    $stmt = $pdo->prepare(
        'SELECT d.id, d.ano, d.nome, n.nota_final
         FROM disciplinas d
         LEFT JOIN notas n ON n.disciplina_id = d.id AND n.estudante_id = ?
         WHERE d.curso_id = ? AND d.ano <= 3
         ORDER BY d.ano, d.nome'
    );
    $stmt->execute([$estudanteId, $cursoId]);
    $disciplinas = $stmt->fetchAll();

    $pendentes = [];
    foreach ($disciplinas as $d) {
        $temNota  = $d['nota_final'] !== null;
        $aprovado = $temNota && (float) $d['nota_final'] >= 10;
        if (!$aprovado) {
            $pendentes[] = $d['ano'] . 'º Ano — ' . $d['nome'] . ($temNota ? ' (nota ' . number_format((float) $d['nota_final'], 1) . ')' : ' (sem nota lançada)');
        }
    }

    return [
        'elegivel'  => empty($pendentes),
        'pendentes' => $pendentes,
    ];
}

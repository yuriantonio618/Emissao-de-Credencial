<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csv_export.php';
exigirAdmin();

$pdo = getPDO();
$stmt = $pdo->query(
    'SELECT e.numero_estudante, e.nome, c.nome AS curso, e.ano_frequencia, e.regime, e.sexo, e.idade,
            e.situacao_financeira, e.contacto, e.email
     FROM estudantes e JOIN cursos c ON c.id = e.curso_id
     ORDER BY c.nome, e.nome'
);
$dados = $stmt->fetchAll();

$linhas = array_map(fn($s) => [
    $s['numero_estudante'], $s['nome'], $s['curso'], $s['ano_frequencia'] . 'º Ano',
    $s['regime'], $s['sexo'], $s['idade'], $s['situacao_financeira'], $s['contacto'], $s['email'],
], $dados);

registarEvento('admin', (int) $_SESSION['user_id'], 'Exportou a lista de estudantes (CSV)');

exportarCSV(
    'estudantes_sigeul_' . date('Ymd_His') . '.csv',
    ['Nº Estudante', 'Nome', 'Curso', 'Ano', 'Regime', 'Sexo', 'Idade', 'Situação Financeira', 'Contacto', 'E-mail'],
    $linhas
);

<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csv_export.php';
exigirAdmin();

$pdo = getPDO();
$stmt = $pdo->query(
    "SELECT e.numero_estudante, e.nome AS estudante, c.nome AS curso, p.local_estagio, p.sector, p.periodo,
            p.data_inicio, p.estado, p.criado_em
     FROM pedidos_estagio p
     JOIN estudantes e ON e.id = p.estudante_id
     JOIN cursos c ON c.id = e.curso_id
     ORDER BY p.criado_em DESC"
);
$dados = $stmt->fetchAll();

$linhas = array_map(fn($p) => [
    $p['numero_estudante'], $p['estudante'], $p['curso'], $p['local_estagio'], $p['sector'] ?? '', $p['periodo'],
    formatarDataPt($p['data_inicio']), $p['estado'], date('d/m/Y H:i', strtotime($p['criado_em'])),
], $dados);

registarEvento('admin', (int) $_SESSION['user_id'], 'Exportou a lista de pedidos de estágio (CSV)');

exportarCSV(
    'pedidos_estagio_sigeul_' . date('Ymd_His') . '.csv',
    ['Nº Estudante', 'Estudante', 'Curso', 'Local de Estágio', 'Sector/Núcleo', 'Período', 'Data de Início', 'Estado', 'Data do Pedido'],
    $linhas
);

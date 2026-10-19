<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/CredencialPDF.php';

exigirEstudante();

$est = estudanteActual();
$pdo = getPDO();

$pedidoId = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT * FROM pedidos_estagio WHERE id = ? AND estudante_id = ? AND estado = 'Aprovado'"
);
$stmt->execute([$pedidoId, $est['id']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(404);
    die('Pedido de credencial não encontrado ou não autorizado para descarregamento.');
}

registarEvento('estudante', (int) $est['id'], 'Descarregou a credencial de estágio em PDF (#' . $pedidoId . ')');

$pdf = gerarCredencialPDF($est, $pedido);
$nomeFicheiro = 'Credencial_Estagio_' . preg_replace('/\s+/', '_', $est['nome']) . '.pdf';

$pdf->Output('D', $nomeFicheiro);

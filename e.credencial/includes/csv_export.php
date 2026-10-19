<?php
/**
 * SIGEUL — Exportação de relatórios em CSV (compatível com Excel)
 */

function exportarCSV(string $nomeFicheiro, array $cabecalhos, array $linhas): never
{
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nomeFicheiro . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $saida = fopen('php://output', 'w');
    // BOM UTF-8 para o Excel reconhecer correctamente os acentos
    fwrite($saida, "\xEF\xBB\xBF");
    fputcsv($saida, $cabecalhos, ';');
    foreach ($linhas as $linha) {
        fputcsv($saida, $linha, ';');
    }
    fclose($saida);
    exit;
}

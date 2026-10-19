<?php
/**
 * SIGEUL — Relatórios em PDF (Pauta administrativa e Ficha Académica)
 * Reutiliza a biblioteca FPDF já vendorizada em /vendor/fpdf.
 */

require_once __DIR__ . '/../vendor/fpdf/fpdf.php';
require_once __DIR__ . '/CredencialPDF.php'; // fornece pdfTxt()

class RelatorioPDF extends FPDF
{
    public string $titulo = '';
    public string $subtitulo = '';

    public function Header(): void
    {
        $this->SetFont('Helvetica', 'B', 13);
        $this->SetTextColor(14, 59, 107);
        $this->Cell(0, 8, pdfTxt($this->titulo), 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 6, pdfTxt($this->subtitulo), 0, 1, 'C');
        $this->SetDrawColor(176, 141, 87);
        $this->SetLineWidth(0.5);
        $this->Line(10, 24, 287, 24);
        $this->Ln(6);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer(): void
    {
        $this->SetY(-15);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 6, pdfTxt('Página ' . $this->PageNo() . ' — Gerado pelo SIGEUL em ' . date('d/m/Y H:i')), 0, 0, 'C');
    }

    /** Cabeçalho de tabela com fundo azul institucional */
    public function cabecalhoTabela(array $colunas, array $larguras): void
    {
        $this->SetFillColor(234, 241, 251);
        $this->SetTextColor(14, 59, 107);
        $this->SetFont('Helvetica', 'B', 9);
        foreach ($colunas as $i => $col) {
            $this->Cell($larguras[$i], 8, pdfTxt($col), 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
        $this->SetFont('Helvetica', '', 9);
    }
}

/** Pauta (mapa de notas) de um curso/ano — usado pelo administrador */
function gerarPautaPDF(string $cursoNome, int $ano, array $linhas): RelatorioPDF
{
    $pdf = new RelatorioPDF('L', 'mm', 'A4');
    $pdf->titulo = 'Pauta de Notas — ' . $cursoNome;
    $pdf->subtitulo = $ano . 'º Ano — Ano lectivo 2025/2026';
    $pdf->AddPage();

    $colunas  = ['Nº Estudante', 'Nome', 'Disciplina', 'Frequência', 'Exame', 'Final', 'Situação'];
    $larguras = [30, 60, 90, 25, 25, 25, 25];
    $pdf->cabecalhoTabela($colunas, $larguras);

    foreach ($linhas as $l) {
        $pdf->Cell($larguras[0], 7, pdfTxt($l['numero_estudante']), 1);
        $pdf->Cell($larguras[1], 7, pdfTxt($l['nome']), 1);
        $pdf->Cell($larguras[2], 7, pdfTxt($l['disciplina']), 1);
        $pdf->Cell($larguras[3], 7, $l['frequencia'] !== null ? number_format((float) $l['frequencia'], 1) : '-', 1, 0, 'C');
        $pdf->Cell($larguras[4], 7, $l['exame'] !== null ? number_format((float) $l['exame'], 1) : '-', 1, 0, 'C');
        $pdf->Cell($larguras[5], 7, $l['final'] !== null ? number_format((float) $l['final'], 1) : '-', 1, 0, 'C');
        $pdf->Cell($larguras[6], 7, pdfTxt($l['final'] !== null ? ($l['final'] >= 10 ? 'Aprovado' : 'Reprovado') : 'Pendente'), 1, 1, 'C');
    }

    return $pdf;
}

/** Ficha académica (histórico de notas) de um estudante — usado pelo próprio estudante */
function gerarFichaAcademicaPDF(array $estudante, array $porAno): RelatorioPDF
{
    $pdf = new RelatorioPDF('P', 'mm', 'A4');
    $pdf->titulo = 'Ficha Académica';
    $pdf->subtitulo = $estudante['nome'] . ' — ' . $estudante['numero_estudante'];
    $pdf->AddPage();

    $pdf->SetFont('Helvetica', '', 10.5);
    $pdf->Cell(0, 7, pdfTxt('Curso: ' . $estudante['curso_nome']), 0, 1);
    $pdf->Cell(0, 7, pdfTxt('Regime: ' . $estudante['regime'] . '   |   Ano de frequência: ' . $estudante['ano_frequencia'] . 'º Ano'), 0, 1);
    $pdf->Ln(4);

    $colunas  = ['Disciplina', 'Frequência', 'Exame', 'Final', 'Situação'];
    $larguras = [95, 25, 25, 25, 20];

    foreach ($porAno as $ano => $lista) {
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetTextColor(27, 95, 168);
        $pdf->Cell(0, 8, pdfTxt($ano . 'º Ano'), 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->cabecalhoTabela($colunas, $larguras);
        foreach ($lista as $d) {
            $pdf->Cell($larguras[0], 7, pdfTxt($d['nome']), 1);
            $pdf->Cell($larguras[1], 7, $d['nota_frequencia'] !== null ? number_format((float) $d['nota_frequencia'], 1) : '-', 1, 0, 'C');
            $pdf->Cell($larguras[2], 7, $d['nota_exame'] !== null ? number_format((float) $d['nota_exame'], 1) : '-', 1, 0, 'C');
            $pdf->Cell($larguras[3], 7, $d['nota_final'] !== null ? number_format((float) $d['nota_final'], 1) : '-', 1, 0, 'C');
            $pdf->Cell($larguras[4], 7, pdfTxt($d['nota_final'] !== null ? ($d['nota_final'] >= 10 ? 'Aprov.' : 'Reprov.') : '-'), 1, 1, 'C');
        }
        $pdf->Ln(3);
    }

    return $pdf;
}

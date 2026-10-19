<?php
/**
 * SIGEUL — Gerador da Credencial de Estágio (PDF)
 * Usa a biblioteca FPDF (vendorizada em /vendor/fpdf) para produzir um
 * documento fiel ao modelo oficial da Universidade Licungo: logótipo
 * no cabeçalho, marca d'água de fundo e texto institucional em nome
 * da repartição do curso.
 */

require_once __DIR__ . '/../vendor/fpdf/fpdf.php';

/** FPDF usa CP1252/Latin-1 nas fontes base — converte a partir de UTF-8 (MySQL). */
function pdfTxt(?string $texto): string
{
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $texto ?? '');
}

class CredencialPDF extends FPDF
{
    public string $faculdade = '';
    public string $departamento = '';
    public string $curso = '';

    private string $logoPath;
    private string $marcaAguaPath;

    public function __construct()
    {
        parent::__construct('P', 'mm', 'A4');
        $this->logoPath      = __DIR__ . '/../assets/img/logo_ul.png';
        $this->marcaAguaPath = __DIR__ . '/../assets/img/logo_marca_agua.png';
    }

    public function Header(): void
    {
        if (is_file($this->logoPath)) {
            $this->Image($this->logoPath, 92, 10, 26, 26);
        }
        $this->SetY(38);
        $this->SetFont('Helvetica', 'B', 11.5);
        $this->SetTextColor(14, 59, 107);
        $this->Cell(0, 6, pdfTxt($this->faculdade), 0, 1, 'C');
        $this->SetFont('Helvetica', '', 10.5);
        $this->SetTextColor(60, 70, 87);
        $this->Cell(0, 6, pdfTxt($this->departamento), 0, 1, 'C');
        $this->Cell(0, 6, pdfTxt('Curso de ' . $this->curso), 0, 1, 'C');
        $this->SetTextColor(0, 0, 0);
        $this->Ln(6);
    }

    public function Footer(): void
    {
        $this->SetY(-18);
        $this->SetFont('Helvetica', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Cell(0, 6, pdfTxt('Documento gerado automaticamente pelo SIGEUL — ' . APP_INSTITUICAO), 0, 0, 'C');
    }

    /** Desenha a marca d'água institucional centrada no fundo da página, atrás do texto. */
    public function desenharMarcaAgua(): void
    {
        if (!is_file($this->marcaAguaPath)) return;
        $tamanho = 130;
        $x = (210 - $tamanho) / 2; // A4 largura = 210mm
        $y = 80;
        $this->Image($this->marcaAguaPath, $x, $y, $tamanho, $tamanho);
    }
}

/**
 * Constrói e devolve o objecto FPDF pronto a ser enviado (Output).
 *
 * @param array $estudante Registo completo do estudante (com curso_nome, faculdade, departamento)
 * @param array $pedido    Registo do pedido de estágio (local_estagio, sector, periodo, data_inicio)
 */
function gerarCredencialPDF(array $estudante, array $pedido): CredencialPDF
{
    $pdf = new CredencialPDF();
    $pdf->faculdade    = $estudante['faculdade'];
    $pdf->departamento = $estudante['departamento'];
    $pdf->curso        = $estudante['curso_nome'];
    $pdf->SetMargins(28, 15, 28);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();

    // Marca d'água por baixo do conteúdo (desenhada antes do texto que se segue)
    $pdf->desenharMarcaAgua();

    $pdf->Ln(6);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 8, pdfTxt('Exmo. Senhor Director'), 0, 1, 'L');
    $pdf->Ln(2);

    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Write(7, pdfTxt('Assunto: '));
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Write(7, pdfTxt('Pedido de Credencial'));
    $pdf->Ln(12);

    // Corpo do texto — redigido em nome da repartição do curso, seguindo
    // exactamente o modelo institucional oficial da Universidade Licungo.
    $sector = trim($pedido['sector'] ?? '');

    escreverParagrafoMisto($pdf, [
        ['t' => 'A repartição do curso acima citado, vem por meio deste, solicitar a V. Excia uma credencial em nome do estudante '],
        ['t' => $estudante['nome'] . ', ', 'b' => true],
        ['t' => 'do ' . (int) $estudante['ano_frequencia'] . 'º ano, regime ' . $estudante['regime'] . ' para efeito de estágio de ' . $pedido['periodo'] . ' no '],
        ['t' => $pedido['local_estagio'] . ($sector !== '' ? ',' : '.'), 'b' => true],
    ]);

    if ($sector !== '') {
        escreverParagrafoMisto($pdf, [
            ['t' => 'no sector do '],
            ['t' => $sector . '.', 'b' => true],
        ]);
    }

    $pdf->Ln(24);

    $pdf->SetFont('Helvetica', '', 11);
    $anoEmissao = date('Y', strtotime($pedido['criado_em'] ?? 'now'));
    $pdf->Cell(0, 8, pdfTxt('Dondo, ' . $anoEmissao), 0, 1, 'R');
    $pdf->Ln(26);

    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 6, pdfTxt('..........................................................................'), 0, 1, 'C');
    $pdf->Cell(0, 6, pdfTxt('O Chefe da Repartição'), 0, 1, 'C');

    return $pdf;
}

/**
 * Escreve um "parágrafo" composto por vários troços de texto (alguns a negrito),
 * quebrando linha automaticamente como um único bloco — usa Write() do FPDF,
 * que já trata a quebra automática de linha ao longo da largura útil da página.
 *
 * @param array<int, array{t: string, b?: bool}> $trocos
 */
function escreverParagrafoMisto(FPDF $pdf, array $trocos): void
{
    foreach ($trocos as $troco) {
        $pdf->SetFont('Helvetica', !empty($troco['b']) ? 'B' : '', 11.5);
        $pdf->Write(7, pdfTxt($troco['t']));
    }
    $pdf->Ln(9);
}

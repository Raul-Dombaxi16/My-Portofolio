
<?php
ini_set('display_errors', '0');
error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../fpdf19/fpdf.php';
require_once __DIR__ . '/conexao.php';

// ============================================
// 1. VALIDAR O ID DO ESTUDANTE
// ============================================

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    exit('Estudante não encontrado.');
}

$id = (int) $_GET['id'];

// Consulta preparada para maior segurança
$stmt = $conexao->prepare(
    "SELECT * FROM alunos WHERE id = ?"
);

if (!$stmt) {
    exit('Erro ao consultar os dados do estudante.');
}

$stmt->bind_param('i', $id);
$stmt->execute();

$resultado = $stmt->get_result();
$estudante = $resultado->fetch_assoc();

$stmt->close();

if (!$estudante) {
    exit('Estudante não encontrado.');
}

// ============================================
// 2. CONFIGURAÇÕES DA ESCOLA
// ============================================

$nome_escola = 'Instituto Médio Politécnico Privado  Quineus';
$sigla_escola = 'I.M.P.P.Q.';
$iban_escola = 'AO06 0040 0000 1234 5678 9012 3';

$caminho_logo = __DIR__ . '/../img/logo.png';

// Cores
define('AZUL', [24, 54, 82]);
define('AZUL_CLARO', [235, 241, 246]);
define('CINZA', [100, 110, 120]);
define('DOURADO', [210, 164, 55]);

// Conversão de caracteres para a versão antiga do FPDF
function textoPDF($texto)
{
    $texto = (string) $texto;

    if (function_exists('iconv')) {
        $resultado = iconv(
            'UTF-8',
            'windows-1252//TRANSLIT',
            $texto
        );

        if ($resultado !== false) {
            return $resultado;
        }
    }

    return utf8_decode($texto);
}

// ============================================
// 3. CLASSE PERSONALIZADA DO PDF
// ============================================

class FichaPDF extends FPDF
{
    public $nome_escola = '';
    public $sigla_escola = '';
    public $iban_escola = '';
    public $caminho_logo = '';

    // Cabeçalho
    function Header()
    {
        // Linha superior discreta
        $this->SetFillColor(24, 54, 82);
        $this->Rect(0, 0, 210, 3, 'F');

        // Logótipo original da escola
        if (is_file($this->caminho_logo)) {
            $this->Image(
                $this->caminho_logo,
                12, 9, 28
            );
        }

        // Nome da escola
        $this->SetXY(43, 12);
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(24, 54, 82);

        $this->Cell(
            153, 8,
            textoPDF($this->nome_escola),
            0, 1, 'C'
        );

        // Sigla
        $this->SetX(43);
        $this->SetFont('Arial', '', 10);
        $this->SetTextColor(100, 110, 120);

        $this->Cell(
            153, 6,
            textoPDF($this->sigla_escola),
            0, 1, 'C'
        );

        // Linha separadora
        $this->SetDrawColor(210, 164, 55);
        $this->SetLineWidth(0.8);
        $this->Line(12, 41, 198, 41);

        // Título da ficha
        $this->SetY(46);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(24, 54, 82);

        $this->Cell(
            0, 9,
            textoPDF('FICHA DE INSCRIÇÃO'),
            0, 1, 'C'
        );

        $this->SetFont('Arial', '', 9);
        $this->SetTextColor(100, 110, 120);

        $this->Cell(
            0, 5,
            textoPDF('DADOS DO CANDIDATO'),
            0, 1, 'C'
        );

        $this->Ln(5);
    }

    // Rodapé
    function Footer()
    {
        $this->SetY(-24);

        $this->SetDrawColor(210, 218, 225);
        $this->SetLineWidth(0.4);
        $this->Line(12, $this->GetY(), 198, $this->GetY());

        $this->Ln(3);

        $this->SetFont('Arial', 'B', 8);
        $this->SetTextColor(24, 54, 82);

        $this->Cell(
            0, 5,
            textoPDF('IBAN DA ESCOLA: ' . $this->iban_escola),
            0, 1, 'C'
        );

        $this->SetFont('Arial', 'I', 7);
        $this->SetTextColor(120, 120, 120);

        $this->Cell(
            0, 4,
            textoPDF(
                'Documento gerado automaticamente pelo Sistema Quineus'
            ),
            0, 1, 'C'
        );

        $this->SetY(-8);
        $this->SetFont('Arial', '', 7);

        $this->Cell(
            0, 4,
            textoPDF('Página ' . $this->PageNo()),
            0, 0, 'R'
        );
    }
}

// ============================================
// 4. CRIAR O PDF
// ============================================

$pdf = new FichaPDF('P', 'mm', 'A4');

$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 28);

$pdf->nome_escola = $nome_escola;
$pdf->sigla_escola = $sigla_escola;
$pdf->iban_escola = $iban_escola;
$pdf->caminho_logo = $caminho_logo;

$pdf->AddPage();

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(45, 55, 65);

// ============================================
// 5. FUNÇÃO PARA TÍTULOS DAS SECÇÕES
// ============================================

function tituloSecao($pdf, $titulo)
{
    $pdf->Ln(3);

    $pdf->SetFillColor(235, 241, 246);
    $pdf->SetTextColor(24, 54, 82);
    $pdf->SetFont('Arial', 'B', 10);

    $pdf->Cell(
        186, 8,
        textoPDF('  ' . $titulo),
        0, 1, 'L', true
    );

    $pdf->SetTextColor(45, 55, 65);
    $pdf->Ln(2);
}

// ============================================
// 6. FUNÇÃO PARA LINHAS DA TABELA
// ============================================

function linhaTabela($pdf, $etiqueta, $valor)
{
    $altura = 10;
    $larguraEtiqueta = 57;
    $larguraValor = 129;

    // Garantir espaço antes de desenhar a linha
    if ($pdf->GetY() + $altura > 265) {
        $pdf->AddPage();
    }

    $x = 12;
    $y = $pdf->GetY();

    // Etiqueta
    $pdf->SetFillColor(245, 247, 249);
    $pdf->SetDrawColor(218, 224, 230);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(24, 54, 82);

    $pdf->SetXY($x, $y);

    $pdf->Cell(
        $larguraEtiqueta, $altura,
        textoPDF($etiqueta),
        1, 0, 'L', true
    );

    // Valor
    $pdf->SetXY($x + $larguraEtiqueta, $y);
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(45, 55, 65);

    $valor = trim((string) $valor);

    if ($valor === '') {
        $valor = 'Não informado';
    }

    $pdf->Cell(
        $larguraValor, $altura,
        textoPDF($valor),
        1, 1, 'L'
    );
}

// ============================================
// 7. NÚMERO E DATA DA INSCRIÇÃO
// ============================================

$numero_inscricao = '#' .
    str_pad((string) $estudante['id'], 5, '0', STR_PAD_LEFT);

$data_inscricao = 'Não registada';

if (!empty($estudante['data_inscricao'])) {
    $timestamp = strtotime($estudante['data_inscricao']);

    if ($timestamp !== false) {
        $data_inscricao = date('d/m/Y H:i', $timestamp);
    }
}

$pdf->SetFillColor(24, 54, 82);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 9);

$pdf->Cell(
    93, 10,
    textoPDF('N.º DE INSCRIÇÃO: ' . $numero_inscricao),
    0, 0, 'C', true
);

$pdf->SetFillColor(245, 177, 0);
$pdf->SetTextColor(24, 54, 82);

$pdf->Cell(
    93, 10,
    textoPDF('DATA: ' . $data_inscricao),
    0, 1, 'C', true
);

// ============================================
// 8. DADOS PESSOAIS
// ============================================

tituloSecao($pdf, '01. DADOS PESSOAIS');

linhaTabela(
    $pdf,
    'Nome completo:',
    $estudante['nome_completo'] ?? ''
);

linhaTabela(
    $pdf,
    'Número do BI:',
    $estudante['numero_bi'] ?? ''
);

$data_formatada = 'Não informada';

if (!empty($estudante['data_nascimento'])) {
    $timestamp = strtotime($estudante['data_nascimento']);

    if ($timestamp !== false) {
        $data_formatada = date('d/m/Y', $timestamp);
    }
}

linhaTabela(
    $pdf,
    'Data de nascimento:',
    $data_formatada
);

linhaTabela(
    $pdf,
    'Telefone:',
    $estudante['telefone'] ?? ''
);

// E-mail, se existir na tabela
if (isset($estudante['email'])) {
    linhaTabela(
        $pdf,
        'E-mail:',
        $estudante['email']
    );
}

// ============================================
// 9. INFORMAÇÕES ACADÉMICAS
// ============================================

tituloSecao($pdf, '02. INFORMAÇÕES ACADÉMICAS');

linhaTabela(
    $pdf,
    'Curso pretendido:',
    $estudante['curso'] ?? ''
);

// ============================================
// 11. DECLARAÇÃO
// ============================================

tituloSecao($pdf, '03. DECLARAÇÃO');

$pdf->SetFont('Arial', '', 10);

$pdf->MultiCell(
    186, 6,
    textoPDF(
        'Declaro que os dados apresentados nesta ficha são ' .
        'verdadeiros e comprometo-me a apresentar os documentos ' .
        'originais necessários para a confirmação da matrícula.'
    ),
    0, 'J'
);

// ============================================
// 12. ASSINATURAS
// ============================================

$pdf->Ln(12);

if ($pdf->GetY() + 18 > 265) {
    $pdf->AddPage();
    $pdf->Ln(5);
}

$pdf->SetDrawColor(130, 140, 150);

$pdf->Cell(85, 1, '', 'T', 0);
$pdf->Cell(16, 1, '', 0, 0);
$pdf->Cell(85, 1, '', 'T', 1);

$pdf->Ln(2);

$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(80, 90, 100);

$pdf->Cell(
    85, 6,
    textoPDF('Assinatura do candidato'),
    0, 0, 'C'
);

$pdf->Cell(16, 6, '', 0, 0);

$pdf->Cell(
    85, 6,
    textoPDF('Assinatura / carimbo da instituição'),
    0, 1, 'C'
);

// ============================================
// 13. NOTA IMPORTANTE
// ============================================

$pdf->Ln(4);

$pdf->SetFillColor(255, 248, 225);
$pdf->SetTextColor(80, 70, 45);
$pdf->SetFont('Arial', '', 9);

$pdf->MultiCell(
    186, 6,
    textoPDF(
        'NOTA IMPORTANTE: apresente esta ficha impressa no dia ' .
        'da matrícula, juntamente com o Bilhete de Identidade ' .
        'original e o certificado de habilitações.'
    ),
    0, 'L', true
);

// ============================================
// 14. GERAR O PDF NO NAVEGADOR
// ============================================

$conexao->close();

// Remover saída anterior para não corromper o PDF
while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdf->Output(
    'D',
    'ficha_inscricao_' . $estudante['id'] . '.pdf'
);

exit;

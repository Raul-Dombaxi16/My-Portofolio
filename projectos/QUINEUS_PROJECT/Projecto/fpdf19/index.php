<?php

require('fpdf19/fpdf.php');

$pdf = new FPDF();
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(40, 10, 'Meu primeiro PDF');

$pdf->Output();

?>

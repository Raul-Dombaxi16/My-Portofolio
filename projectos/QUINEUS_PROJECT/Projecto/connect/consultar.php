<?php

require_once __DIR__ . "/conexao.php";

// Confirmar se o formulário foi enviado por POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../view/consultas.php");
    exit;
}

// Receber o valor introduzido
$identificador = trim($_POST["identificador"] ?? "");

// Verificar se o campo está vazio
if ($identificador === "") {
    header("Location: ../view/consultas.php?erro=vazio");
    exit;
}

// Converter o número da ficha, por exemplo #00015, em 15
$numero_ficha = ltrim(ltrim($identificador, "#"), "0");

if ($numero_ficha === "") {
    $numero_ficha = "0";
}

$id = ctype_digit($numero_ficha) ? (int) $numero_ficha : 0;

// Procurar o aluno pelo ID, BI ou nome completo
$sql = "SELECT id
        FROM alunos
        WHERE id = ?
           OR numero_bi = ?
           OR nome_completo = ?
        LIMIT 1";

$stmt = $conexao->prepare($sql);

if (!$stmt) {
    die("Erro ao preparar a consulta: " . $conexao->error);
}

$stmt->bind_param(
    "iss",
    $id,
    $identificador,
    $identificador
);

$stmt->execute();

$resultado = $stmt->get_result();
$aluno = $resultado->fetch_assoc();

$stmt->close();
$conexao->close();

// Se não encontrar o aluno
if (!$aluno) {
    header("Location: ../view/consultas.php?erro=nao_encontrado");
    exit;

}

// Abrir a ficha PDF do aluno encontrado
header("Location: ficha.php?id=" . (int) $aluno["id"]);
exit;

?>

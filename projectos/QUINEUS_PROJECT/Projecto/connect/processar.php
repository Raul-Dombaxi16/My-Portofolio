
<?php

// ============================================
// FICHEIRO: index.php
// FUNÇÃO: Processar inscrição de estudantes
// ============================================

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

include("conexao.php");

$mensagem_erro = "";

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // ============================================
    // 1. RECEBER E VALIDAR OS DADOS
    // ============================================

    $nome_completo = trim($_POST["nome_completo"] ?? "");
    $numero_bi = trim($_POST["nUmero_BI"] ?? "");
    $data_nascimento = trim($_POST["data_nascimento"] ?? "");
    $telefone = trim($_POST["telefone"] ?? "");
    $curso = trim($_POST["curso"] ?? "");
    $sexo = trim($_POST["sexo"] ?? "");
    $classe = trim($_POST["classe"] ?? "");
    $morada = trim($_POST["morada"] ?? "");

    if (
        $nome_completo === "" ||
        $numero_bi === "" ||
        $data_nascimento === "" ||
        $telefone === "" ||
        $curso === "" ||
        $sexo === "" ||
        $classe === "" ||
        $morada === ""
    ) {
        $mensagem_erro = "Preencha todos os campos obrigatórios.";
    }

    // Validar a data
    if ($mensagem_erro === "") {
        $data = DateTime::createFromFormat("!Y-m-d", $data_nascimento);

        if (
            !$data ||
            $data->format("Y-m-d") !== $data_nascimento ||
            $data > new DateTime("today")
        ) {
            $mensagem_erro = "Introduza uma data de nascimento válida.";
        }
    }

    // ============================================
    // 2. VALIDAR A FOTOGRAFIA
    // ============================================

    $ficheiro = $_FILES["numero_foto"] ?? null;

    if ($mensagem_erro === "") {

        if (
            !$ficheiro ||
            $ficheiro["error"] !== UPLOAD_ERR_OK
        ) {
            $mensagem_erro = "Selecione uma fotografia válida.";
        } elseif ($ficheiro["size"] > 5 * 1024 * 1024) {
            $mensagem_erro = "A fotografia não pode ultrapassar 5 MB.";
        }
    }

    // ============================================
    // 3. GUARDAR A FOTOGRAFIA
    // ============================================

    $pasta_destino = __DIR__ . "/../uploads/";
    $caminho_foto = "";

    if ($mensagem_erro === "") {

        // Verifica o tipo real do ficheiro
        $informacao_imagem = @getimagesize($ficheiro["tmp_name"]);

        $tipos_permitidos = [
            IMAGETYPE_JPEG => "jpg",
            IMAGETYPE_PNG => "png"
        ];

        if (
            $informacao_imagem === false ||
            !isset($tipos_permitidos[$informacao_imagem[2]])
        ) {
            $mensagem_erro = "Apenas imagens JPG ou PNG são aceites.";
        } else {

            // Cria a pasta se ainda não existir
            if (
                !is_dir($pasta_destino) &&
                !mkdir($pasta_destino, 0755, true)
            ) {
                $mensagem_erro = "Não foi possível criar a pasta de fotografias.";
            } elseif (!is_writable($pasta_destino)) {
                $mensagem_erro = "A pasta de fotografias não permite escrita.";
            } else {

                $extensao = $tipos_permitidos[$informacao_imagem[2]];

                $nome_ficheiro = bin2hex(random_bytes(16))
                    . "." . $extensao;

                $destino_completo = $pasta_destino . $nome_ficheiro;

                // Caminho relativo guardado na base de dados
                $caminho_foto = "../uploads/" . $nome_ficheiro;

                if (!move_uploaded_file(
                    $ficheiro["tmp_name"],
                    $destino_completo
                )) {
                    $mensagem_erro = "Não foi possível guardar a fotografia.";
                }
            }
        }
    }

    // ============================================
    // 4. INSERIR O ESTUDANTE NA BASE DE DADOS
    // ============================================

    if ($mensagem_erro === "") {

        try {

            $sql = "INSERT INTO alunos
                (
                    nome_completo,
                    numero_bi,
                    data_nascimento,
                    telefone,
                    curso,
                    caminho_foto,
                    sexo,
                    classe,
                    morada
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $instrucao = $conexao->prepare($sql);

            $instrucao->bind_param(
                "sssssssss",
                $nome_completo,
                $numero_bi,
                $data_nascimento,
                $telefone,
                $curso,
                $caminho_foto,
                $sexo,
                $classe,
                $morada
            );

            $instrucao->execute();

            $id_estudante = $conexao->insert_id;

            $instrucao->close();
            $conexao->close();

            // Redireciona para a ficha do estudante
            header("Location: ficha.php?id=" . $id_estudante);
            exit();

        } catch (mysqli_sql_exception $e) {

            // Remove a fotografia se o registo falhar
            if ($caminho_foto !== "") {
                $ficheiro_guardado = $pasta_destino
                    . basename($caminho_foto);

                if (is_file($ficheiro_guardado)) {
                    unlink($ficheiro_guardado);
                }
            }

            if ($e->getCode() === 1062) {
                $mensagem_erro = "Este número de BI já está registado.";
            } else {
                error_log("Erro ao registar estudante: " . $e->getMessage());
                $mensagem_erro = "Não foi possível concluir a inscrição. Verifique os dados e tente novamente.";
            }
        }
    }
}
?>

<?php if ($mensagem_erro !== ""): ?>
    <div class="mensagem-erro" role="alert">
        <?= htmlspecialchars($mensagem_erro, ENT_QUOTES, "UTF-8") ?>
    </div>
<?php endif; ?>

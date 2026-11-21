<?php
$server = "localhost";
$user = "root";
$password = "";
$database = "registos_quineus";

$conexao = new mysqli($server,$user,$password,$database);
if($conexao ->connect_error){
    die("Erro de ligação".$conexao->connect_error);
}
// else{
//     echo "conexão  com sucesso";
// }
$conexao->set_charset("utf8mb4");


?>
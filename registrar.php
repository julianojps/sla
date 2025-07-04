<?php
include "conectar.php";

// Recebe os dados do POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$observacao = isset($_POST['observacao']) ? $_POST['observacao'] : '';
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

// Validação básica
if ($id <= 0 || ($status !== 'presente' && $status !== 'ausente')) {
    echo json_encode(['error' => 'Parâmetros inválidos']);
    exit;
}

// Busca o nome do usuário na tabela presenca (ou outra tabela onde estiver o nome)
$stmtNome = $conn->prepare("SELECT nome FROM presenca WHERE id = ?");
$stmtNome->bind_param("i", $id);
$stmtNome->execute();
$stmtNome->bind_result($nomeUsuario);
$stmtNome->fetch();
$stmtNome->close();

if (!$nomeUsuario) {
    $nomeUsuario = "Usuário desconhecido";
}

// Define novo status invertendo o atual
$novoStatus = ($status === 'presente') ? 'ausente' : 'presente';
$dataHora = date('Y-m-d H:i:s');

// Atualiza status na tabela presenca
$updateSql = "UPDATE presenca SET status = ?, atualizado_em = ? WHERE id = ?";
$stmtUpdate = $conn->prepare($updateSql);
$stmtUpdate->bind_param('ssi', $novoStatus, $dataHora, $id);
$stmtUpdate->execute();

// Insere no log
$insertSql = "INSERT INTO presenca_log (usuario_id, status, observacao, tipo) VALUES (?, ?, ?, ?)";
$stmtInsert = $conn->prepare($insertSql);
$stmtInsert->bind_param("isss", $id, $novoStatus, $observacao, $tipo);
$stmtInsert->execute();

// Monta mensagem com nome do usuário e novo status
$mensagem = "Status de $nomeUsuario alterado para: $novoStatus";

// Envia mensagem para o PC csti1-df via comando msg do Windows
$comando = "msg /SERVER:csti1-df * \"$mensagem\"";

// Executa o comando e captura saída/erro
exec($comando, $output, $return_var);
if ($return_var !== 0) {
    error_log("Erro ao enviar mensagem msg para csti1-df: " . implode("\n", $output));
}

// Retorna JSON com o novo status
echo json_encode(['status' => $novoStatus]);

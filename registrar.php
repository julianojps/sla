<?php
include "conectar.php";


// Recebe os dados do POST
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';
$observacao = isset($_POST['observacao']) ? $_POST['observacao'] : '';
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

$novoStatus = ($status === 'presente') ? 'ausente' : 'presente';
$dataHora = date('Y-m-d H:i:s');

// Atualiza status na tabela original
$conn->query("UPDATE presenca SET status = '$novoStatus', atualizado_em = '$dataHora' WHERE id = $id");

// Verifica se a tabela de log tem a coluna `observacao`
// Insere no log
$stmt = $conn->prepare("INSERT INTO presenca_log (usuario_id, status, observacao, tipo) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $id, $novoStatus, $observacao, $tipo);
$stmt->execute();

echo json_encode(['status' => $novoStatus]);

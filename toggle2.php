<?php
include "conectar.php";

$id = $_POST['id'] ?? 0;

$stmt = $conn->prepare("SELECT status FROM presenca WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

$novoStatus = $row['status'] === 'presente' ? 'ausente' : 'presente';

$update = $conn->prepare("UPDATE presenca SET status = ? WHERE id = ?");
$update->bind_param("si", $novoStatus, $id);
$update->execute();

echo json_encode(['status' => $novoStatus]);


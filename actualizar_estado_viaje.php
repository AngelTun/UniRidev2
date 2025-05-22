<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$viajeId = (int)$_POST['viaje_id'];
$estado = $_POST['estado'];

// Verificar que el usuario es el conductor
$stmt = $pdo->prepare("SELECT conductor_id FROM viajes WHERE id = ?");
$stmt->execute([$viajeId]);
$viaje = $stmt->fetch();

if ($viaje['conductor_id'] != $_SESSION['id']) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

// Actualizar estado
$stmt = $pdo->prepare("UPDATE viajes SET estado = ? WHERE id = ?");
$stmt->execute([$estado, $viajeId]);

echo json_encode(['success' => true]);
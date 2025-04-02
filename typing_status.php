<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) {
    echo json_encode(['success' => false]);
    exit;
}

$correo_usuario = $_SESSION['usuario'];
$contacto = $_POST['contacto'] ?? '';
$typing = $_POST['typing'] ?? 0;

if (empty($contacto)) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO actividad_usuario (correo, ultimo_contacto, ultima_actividad, typing) 
        VALUES (?, ?, NOW(), ?)
        ON DUPLICATE KEY UPDATE 
            ultimo_contacto = VALUES(ultimo_contacto),
            ultima_actividad = VALUES(ultima_actividad),
            typing = VALUES(typing)
    ");
    $stmt->execute([$correo_usuario, $contacto, $typing]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Error en typing_status.php: ' . $e->getMessage());
    echo json_encode(['success' => false]);
}
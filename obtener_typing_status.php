<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false]);
    exit;
}

$contacto = $_GET['contacto'] ?? '';
if (empty($contacto)) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT typing FROM actividad_usuario 
        WHERE correo = ? AND typing = 1
        AND ultima_actividad > DATE_SUB(NOW(), INTERVAL 3 SECOND)
    ");
    $stmt->execute([$contacto]);
    
    echo json_encode([
        'success' => true,
        'isTyping' => $stmt->fetch() !== false
    ]);
} catch (PDOException $e) {
    error_log('Error en obtener_typing_status.php: ' . $e->getMessage());
    echo json_encode(['success' => false]);
}
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
        SELECT ultima_actividad FROM actividad_usuario 
        WHERE correo = ?
    ");
    $stmt->execute([$contacto]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'ultima_actividad' => $result ? $result['ultima_actividad'] : null
    ]);
} catch (PDOException $e) {
    error_log('Error en ultima_actividad.php: ' . $e->getMessage());
    echo json_encode(['success' => false]);
}
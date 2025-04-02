<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Solicitud no válida']);
    exit;
}

$correo_usuario = $_SESSION['usuario'];
$contacto = $_POST['contacto'] ?? '';

if (empty($contacto)) {
    echo json_encode(['success' => false, 'error' => 'Contacto no especificado']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE mensajes SET leido = 1 
        WHERE destinatario = ? AND remitente = ? AND leido = 0
    ");
    $stmt->execute([$correo_usuario, $contacto]);
    
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('Error al marcar mensajes como leídos: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error en el servidor']);
}
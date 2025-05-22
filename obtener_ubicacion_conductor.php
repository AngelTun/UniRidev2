<?php
require 'conexion.php';
session_start();

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}
$viaje_id = isset($_GET['viaje_id']) ? (int)$_GET['viaje_id'] : 0;

// Comprobar que el usuario tiene una reserva para ese viaje y que el viaje está en curso
$correo = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Usuario no encontrado']);
    exit;
}
$usuario_id = $user['id'];

// ¿Tiene reserva?
$stmt = $pdo->prepare("SELECT id FROM reservas WHERE usuario_id = ? AND viaje_id = ?");
$stmt->execute([$usuario_id, $viaje_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'No tienes reserva para este viaje']);
    exit;
}

// ¿El viaje está en curso?
$stmt = $pdo->prepare("SELECT lat_actual, lng_actual, en_curso FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje || !$viaje['en_curso']) {
    echo json_encode(['success' => false, 'error' => 'El viaje no está en curso']);
    exit;
}

echo json_encode([
    'success' => true,
    'lat' => $viaje['lat_actual'] ?: 20.9671,
    'lng' => $viaje['lng_actual'] ?: -89.6236
]);

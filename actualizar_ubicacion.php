<?php
session_start();
require 'conexion.php';

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false, 'error'=>'No autorizado']);
    exit;
}

$lat = isset($_POST['lat']) ? floatval($_POST['lat']) : null;
$lng = isset($_POST['lng']) ? floatval($_POST['lng']) : null;
$viaje_id = isset($_POST['viaje_id']) ? intval($_POST['viaje_id']) : 0;

// Validar
if ($lat === null || $lng === null || !$viaje_id) {
    echo json_encode(['success'=>false, 'error'=>'Datos incompletos']);
    exit;
}

// Verificar que el usuario sea el conductor del viaje y que esté en curso
$stmt = $pdo->prepare("SELECT conductor_id, en_curso FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$viaje || $viaje['conductor_id'] != $_SESSION['id'] || !$viaje['en_curso']) {
    echo json_encode(['success'=>false, 'error'=>'No autorizado o viaje no en curso']);
    exit;
}

// Actualizar la ubicación del viaje
$stmt = $pdo->prepare("UPDATE viajes SET lat=?, lng=? WHERE id=?");
$stmt->execute([$lat, $lng, $viaje_id]);
echo json_encode(['success'=>true]);

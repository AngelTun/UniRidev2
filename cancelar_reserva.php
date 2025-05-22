<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

if (!isset($_SESSION['usuario']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['reserva_id'])) {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida.']);
    exit;
}

$reserva_id = intval($_POST['reserva_id']);

// Establecer zona horaria correcta
date_default_timezone_set('America/Mexico_City'); // ← AJUSTA SI ESTÁS EN OTRA ZONA

// Obtener ID del usuario
$stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmtUser->execute([$_SESSION['usuario']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Usuario no válido.']);
    exit;
}

// Obtener detalles de la reserva
$stmt = $pdo->prepare("
    SELECT r.*, v.fecha, v.hora 
    FROM reservas r 
    JOIN viajes v ON r.viaje_id = v.id 
    WHERE r.id = ? AND r.usuario_id = ?
");
$stmt->execute([$reserva_id, $user['id']]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reserva) {
    echo json_encode(['success' => false, 'error' => 'Reserva no encontrada.']);
    exit;
}

// Comparar fecha y hora del viaje con la hora actual
$fechaHoraViaje = new DateTime($reserva['fecha'] . ' ' . $reserva['hora']);
$ahora = new DateTime();

if ($fechaHoraViaje <= $ahora) {
    echo json_encode(['success' => false, 'error' => 'No puedes cancelar reservas pasadas.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Liberar un asiento
    $stmt = $pdo->prepare("UPDATE viajes SET asientos = asientos + 1 WHERE id = ?");
    $stmt->execute([$reserva['viaje_id']]);

    // Eliminar la reserva
    $stmt = $pdo->prepare("DELETE FROM reservas WHERE id = ?");
    $stmt->execute([$reserva_id]);

    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}

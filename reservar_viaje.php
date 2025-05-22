<?php
session_start();
require 'conexion.php';

header('Content-Type: application/json');

// Validar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'Debes iniciar sesión para reservar.']);
    exit;
}

// Validar POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['viaje_id'])) {
    echo json_encode(['success' => false, 'error' => 'Solicitud inválida']);
    exit;
}

$viaje_id = intval($_POST['viaje_id']);

// Buscar usuario
$stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmtUser->execute([$_SESSION['usuario']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'Usuario no válido']);
    exit;
}
$usuario_id = $user['id'];

// 1. Evitar reservar dos veces el mismo viaje
$stmt = $pdo->prepare("SELECT id FROM reservas WHERE usuario_id = ? AND viaje_id = ?");
$stmt->execute([$usuario_id, $viaje_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Ya reservaste este viaje.']);
    exit;
}

// 2. Evitar reservar dos viajes a la vez (futuros, no finalizados)
$stmt = $pdo->prepare("
    SELECT r.id 
    FROM reservas r
    JOIN viajes v ON r.viaje_id = v.id
    WHERE r.usuario_id = ? AND CONCAT(v.fecha, ' ', v.hora) >= NOW()
");
$stmt->execute([$usuario_id]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'error' => 'Ya tienes una reserva activa. Cancela tu otra reserva antes de reservar otro viaje.']);
    exit;
}

// 3. Revisar lugares disponibles
$stmt = $pdo->prepare("SELECT asientos FROM viajes WHERE id = ?");
$stmt->execute([$viaje_id]);
$viaje = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$viaje || $viaje['asientos'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'No hay asientos disponibles.']);
    exit;
}

// 4. Realizar la reserva
try {
    $pdo->beginTransaction();

    // Insertar la reserva
    $stmt = $pdo->prepare("INSERT INTO reservas (usuario_id, viaje_id) VALUES (?, ?)");
    $stmt->execute([$usuario_id, $viaje_id]);

    // Descontar un asiento
    $stmt = $pdo->prepare("UPDATE viajes SET asientos = asientos - 1 WHERE id = ? AND asientos > 0");
    $stmt->execute([$viaje_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'msg' => 'Reserva realizada con éxito.']);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => 'Error al reservar: ' . $e->getMessage()]);
}

<?php
session_start();
require 'conexion.php';

// 1. Recibimos por GET el último ID que el cliente ya vio
$ultimo_id = isset($_GET['ultimo_id']) ? intval($_GET['ultimo_id']) : 0;

// 2. Consultamos viajes futuros cuyo id sea > ultimo_id
$stmt = $pdo->prepare(
    "SELECT v.id, v.origen, v.destino, v.fecha, v.hora, v.asientos, v.precio, u.nombre AS conductor
     FROM viajes v
     JOIN usuarios u ON v.conductor_id = u.id
     WHERE v.id > ? AND CONCAT(v.fecha, ' ', v.hora) >= NOW()
     ORDER BY v.id ASC"
);
$stmt->execute([$ultimo_id]);
$nuevos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Respondemos JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'count'   => count($nuevos),
    'viajes'  => $nuevos
]);

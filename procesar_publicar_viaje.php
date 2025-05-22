<?php
session_start();
require 'conexion.php';

// Verifica si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
  echo '<div class="error-alert">Debes iniciar sesión.</div>';
  exit;
}

// Recupera ID del usuario si no está definido
if (!isset($_SESSION['id'])) {
  $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
  $stmt->execute([ $_SESSION['usuario'] ]);
  $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($usuario) {
    $_SESSION['id'] = $usuario['id'];
  } else {
    echo '<div class="error-alert">No se pudo obtener el ID del usuario.</div>';
    exit;
  }
}

$errores = [];
$origen   = trim($_POST['origen'] ?? '');
$destino  = trim($_POST['destino'] ?? '');
$fecha    = $_POST['fecha'] ?? '';
$hora     = $_POST['hora'] ?? '';
$asientos = intval($_POST['asientos'] ?? 0);
$precio   = floatval($_POST['precio'] ?? 0);
$detalles = trim($_POST['detalles'] ?? '');

// Validaciones
if (!$origen) $errores[] = "El origen es requerido";
if (!$destino) $errores[] = "El destino es requerido";
if (!$fecha) $errores[] = "La fecha es requerida";
if (!$hora) $errores[] = "La hora es requerida";
if ($asientos < 1) $errores[] = "Los asientos deben ser mayor a 0";
if ($precio < 0) $errores[] = "El precio no es válido";

// Mostrar errores
if (!empty($errores)) {
  echo '<div class="error-alert" id="mensajeError">';
  foreach ($errores as $e) echo '<p>' . htmlspecialchars($e) . '</p>';
  echo '</div>';
  exit;
}

// Insertar en la base de datos
try {
  $stmt = $pdo->prepare("INSERT INTO viajes (conductor_id, origen, destino, fecha, hora, asientos, precio, detalles, created_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
  $stmt->execute([
    $_SESSION['id'],
    $origen,
    $destino,
    $fecha,
    $hora,
    $asientos,
    $precio,
    $detalles
  ]);
  echo '<div class="success-alert mensajeExito">¡Viaje publicado exitosamente!</div>';
} catch (PDOException $e) {
  echo '<div class="error-alert">Error al guardar el viaje.</div>';
}

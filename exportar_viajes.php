<?php
session_start();

// Validación completa de sesión
if (!isset($_SESSION['usuario'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Acceso no autorizado. <a href="iniciosesion.php">Iniciar sesión</a>');
}

require_once 'conexion.php';

// Verificación de sesión única
$correo_usuario = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT session_id, id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && $usuario['session_id'] !== session_id()) {
    header('HTTP/1.1 403 Forbidden');
    exit('Sesión inválida o concurrente detectada');
}

$usuario_id = $usuario['id'];
$hoy = date('Y-m-d');

// Sanitización de parámetros
$filtros = [
    'fecha' => isset($_GET['fecha']) && $_GET['fecha'] <= $hoy ? $_GET['fecha'] : '',
    'origen' => filter_input(INPUT_GET, 'origen', FILTER_SANITIZE_STRING),
    'destino' => filter_input(INPUT_GET, 'destino', FILTER_SANITIZE_STRING),
    'asientos' => filter_input(INPUT_GET, 'asientos', FILTER_VALIDATE_INT)
];

// Construcción de consulta segura
$where = 'WHERE conductor_id = :id AND fecha <= :hoy';
$params = [
    ':id' => $usuario_id,
    ':hoy' => $hoy
];

// Aplicación de filtros
if (!empty($filtros['fecha'])) {
    $where .= ' AND fecha = :fecha';
    $params[':fecha'] = $filtros['fecha'];
}

if (!empty($filtros['origen'])) {
    $where .= ' AND origen LIKE :origen';
    $params[':origen'] = '%' . $filtros['origen'] . '%';
}

if (!empty($filtros['destino'])) {
    $where .= ' AND destino LIKE :destino';
    $params[':destino'] = '%' . $filtros['destino'] . '%';
}

if (!empty($filtros['asientos']) && $filtros['asientos'] >= 1 && $filtros['asientos'] <= 6) {
    $where .= ' AND asientos >= :asientos';
    $params[':asientos'] = $filtros['asientos'];
}

try {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="mis_viajes_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');

    $stmt = $pdo->prepare("SELECT * FROM viajes $where ORDER BY fecha DESC, hora DESC");
    $stmt->execute($params);
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>";
    echo "<tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Origen</th>
            <th>Destino</th>
            <th>Asientos</th>
            <th>Precio</th>
          </tr>";
    
    foreach ($viajes as $viaje) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($viaje['fecha']) . "</td>";
        echo "<td>" . htmlspecialchars($viaje['hora']) . "</td>";
        echo "<td>" . htmlspecialchars($viaje['origen']) . "</td>";
        echo "<td>" . htmlspecialchars($viaje['destino']) . "</td>";
        echo "<td>" . htmlspecialchars($viaje['asientos']) . "</td>";
        echo "<td>$" . number_format($viaje['precio'], 2) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    exit;

} catch (PDOException $e) {
    header('HTTP/1.1 500 Internal Server Error');
    exit('Error al generar el reporte: ' . htmlspecialchars($e->getMessage()));
}
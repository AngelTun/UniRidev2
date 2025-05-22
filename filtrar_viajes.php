<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    die(json_encode(['error' => 'No autenticado']));
}

require_once 'conexion.php';

$correo_usuario = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT session_id, id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario['session_id'] !== session_id()) {
    die(json_encode(['error' => 'Sesión inválida']));
}

$usuario_id = $usuario['id'];
$hoy = date('Y-m-d');

$filtros = [
    'fecha' => isset($_GET['fecha']) && $_GET['fecha'] <= $hoy ? $_GET['fecha'] : '',
    'origen' => filter_input(INPUT_GET, 'origen', FILTER_SANITIZE_STRING),
    'destino' => filter_input(INPUT_GET, 'destino', FILTER_SANITIZE_STRING),
    'asientos' => filter_input(INPUT_GET, 'asientos', FILTER_VALIDATE_INT)
];

$where = 'WHERE r.usuario_id = :usuario_id';
$params = [':usuario_id' => $usuario_id];

if (!empty($filtros['fecha'])) {
    $where .= ' AND v.fecha = :fecha';
    $params[':fecha'] = $filtros['fecha'];
}
if (!empty($filtros['origen'])) {
    $where .= ' AND v.origen LIKE :origen';
    $params[':origen'] = '%' . $filtros['origen'] . '%';
}
if (!empty($filtros['destino'])) {
    $where .= ' AND v.destino LIKE :destino';
    $params[':destino'] = '%' . $filtros['destino'] . '%';
}
if (!empty($filtros['asientos']) && $filtros['asientos'] >= 1 && $filtros['asientos'] <= 6) {
    $where .= ' AND v.asientos >= :asientos';
    $params[':asientos'] = $filtros['asientos'];
}

try {
    $sql = "SELECT v.*, r.id AS reserva_id, r.fecha_reserva
            FROM reservas r
            JOIN viajes v ON r.viaje_id = v.id
            $where
            ORDER BY v.fecha DESC, v.hora DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $viajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('<p class="text-red-500 text-center">Error al cargar los viajes: ' . htmlspecialchars($e->getMessage()) . '</p>');
}
?>

<div id="resultados">
<?php if (empty($viajes)): ?>
    <p class="text-center text-gray-500 py-4">No tienes viajes reservados</p>
<?php else: ?>
    <div class="flex justify-end mb-4">
        <a id="btnExportar" href="exportar_viajes.php?<?= http_build_query($_GET) ?>" 
           class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
            Exportar a Excel
        </a>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($viajes as $viaje): ?>
    <?php
    date_default_timezone_set('America/Mexico_City'); // Asegura coherencia con la hora local
    $fechaHoraViaje = new DateTime($viaje['fecha'] . ' ' . $viaje['hora']);
    $ahora = new DateTime();
    $esFuturo = $fechaHoraViaje > $ahora;
    ?>

    <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition-shadow">
        <div class="text-sm text-gray-400 mb-2">
            <span class="block">Fecha: <?= htmlspecialchars($viaje['fecha']) ?></span>
            <span class="block">Hora: <?= htmlspecialchars($viaje['hora']) ?></span>
        </div>
        <div class="mb-4">
            <p class="text-lg font-semibold text-indigo-600">
                <?= htmlspecialchars($viaje['origen']) ?>
                <span class="text-gray-400">→</span>
                <?= htmlspecialchars($viaje['destino']) ?>
            </p>
        </div>
        <div class="flex justify-between items-center">
            <span class="text-gray-500">Asientos: <?= htmlspecialchars($viaje['asientos']) ?></span>
            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-sm">
                $<?= number_format($viaje['precio'], 2) ?>
            </span>
        </div>
        <div class="mt-2 text-xs text-gray-400">
            Reservado el: <?= isset($viaje['fecha_reserva']) ? htmlspecialchars($viaje['fecha_reserva']) : '---' ?>
        </div>

        <button 
            class="cancelar-btn mt-4 px-4 py-2 rounded text-white <?= $esFuturo ? 'bg-red-500 hover:bg-red-700' : 'bg-red-300 cursor-not-allowed' ?>" 
            data-id="<?= $viaje['reserva_id'] ?>" 
            <?= $esFuturo ? '' : 'disabled' ?>>
            Cancelar
        </button>
    </div>
<?php endforeach; ?>


    </div>
<?php endif; ?>
</div>

<?php
require_once __DIR__ . '/conexion.php';

// Verificar conexión MySQLi
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Obtener parámetros y sanitizar
$filtros = [
    'fecha' => isset($_GET['fecha']) ? $conn->real_escape_string($_GET['fecha']) : '',
    'origen' => isset($_GET['origen']) ? $conn->real_escape_string($_GET['origen']) : '',
    'destino' => isset($_GET['destino']) ? $conn->real_escape_string($_GET['destino']) : '',
    'asientos' => isset($_GET['asientos']) ? (int)$_GET['asientos'] : 0
];

// Construir consulta base
$sql = "SELECT * FROM viajes WHERE fecha <= CURDATE()";
$params = [];
$types = '';

// Aplicar filtros
if (!empty($filtros['fecha'])) {
    $sql .= " AND fecha = ?";
    $params[] = $filtros['fecha'];
    $types .= 's';
}

if (!empty($filtros['origen'])) {
    $sql .= " AND origen LIKE ?";
    $params[] = '%' . $filtros['origen'] . '%';
    $types .= 's';
}

if (!empty($filtros['destino'])) {
    $sql .= " AND destino LIKE ?";
    $params[] = '%' . $filtros['destino'] . '%';
    $types .= 's';
}

if ($filtros['asientos'] > 0) {
    $sql .= " AND asientos >= ?";
    $params[] = $filtros['asientos'];
    $types .= 'i';
}

// Preparar y ejecutar consulta
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error al preparar la consulta: " . $conn->error);
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$resultado = $stmt->get_result();
?>


    <!-- Resultados -->
    <div id="resultados">
        <?php if ($resultado->num_rows === 0): ?>
            <p class="text-center text-gray-500 py-4">No se encontraron viajes con estos filtros</p>
        <?php else: ?>
            <div class="flex justify-end mb-4">
                    <a id="btnExportar" href="exportar_viajes.php?<?= http_build_query($_GET) ?>" 
                       class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        Exportar a Excel
                    </a>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php while ($viaje = $resultado->fetch_assoc()): ?>
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
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


<?php
// Cerrar conexión
$stmt->close();
$conn->close();
?>
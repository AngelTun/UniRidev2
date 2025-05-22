<?php
require 'conexion.php';
session_start();

// Obtener ID de usuario si está logueado
$usuario_id = null;
if (isset($_SESSION['usuario'])) {
    $correo = $_SESSION['usuario'];
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);
    $usuario_id = $usr ? $usr['id'] : null;
}

try {
    // Solo traemos viajes pendientes o en curso, y cuya fecha/hora aún no haya pasado
    $sql = "
        SELECT v.*, CONCAT(u.nombres, ' ', u.apellidos) AS conductor
        FROM viajes v
        INNER JOIN usuarios u ON v.conductor_id = u.id
        WHERE v.estado IN ('pendiente','en_curso')
          AND CONCAT(v.fecha, ' ', v.hora) >= NOW()
        ORDER BY v.fecha ASC, v.hora ASC
        LIMIT 6
    ";
    $stmt = $pdo->query($sql);

    while ($viaje = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $yaReservado = false;
        $tieneActiva = false;
        $puedeSeguir = false;
        // Detectar si es el propio viaje publicado
        $esPropio = ($usuario_id && $viaje['conductor_id'] == $usuario_id);

        if ($usuario_id && !$esPropio) {
            // ¿Ya reservó este viaje?
            $stmt2 = $pdo->prepare("SELECT id FROM reservas WHERE usuario_id = ? AND viaje_id = ?");
            $stmt2->execute([$usuario_id, $viaje['id']]);
            $yaReservado = (bool)$stmt2->fetch();

            // ¿Tiene otra reserva activa en un viaje futuro?
            $stmt3 = $pdo->prepare("
                SELECT r.id
                FROM reservas r
                JOIN viajes v2 ON r.viaje_id = v2.id
                WHERE r.usuario_id = ?
                  AND v2.estado IN ('pendiente','en_curso')
                  AND CONCAT(v2.fecha, ' ', v2.hora) >= NOW()
            ");
            $stmt3->execute([$usuario_id]);
            $tieneActiva = (bool)$stmt3->fetch();

            // ¿Puede seguir al conductor?
            $puedeSeguir = $yaReservado && $viaje['estado'] === 'en_curso';
        }
        ?>
        <div class="card" data-id="<?= $viaje['id'] ?>">
            <h3 class="card-title">
                <?= htmlspecialchars($viaje['origen']) ?> → <?= htmlspecialchars($viaje['destino']) ?>
            </h3>
            <p class="card-text">
                Fecha: <?= date('d M Y', strtotime($viaje['fecha'])) ?>  
                Hora: <?= date('H:i', strtotime($viaje['hora'])) ?>
            </p>
            <p class="card-text">Conductor: <?= htmlspecialchars($viaje['conductor']) ?></p>
            <p class="card-text">Espacios disponibles: <?= $viaje['asientos'] ?></p>
            <p class="card-text">Precio: $<?= number_format($viaje['precio'], 2) ?> MXN</p>

            <?php if (!$usuario_id): ?>
                <button class="btn" disabled>Inicia sesión para reservar</button>

            <?php elseif ($esPropio): ?>
                <span class="status-badge bg-blue-500">Tu viaje</span>

            <?php elseif (!$yaReservado && !$tieneActiva && $viaje['asientos'] > 0): ?>
                <button class="btn reservar-btn" data-id="<?= $viaje['id'] ?>">Reservar</button>

            <?php elseif ($yaReservado): ?>
                <?php if ($puedeSeguir): ?>
                    <button class="btn seguir-btn" data-id="<?= $viaje['id'] ?>">Seguir Conductor</button>
                <?php else: ?>
                    <span class="status-badge bg-green-200 text-green-700">Reservado</span>
                <?php endif; ?>

            <?php elseif ($tieneActiva): ?>
                <span class="status-badge bg-yellow-200 text-yellow-800">Ya tienes una reserva activa</span>

            <?php elseif ($viaje['asientos'] <= 0): ?>
                <span class="status-badge bg-red-200 text-red-700">Sin asientos</span>
            <?php endif; ?>
        </div>
        <?php
    }
} catch (PDOException $e) {
    echo '<p class="text-red-500">Error al cargar viajes: ' . htmlspecialchars($e->getMessage()) . '</p>';
}
?>

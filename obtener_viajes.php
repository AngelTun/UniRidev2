<?php
require 'conexion.php';

try {
    // 1) Seleccionamos también el nombre completo del conductor
    $sql = "SELECT 
                v.id,
                v.origen,
                v.destino,
                v.fecha,
                v.hora,
                v.asientos,
                v.precio,
                CONCAT(u.nombres, ' ', u.apellidos) AS conductor
            FROM viajes v 
            INNER JOIN usuarios u 
                ON v.conductor_id = u.id 
            WHERE CONCAT(v.fecha, ' ', v.hora) >= NOW()
            ORDER BY v.fecha ASC, v.hora ASC
            LIMIT 6";
    $stmt = $pdo->query($sql);
    
    while ($viaje = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $fecha = date('d M Y', strtotime($viaje['fecha']));
        $hora  = date('H:i',   strtotime($viaje['hora']));
        ?>
        <div class="card" data-id="<?= $viaje['id'] ?>">
            <h3 class="card-title">
              <?= htmlspecialchars($viaje['origen']) ?> → <?= htmlspecialchars($viaje['destino']) ?>
            </h3>
            <p class="card-text">Fecha: <?= $fecha ?> - <?= $hora ?></p>

            <!-- Aquí mostramos el nombre del conductor que publicó el viaje -->
            <p class="card-text">
              Conductor: <?= htmlspecialchars($viaje['conductor']) ?>
            </p>

            <p class="card-text">Espacios disponibles: <?= $viaje['asientos'] ?></p>
            <p class="card-text">Precio: $<?= number_format($viaje['precio'], 2) ?> MXN</p>
            <button class="btn">Reservar</button>
        </div>
        <?php
    }
} catch (PDOException $e) {
    echo '<p class="text-red-500">Error al cargar viajes: ' . $e->getMessage() . '</p>';
}
?>

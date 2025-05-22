<?php
session_start();
require 'conexion.php';

// 1. Validar sesión
if (!isset($_SESSION['usuario'])) {
    echo '<p>Debes iniciar sesión para ver tus reservas.</p>';
    exit;
}

// 2. Obtener usuario ID
$stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
$stmtUser->execute([$_SESSION['usuario']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo '<p>Usuario no válido.</p>';
    exit;
}
$usuario_id = $user['id'];

// 3. Buscar reserva activa (viaje futuro)
$stmt = $pdo->prepare("
    SELECT r.id as reserva_id, v.*, CONCAT(u.nombres,' ',u.apellidos) AS conductor_nombre
    FROM reservas r
    JOIN viajes v ON r.viaje_id = v.id
    JOIN usuarios u ON v.conductor_id = u.id
    WHERE r.usuario_id = ? AND CONCAT(v.fecha, ' ', v.hora) >= NOW()
    ORDER BY v.fecha ASC, v.hora ASC
    LIMIT 1
");
$stmt->execute([$usuario_id]);
$reserva = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Mostrar
?>
<div class="max-w-xl mx-auto my-6 bg-white rounded shadow p-6">
    <h2 class="text-2xl font-bold mb-4">Mi Reserva Actual</h2>
    <?php if (!$reserva): ?>
        <p class="text-gray-500">No tienes ninguna reserva activa.</p>
    <?php else: ?>
        <div class="mb-4">
            <p><b>Origen:</b> <?= htmlspecialchars($reserva['origen']) ?></p>
            <p><b>Destino:</b> <?= htmlspecialchars($reserva['destino']) ?></p>
            <p><b>Fecha:</b> <?= htmlspecialchars($reserva['fecha']) ?> <?= htmlspecialchars($reserva['hora']) ?></p>
            <p><b>Conductor:</b> <?= htmlspecialchars($reserva['conductor_nombre']) ?></p>
            <p><b>Precio:</b> $<?= number_format($reserva['precio'],2) ?> MXN</p>
        </div>
        <button class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-700 btn-cancelar-reserva" data-reserva="<?= $reserva['reserva_id'] ?>">Cancelar Reserva</button>
    <?php endif; ?>
</div>
<script>
document.querySelectorAll('.btn-cancelar-reserva').forEach(btn=>{
    btn.addEventListener('click',function(){
        if(confirm('¿Estás seguro de cancelar tu reserva?')){
            fetch('cancelar_reserva.php', {
                method:'POST',
                headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body: 'reserva_id=' + this.getAttribute('data-reserva'),
                credentials:'include'
            })
            .then(r=>r.json())
            .then(data=>{
                if(data.success){
                    alert('Reserva cancelada.');
                    location.reload();
                }else{
                    alert(data.error || 'No se pudo cancelar.');
                }
            });
        }
    });
});
</script>

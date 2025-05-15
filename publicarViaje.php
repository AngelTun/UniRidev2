<?php 
session_start();
require 'conexion.php';

// Si no está logueado, redirige a login
if (!isset($_SESSION['usuario'])) {
    header('Location: login.php');
    exit;
}

// Asegurarnos de tener el ID del usuario en sesión
if (!isset($_SESSION['id'])) {
    $stmtUser = $pdo->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmtUser->execute([ $_SESSION['usuario'] ]);
    $userRow = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if (!$userRow) {
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['id'] = $userRow['id'];
}

$conductor_id = $_SESSION['id'];
$errores     = [];
$mensajeExito = '';

// Procesar envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $origen   = filter_input(INPUT_POST, 'origen',   FILTER_SANITIZE_STRING);
    $destino  = filter_input(INPUT_POST, 'destino',  FILTER_SANITIZE_STRING);
    $fecha    = filter_input(INPUT_POST, 'fecha',    FILTER_SANITIZE_STRING);
    $hora     = filter_input(INPUT_POST, 'hora',     FILTER_SANITIZE_STRING);
    $asientos = filter_input(INPUT_POST, 'asientos', FILTER_VALIDATE_INT);
    $precio   = filter_input(INPUT_POST, 'precio',   FILTER_VALIDATE_FLOAT);
    $detalles = filter_input(INPUT_POST, 'detalles', FILTER_SANITIZE_STRING);

    // Validaciones
    if (!$origen)  $errores[] = "El origen es requerido";
    if (!$destino) $errores[] = "El destino es requerido";
    if (!$fecha)   $errores[] = "La fecha es requerida";
    if (!$hora)    $errores[] = "La hora es requerida";
    if (!$asientos || $asientos < 1) $errores[] = "Los asientos deben ser mayor a 0";
    if (!$precio || $precio < 0)     $errores[] = "El precio no es válido";

    if (empty($errores)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO viajes 
                (conductor_id, origen, destino, fecha, hora, asientos, precio, detalles, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $conductor_id,
                $origen,
                $destino,
                $fecha,
                $hora,
                $asientos,
                $precio,
                $detalles
            ]);
            $mensajeExito = "¡Viaje publicado exitosamente!";
        } catch (PDOException $e) {
            $errores[] = "Error al guardar el viaje: " . $e->getMessage();
        }
    }
}

// Si es petición AJAX, devolvemos solo las alertas
if (
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {
    if ($mensajeExito) {
        echo '<div id="mensajeExito" class="success-alert">'
           .  htmlspecialchars($mensajeExito) .
             '</div>';
    } elseif (!empty($errores)) {
        echo '<div id="mensajeError" class="error-alert">';
        foreach ($errores as $error) {
            echo '<p>' . htmlspecialchars($error) . '</p>';
        }
        echo '</div>';
    }
    exit;
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="publicar_viaje.css" />

<!-- CONTENEDOR DE ALERTAS -->
<div id="msgContainerPublicar"></div>

<!-- FORMULARIO -->
<div class="publicar-viaje-container">
    <h2 class="section-title">Publicar Nuevo Viaje</h2>
    <form id="formPublicarViaje" method="POST" class="viaje-form" autocomplete="off">
        <div class="form-grid">
            <div class="input-group">
                <label for="origen">Origen:</label>
                <input type="text" id="origen" name="origen" required class="input-field" placeholder="Selecciona o escribe el origen">
            </div>
            <div class="input-group">
                <label for="destino">Destino:</label>
                <input type="text" id="destino" name="destino" required class="input-field" placeholder="Selecciona o escribe el destino">
            </div>
            <div class="input-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required class="input-field" min="<?= date('Y-m-d') ?>">
            </div>
            <div class="input-group">
                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" required class="input-field">
            </div>
            <div class="input-group">
                <label for="asientos">Asientos disponibles:</label>
                <input type="number" id="asientos" name="asientos" required class="input-field" min="1" max="6" value="1">
            </div>
            <div class="input-group">
                <label for="precio">Precio por asiento:</label>
                <div class="price-input">
                    <span class="currency">$</span>
                    <input type="number" id="precio" name="precio" required class="input-field" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
        </div>
        <div class="input-group full-width">
            <label for="detalles">Detalles adicionales:</label>
            <textarea id="detalles" name="detalles" class="input-field textarea-field"
                placeholder="Ejemplo: Punto de encuentro, paradas, normas del viaje..."></textarea>
        </div>
        <!-- Mapa para selección de origen y destino -->
        <div style="height: 350px; margin-bottom: 20px;">
            <div id="mapaViaje" style="width: 100%; height: 100%; border-radius: 8px;"></div>
        </div>
        <button type="submit" class="submit-btn">Publicar Viaje</button>
    </form>
</div>

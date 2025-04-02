<?php
session_start();
require_once 'conexion.php';

header('Content-Type: application/json; charset=utf-8');

// Validación de sesión más robusta
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado', 'code' => 401]);
    exit;
}

$correo_usuario = $_SESSION['usuario'];

// Determine if this is a request for unread message counts or new messages in a chat
$isChatRequest = isset($_GET['contacto']);

if ($isChatRequest) {
    // Handle chat message request (new functionality)
    $destinatario_actual = filter_input(INPUT_GET, 'contacto', FILTER_SANITIZE_EMAIL);
    $ultimo_id = filter_input(INPUT_GET, 'ultimo_id', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 0]]);

    // Validación más estricta del correo
    if (!filter_var($destinatario_actual, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Contacto no válido', 'code' => 400]);
        exit;
    }

    try {
        // Obtener nuevos mensajes con información adicional
        $stmt = $pdo->prepare("
            SELECT 
                id, 
                remitente, 
                mensaje, 
                DATE_FORMAT(fecha, '%H:%i') as hora,
                leido
            FROM mensajes
            WHERE (
                (remitente = :usuario AND destinatario = :contacto) OR 
                (remitente = :contacto AND destinatario = :usuario)
            )
            AND id > :ultimo_id
            ORDER BY fecha ASC
        ");
        
        $stmt->execute([
            ':usuario' => $correo_usuario,
            ':contacto' => $destinatario_actual,
            ':ultimo_id' => $ultimo_id
        ]);
        
        $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Marcar mensajes como leídos si el chat está abierto (solo los del otro usuario)
        if (!empty($mensajes)) {
            $mensajes_no_leidos = array_filter($mensajes, function($msg) use ($correo_usuario) {
                return $msg['remitente'] !== $correo_usuario && $msg['leido'] == 0;
            });
            
            if (!empty($mensajes_no_leidos)) {
                $ids = array_column($mensajes_no_leidos, 'id');
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                
                $update_stmt = $pdo->prepare("
                    UPDATE mensajes 
                    SET leido = 1, 
                        fecha_leido = CURRENT_TIMESTAMP 
                    WHERE id IN ($placeholders) 
                    AND destinatario = ?
                ");
                $update_stmt->execute(array_merge($ids, [$correo_usuario]));
                
                // Actualizar el estado 'leido' en los mensajes devueltos
                foreach ($mensajes as &$msg) {
                    if (in_array($msg['id'], $ids)) {
                        $msg['leido'] = 1;
                    }
                }
            }
        }

        // Registrar la actividad del usuario para sincronización entre dispositivos
        $stmt = $pdo->prepare("
            INSERT INTO actividad_usuario (correo, ultimo_contacto, ultima_actividad) 
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE 
                ultimo_contacto = VALUES(ultimo_contacto),
                ultima_actividad = VALUES(ultima_actividad)
        ");
        $stmt->execute([$correo_usuario, $destinatario_actual]);

        echo json_encode([
            'success' => true,
            'mensajes' => $mensajes,
            'ultimo_id' => !empty($mensajes) ? end($mensajes)['id'] : $ultimo_id
        ]);

    } catch (PDOException $e) {
        error_log('Error en obtener_nuevos_mensajes.php: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error en el servidor',
            'code' => 500
        ]);
    }
} else {
    // Handle original unread message counts request
    try {
        $stmt = $pdo->prepare("
            SELECT 
                u.correo,
                CONCAT(u.nombres, ' ', u.apellidos) as nombre,
                COUNT(m.id) as count
            FROM mensajes m
            JOIN usuarios u ON m.remitente = u.correo
            WHERE m.destinatario = ? AND m.leido = 0
            GROUP BY u.correo, u.nombres, u.apellidos
        ");
        $stmt->execute([$correo_usuario]);
        $mensajes_no_leidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'mensajes' => $mensajes_no_leidos
        ]);
    } catch (PDOException $e) {
        error_log('Error en obtener_mensajes_no_leidos.php: ' . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Error en el servidor',
            'code' => 500
        ]);
    }
}
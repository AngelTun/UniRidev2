<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) { 
    echo "<script>
        alert('Debes iniciar sesión para acceder a los mensajes');
        window.location.href = 'iniciosesion.php';
    </script>";
    exit;
}

require_once 'conexion.php';

// Verificar sesión única
$correo_usuario = $_SESSION['usuario'];
$stmt = $pdo->prepare("SELECT session_id FROM usuarios WHERE correo = ?");
$stmt->execute([$correo_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario && $usuario['session_id'] !== session_id()) {
    echo "<script>
        alert('Ya hay una sesión activa con este usuario en otro dispositivo.');
        window.location.href = 'cerrarsesion.php';
    </script>";
    exit;
}

// Obtener lista de contactos con conteo de mensajes no leídos (del primer código)
$stmt = $pdo->prepare("
    SELECT 
        u.id, 
        u.nombres, 
        u.apellidos, 
        u.correo,
        COUNT(m.id) as mensajes_no_leidos
    FROM usuarios u
    LEFT JOIN mensajes m ON (
        m.remitente = u.correo 
        AND m.destinatario = ? 
        AND m.leido = 0
    )
    WHERE u.correo != ?
    GROUP BY u.id, u.nombres, u.apellidos, u.correo
    ORDER BY u.nombres
");
$stmt->execute([$correo_usuario, $correo_usuario]);
$contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener destinatario actual con sanitización (del primer código)
$destinatario_actual = isset($_GET['contacto']) ? filter_var($_GET['contacto'], FILTER_SANITIZE_EMAIL) : "";
?>

<div class="messages-container">
    <div class="contacts-list">
        <h3>Contactos</h3>
        <div class="search-box">
            <input type="text" placeholder="Buscar contacto..." id="searchContact">
        </div>
        <ul id="contactsList">
            <?php foreach ($contactos as $contacto): ?>
                <li class="contact-item <?= ($contacto['correo'] == $destinatario_actual) ? 'active' : '' ?>" 
                    data-email="<?= htmlspecialchars($contacto['correo']) ?>">
                    <a href="#" class="contact-link">
                        <div class="contact-avatar">
                            <?= strtoupper(substr($contacto['nombres'], 0, 1)) ?>
                        </div>
                        <div class="contact-info">
                            <span class="contact-name">
                                <?= htmlspecialchars($contacto['nombres'] . ' ' . $contacto['apellidos']) ?>
                            </span>
                            <span class="contact-email">
                                <?= htmlspecialchars($contacto['correo']) ?>
                            </span>
                        </div>
                        <?php if ($contacto['mensajes_no_leidos'] > 0): ?>
                            <span class="contact-badge"><?= $contacto['mensajes_no_leidos'] ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <div class="chat-container" id="chatContainer">
        <?php if (!empty($destinatario_actual)): ?>
            <div class="loading-chat">
                <div class="spinner"></div>
                <p>Cargando conversación...</p>
            </div>
            <script>
                // Combinación de ambos enfoques
                if (typeof loadChat === 'function') {
                    loadChat('<?= $destinatario_actual ?>');
                    
                    // Marcado como leído del primer código
                    fetch('marcar_como_leido.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'contacto=<?= urlencode($destinatario_actual) ?>',
                        credentials: 'include'
                    });
                }
            </script>
        <?php else: ?>
            <div class="no-chat-selected">
                <div class="icon">💬</div>
                <h4>Selecciona un contacto para comenzar a chatear</h4>
                <p>Elige un contacto de la lista para ver la conversación</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Función combinada para cargar el chat
function loadChat(email) {
    window.currentChatEmail = email;
    const chatContainer = document.getElementById('chatContainer');
    
    // Mostrar estado de carga (de ambos códigos)
    chatContainer.innerHTML = `
        <div class="loading-chat">
            <div class="spinner"></div>
            <p>Cargando conversación...</p>
        </div>`;

    fetch(`obtener_conversacion.php?contacto=${encodeURIComponent(email)}`, {
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) throw new Error('Error en la respuesta');
        return response.text();
    })
    .then(html => {
        chatContainer.innerHTML = html;
        
        // Funcionalidades del primer código
        if (typeof scrollToBottom === 'function') scrollToBottom();
        if (typeof startPolling === 'function') startPolling(email);
        if (typeof updateContactBadge === 'function') updateContactBadge(email, 0);
    })
    .catch(error => {
        // Manejo de errores mejorado
        chatContainer.innerHTML = `
            <div class="error-chat">
                <p>Error al cargar la conversación</p>
                <button onclick="location.reload()">Reintentar</button>
            </div>`;
        console.error('Error:', error);
    });
}

// Función auxiliar para actualizar badges (del primer código)
function updateContactBadge(email, count) {
    const contactItem = document.querySelector(`.contact-item[data-email="${email}"]`);
    if (contactItem) {
        const badge = contactItem.querySelector('.contact-badge');
        if (count > 0) {
            if (!badge) {
                const newBadge = document.createElement('span');
                newBadge.className = 'contact-badge';
                newBadge.textContent = count;
                contactItem.querySelector('.contact-link').appendChild(newBadge);
            } else {
                badge.textContent = count;
            }
        } else if (badge) {
            badge.remove();
        }
    }
}
</script>
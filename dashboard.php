<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Compartir Viajes</title>
  <!-- TailwindCSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- API de Google Maps -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB-VtkPeG2cL2SjoAIufnNf39U-RA0qQRc"></script>
  <!-- Nuestro CSS personalizado -->
  <link rel="stylesheet" href="dashboard.css" />
  <link rel="stylesheet" href="perfil.css" />
  <link rel="stylesheet" href="mensajes.css" />
  <link rel="stylesheet" href="seguridad.css" />
  <link rel="stylesheet" href="cerrarsesion.css" />
  <style>
    /* Estilos para notificaciones */
    .notification-badge {
      position: absolute;
      top: -5px;
      right: -5px;
      background-color: #ef4444;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: bold;
    }
    .contact-badge {
      background-color: #ef4444;
      color: white;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 10px;
      font-weight: bold;
      margin-left: auto;
    }
    .sidebar-link {
      position: relative;
    }
    /* Animaciones para mensajes */
    .message.sent {
      animation: messageSent 0.3s ease-out;
    }
    .message.received {
      animation: messageReceived 0.3s ease-out;
    }
    @keyframes messageSent {
      from { transform: translateX(10px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes messageReceived {
      from { transform: translateX(-10px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    /* Indicador de "escribiendo" */
    .typing-indicator {
      display: flex;
      padding: 8px;
      margin: 5px 0;
    }
    .typing-dot {
      width: 8px;
      height: 8px;
      background-color: #9ca3af;
      border-radius: 50%;
      margin: 0 2px;
      animation: typingAnimation 1.4s infinite ease-in-out;
    }
    .typing-dot:nth-child(1) { animation-delay: 0s; }
    .typing-dot:nth-child(2) { animation-delay: 0.2s; }
    .typing-dot:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typingAnimation {
      0%, 60%, 100% { transform: translateY(0); }
      30% { transform: translateY(-5px); }
    }
  </style>
  <script>
    // Variables globales para el sistema de mensajes
    window.messagePollingInterval = null;
    window.globalPollingInterval = null;
    window.currentChatEmail = '';
    window.unreadMessages = 0;
    window.lastNotificationTime = 0;
    window.unreadCounts = {};

    // Inicializa el mapa para la sección "Inicio"
    function initMap() {
      var map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 20.9671, lng: -89.6236 },
        zoom: 12
      });
      var marker = new google.maps.Marker({
        position: { lat: 20.9671, lng: -89.6236 },
        map: map,
        title: "Ubicación del Conductor"
      });
    }

    // Función para cambiar de sección y cargar contenido externo si es necesario
    function changeSection(page) {
      // Limpiar intervalos de mensajes si existen
      if (window.messagePollingInterval) {
        clearInterval(window.messagePollingInterval);
        window.messagePollingInterval = null;
      }
      
      // Quitar la clase 'active' de todos los enlaces
      document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
      });
      // Agregar 'active' al enlace seleccionado
      document.querySelector(`.sidebar-link[data-page="${page}"]`).classList.add('active');

      if(page === "Inicio") {
        document.getElementById('contentInicio').classList.remove('hidden');
        document.getElementById('contentContainer').classList.add('hidden');
        initMap();
      } else {
        document.getElementById('contentInicio').classList.add('hidden');
        fetch(page + ".php", { credentials: 'include' })
          .then(response => response.text())
          .then(data => {
            document.getElementById('contentContainer').innerHTML = data;
            document.getElementById('contentContainer').classList.remove('hidden');
            
            if (page === "mensajes") {
              initializeMessages();
            }
          })
          .catch(error => {
            document.getElementById('contentContainer').innerHTML = "<p>Error al cargar el contenido.</p>";
            document.getElementById('contentContainer').classList.remove('hidden');
          });
      }
    }

    // ==================== FUNCIONES GLOBALES ====================
    function startGlobalMessagePolling() {
      // Detener polling anterior si existe
      if (window.globalPollingInterval) {
        clearInterval(window.globalPollingInterval);
      }
      
      // Verificar inmediatamente
      checkUnreadMessages();
      
      // Configurar intervalo para verificar cada 5 segundos
      window.globalPollingInterval = setInterval(checkUnreadMessages, 5000);
    }

    function checkUnreadMessages() {
      fetch('obtener_mensajes_no_leidos.php', { credentials: 'include' })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            updateMessageBadge(data.mensajes);
            window.unreadCounts = {};
            data.mensajes.forEach(msg => {
              window.unreadCounts[msg.correo] = msg.count;
              // Actualizar badges de contactos solo si estamos en mensajes
              if (document.querySelector('.sidebar-link[data-page="mensajes"]').classList.contains('active')) {
                updateContactBadge(msg.correo, msg.count);
              }
            });
          }
        })
        .catch(error => console.error('Error al verificar mensajes no leídos:', error));
    }

    function updateMessageBadge(mensajes) {
      const mensajesLink = document.querySelector('.sidebar-link[data-page="mensajes"]');
      if (!mensajesLink) return;
      
      const badge = mensajesLink.querySelector('.notification-badge') || document.createElement('span');
      
      window.unreadMessages = mensajes.reduce((total, msg) => {
        return total + msg.count;
      }, 0);
      
      if (window.unreadMessages > 0) {
        badge.className = 'notification-badge';
        badge.textContent = window.unreadMessages > 9 ? '9+' : window.unreadMessages;
        if (!mensajesLink.contains(badge)) {
          mensajesLink.appendChild(badge);
        }
      } else if (badge.parentNode) {
        badge.remove();
      }
    }

    // ==================== SECCIÓN DE MENSAJES ====================
    function initializeMessages() {
      let currentChatEmail = '';

      function loadChat(email) {
        currentChatEmail = email;
        window.currentChatEmail = email;
        const chatContainer = document.getElementById('chatContainer');
        
        fetch(`obtener_conversacion.php?contacto=${encodeURIComponent(email)}`, {
          credentials: 'include'
        })
        .then(response => response.text())
        .then(html => {
          chatContainer.innerHTML = html;
          setupChatForm();
          scrollToBottom();
          startMessagePolling(email);
          markMessagesAsRead(email);
          updateContactBadge(email, 0);
        })
        .catch(error => {
          chatContainer.innerHTML = `
            <div class="error-chat">
              <p>Error al cargar la conversación</p>
              <button onclick="location.reload()">Reintentar</button>
            </div>
          `;
        });
      }

      function updateContactBadge(email, count) {
        const contactItem = document.querySelector(`.contact-item[data-email="${email}"]`);
        if (!contactItem) return;
        
        let badge = contactItem.querySelector('.contact-badge');
        
        if (count > 0) {
          if (!badge) {
            badge = document.createElement('span');
            badge.className = 'contact-badge';
            contactItem.appendChild(badge);
          }
          badge.textContent = count > 9 ? '9+' : count;
        } else if (badge) {
          badge.remove();
        }
      }

      function setupChatForm() {
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
          chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage(this);
          });
        }
      }

      function sendMessage(form) {
        const formData = new FormData(form);
        const chatMessages = document.getElementById('chatMessages');
        
        fetch('enviar_mensaje.php', {
          method: 'POST',
          body: formData,
          credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message sent';
            messageDiv.dataset.messageId = data.id;
            messageDiv.innerHTML = `
              <div class="message-content">${data.mensaje}</div>
              <div class="message-time">${data.hora}</div>
            `;
            chatMessages.appendChild(messageDiv);
            form.reset();
            scrollToBottom();
            checkNewMessages(currentChatEmail);
          } else {
            alert('Error: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error al enviar el mensaje');
        });
      }

      function scrollToBottom() {
        const chatMessages = document.getElementById('chatMessages');
        if (chatMessages) {
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }
      }

      function startMessagePolling(email) {
        stopMessagePolling();
        checkNewMessages(email);
        window.messagePollingInterval = setInterval(() => {
          checkNewMessages(email);
        }, 3000);
      }

      function stopMessagePolling() {
        if (window.messagePollingInterval) {
          clearInterval(window.messagePollingInterval);
          window.messagePollingInterval = null;
        }
      }

      function checkNewMessages(email) {
        if (!email) return;
        
        const chatMessages = document.getElementById('chatMessages');
        if (!chatMessages) return;
        
        const lastMessage = chatMessages.querySelector('.message:last-child');
        const lastMessageId = lastMessage ? lastMessage.dataset.messageId : 0;
        
        fetch(`obtener_nuevos_mensajes.php?contacto=${encodeURIComponent(email)}&ultimo_id=${lastMessageId}`, {
          credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
          if (data.success && data.mensajes && data.mensajes.length > 0) {
            let hasNewMessages = false;
            
            data.mensajes.forEach(msg => {
              if (!document.querySelector(`.message[data-message-id="${msg.id}"]`)) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${msg.remitente === '<?= $_SESSION['usuario'] ?? '' ?>' ? 'sent' : 'received'}`;
                messageDiv.dataset.messageId = msg.id;
                messageDiv.innerHTML = `
                  <div class="message-content">${msg.mensaje}</div>
                  <div class="message-time">${msg.hora}</div>
                `;
                chatMessages.appendChild(messageDiv);
                hasNewMessages = true;
                
                if (email !== currentChatEmail || !document.hasFocus()) {
                  const now = Date.now();
                  if (now - window.lastNotificationTime > 2000) {
                    showDesktopNotification(msg.remitente, msg.mensaje);
                    window.lastNotificationTime = now;
                  }
                }
              }
            });
            
            if (hasNewMessages) {
              scrollToBottom();
              if (email === currentChatEmail) {
                markMessagesAsRead(email);
              } else {
                checkUnreadMessages();
              }
            }
          }
        })
        .catch(error => console.error('Error al verificar nuevos mensajes:', error));
      }

      function markMessagesAsRead(contactEmail) {
        fetch('marcar_como_leido.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `contacto=${encodeURIComponent(contactEmail)}`,
          credentials: 'include'
        }).then(() => {
          checkUnreadMessages();
          updateContactBadge(contactEmail, 0);
        });
      }

      function showDesktopNotification(remitente, mensaje) {
        if (!("Notification" in window)) return;
        
        if (Notification.permission === "granted") {
          new Notification(`Nuevo mensaje de ${remitente}`, {
            body: mensaje.length > 50 ? mensaje.substring(0, 50) + '...' : mensaje,
            icon: 'Images/notification-icon.png'
          });
        } 
        else if (Notification.permission !== "denied") {
          Notification.requestPermission().then(permission => {
            if (permission === "granted") {
              new Notification(`Nuevo mensaje de ${remitente}`, {
                body: mensaje,
                icon: 'Images/notification-icon.png'
              });
            }
          });
        }
      }

      function setupMessagesEvents() {
        const contactsList = document.getElementById('contactsList');
        if (contactsList) {
          contactsList.addEventListener('click', function(e) {
            if (e.target.closest('.contact-link')) {
              e.preventDefault();
              const contacto = e.target.closest('.contact-item');
              const email = contacto.getAttribute('data-email');
              
              document.querySelectorAll('.contact-item').forEach(li => {
                li.classList.remove('active');
              });
              contacto.classList.add('active');
              
              loadChat(email);
            }
          });
        }

        const searchInput = document.getElementById('searchContact');
        if (searchInput) {
          searchInput.addEventListener('input', function() {
            const term = this.value.toLowerCase();
            document.querySelectorAll('.contact-item').forEach(item => {
              const name = item.querySelector('.contact-name').textContent.toLowerCase();
              const email = item.querySelector('.contact-email').textContent.toLowerCase();
              item.style.display = (name.includes(term) || email.includes(term)) ? 'flex' : 'none';
            });
          });
        }
      }

      setupMessagesEvents();

      const initialContact = document.querySelector('.contact-item.active');
      if (initialContact) {
        const email = initialContact.getAttribute('data-email');
        loadChat(email);
      }
    }

    // Al cargar la página
    window.addEventListener("DOMContentLoaded", function() {
      changeSection("Inicio");
      
      if ("Notification" in window) {
        Notification.requestPermission();
      }

      // Iniciar polling global de mensajes
      startGlobalMessagePolling();
    });
  </script>
</head>
<body class="bg-gray-100">
  <!-- Encabezado fijo -->
  <header class="header fixed top-0 left-0 right-0 z-50 bg-white shadow-md">
    <div class="container mx-auto flex items-center px-4 py-2">
      <button id="toggleSidebar" class="toggle-btn">☰</button>
      <div class="logo-container ml-4">
        <img src="Images/image.png" alt="Logo" class="logo" />
        <span class="logo-text">UniRide</span>
      </div>
    </div>
  </header>

  <!-- Contenedor principal -->
  <div class="flex h-screen pt-[60px]">
    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-[60px] h-full transition-transform duration-300 ease-in-out">
      <nav>
        <ul>
          <li>
            <a href="#" data-page="Inicio" class="sidebar-link active" onclick="changeSection('Inicio'); return false;">Inicio</a>
          </li>
          <li>
            <a href="#" data-page="misViajes" class="sidebar-link" onclick="changeSection('misViajes'); return false;">Mis Viajes</a>
          </li>
          <li>
            <a href="#" data-page="publicarViaje" class="sidebar-link" onclick="changeSection('publicarViaje'); return false;">Publicar Viaje</a>
          </li>
          <li>
            <a href="#" data-page="mensajes" class="sidebar-link" onclick="changeSection('mensajes'); return false;">
              Mensajes
            </a>
          </li>
          <li>
            <a href="#" data-page="perfil" class="sidebar-link" onclick="changeSection('perfil'); return false;">Perfil</a>
          </li>
          <li>
            <a href="#" data-page="seguridad" class="sidebar-link" onclick="changeSection('seguridad'); return false;">Seguridad</a>
          </li>
          <li>
            <a href="#" data-page="cerrarSesion" class="sidebar-link" onclick="changeSection('cerrarSesion'); return false;">Cerrar Sesión</a>
          </li>
        </ul>
      </nav>
    </aside>

    <!-- Contenido Principal -->
    <main id="mainContent" class="main-content ml-[200px] transition-all duration-300">
      <!-- Sección integrada "Inicio" -->
      <div id="contentInicio" class="content-section">
        <h2 class="main-title">Viajes Disponibles</h2>
        <div class="grid-container">
          <div class="card">
            <h3 class="card-title">Mérida → Progreso</h3>
            <p class="card-text">Fecha: 22 Feb 2025 - 10:00 AM</p>
            <p class="card-text">Conductor: Juan Pérez</p>
            <p class="card-text">Espacios disponibles: 2</p>
            <button class="btn">Reservar</button>
          </div>
          <div class="card">
            <h3 class="card-title">Centro → Universidad</h3>
            <p class="card-text">Fecha: 22 Feb 2025 - 7:30 AM</p>
            <p class="card-text">Conductor: Ana López</p>
            <p class="card-text">Espacios disponibles: 3</p>
            <button class="btn">Reservar</button>
          </div>
          <div class="card">
            <h3 class="card-title">Universidad → Plaza Mayor</h3>
            <p class="card-text">Fecha: 22 Feb 2025 - 5:00 PM</p>
            <p class="card-text">Conductor: Carlos Méndez</p>
            <p class="card-text">Espacios disponibles: 1</p>
            <button class="btn">Reservar</button>
          </div>
        </div>
        <h2 class="main-title mt-8">Seguimiento del Conductor</h2>
        <div id="map" class="map-container" style="height: 400px;"></div>
      </div>
      
      <!-- Contenedor para contenido dinámico -->
      <div id="contentContainer" class="content-section hidden"></div>
    </main>
  </div>

  <!-- Script para el toggle del sidebar -->
  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function () {
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('mainContent');
      sidebar.classList.toggle('sidebar-hidden');
      mainContent.classList.toggle('expanded');
    });

    function enviarFormularioPerfil() {
      const form = document.getElementById('formPerfil');
      const formData = new FormData(form);

      fetch('perfil.php', {
          method: 'POST',
          body: formData,
          credentials: 'include'
      })
      .then(response => response.text())
      .then(data => {
          document.getElementById('contentContainer').innerHTML = data;
          const mensajeExito = document.getElementById('mensajeExito');
          const mensajeError = document.getElementById('mensajeError');
          if (mensajeExito) setTimeout(() => mensajeExito.remove(), 5000);
          if (mensajeError) setTimeout(() => mensajeError.remove(), 5000);
      })
      .catch(error => console.error("Error en la solicitud fetch:", error));
    }

    function enviarFormularioSeguridad() {
      const form = document.getElementById('formSeguridad');
      const formData = new FormData(form);

      fetch('seguridad.php', {
          method: 'POST',
          body: formData,
          credentials: 'include'
      })
      .then(response => response.text())
      .then(data => {
          document.getElementById('msgContainer').innerHTML = data;
          if (data.includes('mensajeExito')) form.reset();
          setTimeout(() => {
              const mensaje = document.getElementById('mensajeExito') || document.getElementById('mensajeError');
              if (mensaje) mensaje.remove();
          }, 5000);
      })
      .catch(error => console.error("Error en la solicitud fetch:", error));
    }

    document.addEventListener('click', function(e) {
      if (e.target && e.target.classList.contains('toggle-password')) {
        const targetId = e.target.getAttribute('data-target');
        const passwordInput = document.getElementById(targetId);
        if (passwordInput) {
          if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            e.target.textContent = '🔒';
          } else {
            passwordInput.type = 'password';
            e.target.textContent = '👁️';
          }
        }
      }
    });

    document.addEventListener('input', function(e) {
      if (e.target && e.target.id === 'new_password') {
        const password = e.target.value;
        const hasUpperCase = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const isValid = password.length >= 8 && hasUpperCase && hasNumber;
        if (password.length > 0) {
          e.target.style.borderColor = isValid ? '#27ae60' : '#e74c3c';
        } else {
          e.target.style.borderColor = '#ddd';
        }
      }
    });
  </script>
</body>
</html>
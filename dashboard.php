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
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB-VtkPeG2cL2SjoAIufnNf39U-RA0qQRc&libraries=places"></script>
  <!-- Nuestro CSS personalizado -->
  <link rel="stylesheet" href="dashboard.css" />
  <link rel="stylesheet" href="perfil.css" />
  <link rel="stylesheet" href="mensajes.css" />
  <link rel="stylesheet" href="seguridad.css" />
  <link rel="stylesheet" href="cerrarsesion.css" />
  <link rel="stylesheet" href="publicar_viaje.css" />
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
    /* Estilo para mensajes de publicar viaje */
    #msgContainerPublicar {
      margin-bottom: 1.5rem;
    }
  </style>
  <script>
    // Variables globales para mensajes y polling
    window.messagePollingInterval = null;
    window.globalPollingInterval = null;
    window.currentChatEmail = '';
    window.unreadMessages = 0;
    window.lastNotificationTime = 0;
    window.unreadCounts = {};
    let isSubmitting = false;

    // Inicializa mapa en "Inicio"
    function initMap() {
      var map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 20.9671, lng: -89.6236 },
        zoom: 12
      });
      new google.maps.Marker({
        position: { lat: 20.9671, lng: -89.6236 },
        map: map,
        title: "Ubicación del Conductor"
      });
    }

    // Cambia de sección y carga PHP correspondiente
    function changeSection(page) {
      if (window.messagePollingInterval) {
        clearInterval(window.messagePollingInterval);
        window.messagePollingInterval = null;
      }
      document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
      document.querySelector(`.sidebar-link[data-page="${page}"]`).classList.add('active');

      if (page === 'Inicio') {
        document.getElementById('contentInicio').classList.remove('hidden');
        document.getElementById('contentContainer').classList.add('hidden');
        initMap();
      } else {
        document.getElementById('contentInicio').classList.add('hidden');
        fetch(page + '.php', { credentials: 'include' })
          .then(r => r.text())
          .then(html => {
            document.getElementById('contentContainer').innerHTML = html;
            document.getElementById('contentContainer').classList.remove('hidden');
            if (page === 'mensajes') initializeMessages();
            if (page === 'publicarViaje') setupPublicarViaje();
          })
          .catch(() => {
            document.getElementById('contentContainer').innerHTML = '<p>Error al cargar el contenido.</p>';
            document.getElementById('contentContainer').classList.remove('hidden');
          });
      }
    }

    // Polling global de mensajes no leídos
    function startGlobalMessagePolling() {
      if (window.globalPollingInterval) clearInterval(window.globalPollingInterval);
      checkUnreadMessages();
      window.globalPollingInterval = setInterval(checkUnreadMessages, 5000);
    }

    function checkUnreadMessages() {
      fetch('obtener_mensajes_no_leidos.php', { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            updateMessageBadge(data.mensajes);
            window.unreadCounts = {};
            data.mensajes.forEach(m => {
              window.unreadCounts[m.correo] = m.count;
              if (document.querySelector('.sidebar-link[data-page="mensajes"]').classList.contains('active')) {
                updateContactBadge(m.correo, m.count);
              }
            });
          }
        })
        .catch(console.error);
    }

    function updateMessageBadge(mensajes) {
      const link = document.querySelector('.sidebar-link[data-page="mensajes"]');
      if (!link) return;
      let badge = link.querySelector('.notification-badge') || document.createElement('span');
      window.unreadMessages = mensajes.reduce((t, m) => t + m.count, 0);
      if (window.unreadMessages > 0) {
        badge.className = 'notification-badge';
        badge.textContent = window.unreadMessages > 9 ? '9+' : window.unreadMessages;
        if (!link.contains(badge)) link.appendChild(badge);
      } else if (badge.parentNode) badge.remove();
    }

    // ==================== SECCIÓN MENSAJES ====================
    function initializeMessages() {
      let currentChatEmail = '';
      function loadChat(email) {
        currentChatEmail = email;
        window.currentChatEmail = email;
        const chatContainer = document.getElementById('chatContainer');
        fetch(`obtener_conversacion.php?contacto=${encodeURIComponent(email)}`, { credentials: 'include' })
          .then(r => r.text())
          .then(html => {
            chatContainer.innerHTML = html;
            setupChatForm();
            scrollToBottom();
            startMessagePolling(email);
            markMessagesAsRead(email);
            updateContactBadge(email, 0);
          })
          .catch(() => {
            chatContainer.innerHTML = '<div class="error-chat"><p>Error al cargar la conversación</p><button onclick="location.reload()">Reintentar</button></div>';
          });
      }

      function updateContactBadge(email, count) {
        const item = document.querySelector(`.contact-item[data-email="${email}"]`);
        if (!item) return;
        let b = item.querySelector('.contact-badge');
        if (count > 0) {
          if (!b) { b = document.createElement('span'); b.className = 'contact-badge'; item.appendChild(b); }
          b.textContent = count > 9 ? '9+' : count;
        } else if (b) b.remove();
      }

      function setupChatForm() {
        const f = document.getElementById('chatForm');
        if (f) f.addEventListener('submit', e => { e.preventDefault(); sendMessage(f); });
      }

      function sendMessage(form) {
        const fd = new FormData(form), chatMessages = document.getElementById('chatMessages');
        fetch('enviar_mensaje.php', { method: 'POST', body: fd, credentials: 'include' })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              const div = document.createElement('div');
              div.className = 'message sent';
              div.dataset.messageId = data.id;
              div.innerHTML = `<div class="message-content">${data.mensaje}</div><div class="message-time">${data.hora}</div>`;
              chatMessages.appendChild(div);
              form.reset(); scrollToBottom(); checkNewMessages(currentChatEmail);
            } else alert('Error: ' + data.error);
          })
          .catch(() => alert('Error al enviar el mensaje'));
      }

      function scrollToBottom() {
        const cm = document.getElementById('chatMessages');
        if (cm) cm.scrollTop = cm.scrollHeight;
      }

      function startMessagePolling(email) {
        stopMessagePolling(); checkNewMessages(email);
        window.messagePollingInterval = setInterval(() => checkNewMessages(email), 3000);
      }

      function stopMessagePolling() {
        if (window.messagePollingInterval) { clearInterval(window.messagePollingInterval); window.messagePollingInterval = null; }
      }

      function checkNewMessages(email) {
        if (!email) return;
        const cm = document.getElementById('chatMessages');
        const last = cm.querySelector('.message:last-child');
        const lastId = last ? last.dataset.messageId : 0;
        fetch(`obtener_nuevos_mensajes.php?contacto=${encodeURIComponent(email)}&ultimo_id=${lastId}`, { credentials: 'include' })
          .then(r => r.json())
          .then(data => {
            if (data.success && data.mensajes.length) {
              let newFlag = false;
              data.mensajes.forEach(msg => {
                if (!document.querySelector(`.message[data-message-id="${msg.id}"]`)) {
                  const d = document.createElement('div');
                  d.className = `message ${msg.remitente===('<?= $_SESSION['usuario']??'' ?>')?'sent':'received'}`;
                  d.dataset.messageId = msg.id;
                  d.innerHTML = `<div class="message-content">${msg.mensaje}</div><div class="message-time">${msg.hora}</div>`;
                  cm.appendChild(d); newFlag = true;
                  if (email!==currentChatEmail||!document.hasFocus()) {
                    const now = Date.now();
                    if (now-window.lastNotificationTime>2000) {
                      showDesktopNotification(msg.remitente,msg.mensaje);
                      window.lastNotificationTime = now;
                    }
                  }
                }
              });
              if (newFlag) {
                scrollToBottom();
                if (email===currentChatEmail) markMessagesAsRead(email);
                else checkUnreadMessages();
              }
            }
          })
          .catch(console.error);
      }

      function markMessagesAsRead(email) {
        fetch('marcar_como_leido.php', {
          method:'POST',
          headers:{'Content-Type':'application/x-www-form-urlencoded'},
          body:`contacto=${encodeURIComponent(email)}`,
          credentials:'include'
        }).then(()=>{ checkUnreadMessages(); updateContactBadge(email,0); });
      }

      function showDesktopNotification(remitente,mensaje) {
        if (!('Notification' in window)) return;
        if (Notification.permission==='granted') {
          new Notification(`Nuevo mensaje de ${remitente}`, {
            body:mensaje.length>50?mensaje.substring(0,50)+'...':mensaje,
            icon:'Images/notification-icon.png'
          });
        } else if (Notification.permission!=='denied') {
          Notification.requestPermission().then(p=>p==='granted'&&new Notification(`Nuevo mensaje de ${remitente}`,{body:mensaje,icon:'Images/notification-icon.png'}));
        }
      }

      function setupMessagesEvents() {
        const cl = document.getElementById('contactsList');
        if (cl) cl.addEventListener('click',e=>{
          const item = e.target.closest('.contact-item');
          if (!item) return;
          document.querySelectorAll('.contact-item').forEach(i=>i.classList.remove('active'));
          item.classList.add('active');
          loadChat(item.getAttribute('data-email'));
        });
        const sc = document.getElementById('searchContact');
        if (sc) sc.addEventListener('input',()=>{
          const term = sc.value.toLowerCase();
          document.querySelectorAll('.contact-item').forEach(item=>{
            const name=item.querySelector('.contact-name').textContent.toLowerCase();
            const mail=item.querySelector('.contact-email').textContent.toLowerCase();
            item.style.display=(name.includes(term)||mail.includes(term))?'flex':'none';
          });
        });
      }
      setupMessagesEvents();
      const init = document.querySelector('.contact-item.active');
      if (init) loadChat(init.getAttribute('data-email'));
    }

    // ==================== PUBLICAR VIAJE ====================
    function setupPublicarViaje() {
      const cc = document.getElementById('contentContainer');
      if (!document.getElementById('msgContainerPublicar')) {
        const div = document.createElement('div');
        div.id = 'msgContainerPublicar';
        cc.prepend(div);
      }
    }

    function enviarFormularioViaje() {
      if (isSubmitting) return;
      isSubmitting = true;

      const form = document.getElementById('formPublicarViaje');
      const mc = document.getElementById('msgContainerPublicar');
      const fd = new FormData(form);

      // Validación de fecha/hora en cliente
      const fecha = form.fecha.value;
      const hora = form.hora.value;
      const fechaHora = new Date(`${fecha}T${hora}`);

      if (fechaHora < new Date()) {
        mc.innerHTML = '<div class="error-alert">La fecha y hora deben ser futuras</div>';
        setTimeout(() => mc.innerHTML = '', 5000);
        isSubmitting = false;
        return;
      }

      fetch('publicarViaje.php', {
        method: 'POST',
        body: fd,
        credentials: 'include',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
      .then(r => r.text())
      .then(html => {
        mc.innerHTML = html;
        if (html.includes('mensajeExito')) {
          form.reset();
          actualizarViajes();
        }
        setTimeout(() => mc.innerHTML = '', 5000);
      })
      .catch(() => {
        mc.innerHTML = '<div class="error-alert">Error al enviar el formulario</div>';
        setTimeout(() => mc.innerHTML = '', 5000);
      })
      .finally(() => { isSubmitting = false; });
    }

    function actualizarViajes() {
      fetch('obtener_viajes.php')
        .then(r => r.text())
        .then(html => document.getElementById('viajesContainer').innerHTML = html);
    }

    // ==================== VIAJES DINÁMICOS & NOTIFICACIONES ====================
    window.lastTripId = 0;
    window.tripsPollingInterval = null;

    function checkNewTrips() {
      fetch(`obtener_nuevos_viajes.php?ultimo_id=${window.lastTripId}`, { credentials: 'include' })
        .then(r => r.json())
        .then(data => {
          if (data.success && data.count > 0) {
            const inicioLink = document.querySelector('.sidebar-link[data-page="Inicio"]');
            let badge = inicioLink.querySelector('.notification-badge') || document.createElement('span');
            badge.className = 'notification-badge';
            badge.textContent = data.count > 9 ? '9+' : data.count;
            if (!inicioLink.contains(badge)) inicioLink.appendChild(badge);
            if (inicioLink.classList.contains('active')) actualizarViajes();
            data.viajes.forEach(v => {
              if (Notification.permission === 'granted') {
                new Notification('Nuevo viaje disponible', {
                  body: `${v.origen} → ${v.destino} · ${v.fecha} ${v.hora}`
                });
              }
            });
            window.lastTripId = data.viajes[data.viajes.length - 1].id;
          }
        })
        .catch(console.error);
    }

    function startTripsPolling() {
      if (window.tripsPollingInterval) clearInterval(window.tripsPollingInterval);
      // Inicializar lastTripId
      document.querySelectorAll('#viajesContainer .card').forEach(c => {
        const id = parseInt(c.getAttribute('data-id'), 10);
        if (id > window.lastTripId) window.lastTripId = id;
      });
      checkNewTrips();
      window.tripsPollingInterval = setInterval(checkNewTrips, 30000);
    }

    document.addEventListener('DOMContentLoaded', () => {
      changeSection('Inicio');
      if ('Notification' in window) Notification.requestPermission();
      startGlobalMessagePolling();
      actualizarViajes();
      startTripsPolling();

      // Limpiar badge al hacer click en "Inicio"
      document.querySelector('.sidebar-link[data-page="Inicio"]').addEventListener('click', () => {
        const b = document.querySelector('.sidebar-link[data-page="Inicio"] .notification-badge');
        if (b) b.remove();
      });

      document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'formPublicarViaje') {
          e.preventDefault();
          enviarFormularioViaje();
        }
      });
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
          <li><a href="#" data-page="Inicio" class="sidebar-link active" onclick="changeSection('Inicio'); return false;">Inicio</a></li>
          <li><a href="#" data-page="misViajes" class="sidebar-link" onclick="changeSection('misViajes'); return false;">Mis Viajes</a></li>
          <li><a href="#" data-page="publicarViaje" class="sidebar-link" onclick="changeSection('publicarViaje'); return false;">Publicar Viaje</a></li>
          <li><a href="#" data-page="mensajes" class="sidebar-link" onclick="changeSection('mensajes'); return false;">Mensajes</a></li>
          <li><a href="#" data-page="perfil" class="sidebar-link" onclick="changeSection('perfil'); return false;">Perfil</a></li>
          <li><a href="#" data-page="seguridad" class="sidebar-link" onclick="changeSection('seguridad'); return false;">Seguridad</a></li>
          <li><a href="#" data-page="cerrarSesion" class="sidebar-link" onclick="changeSection('cerrarSesion'); return false;">Cerrar Sesión</a></li>
        </ul>
      </nav>
    </aside>

    <!-- Contenido Principal -->
    <main id="mainContent" class="main-content ml-[200px] transition-all duration-300">
      <!-- Sección integrada "Inicio" -->
      <div id="contentInicio" class="content-section">
        <h2 class="main-title">Viajes Disponibles</h2>
        <div class="grid-container" id="viajesContainer">
          <?php include 'obtener_viajes.php'; ?>
        </div>
        <h2 class="main-title mt-8">Seguimiento del Conductor</h2>
        <div id="map" class="map-container" style="height: 400px;"></div>
      </div>
      <!-- Contenedor para contenido dinámico -->
      <div id="contentContainer" class="content-section hidden"></div>
    </main>
  </div>

  <!-- Toggle sidebar y formularios Perfil/Seguridad -->
  <script>
    document.getElementById('toggleSidebar').addEventListener('click', function () {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('expanded');
    });

    function enviarFormularioPerfil() {
      const form = document.getElementById('formPerfil');
      const fd = new FormData(form);
      fetch('perfil.php', { method: 'POST', body: fd, credentials: 'include' })
        .then(r => r.text()).then(data => {
          document.getElementById('contentContainer').innerHTML = data;
          const me = document.getElementById('mensajeExito');
          const mr = document.getElementById('mensajeError');
          if (me) setTimeout(() => me.remove(), 5000);
          if (mr) setTimeout(() => mr.remove(), 5000);
        })
        .catch(console.error);
    }

    function enviarFormularioSeguridad() {
      const form = document.getElementById('formSeguridad');
      const fd = new FormData(form);
      fetch('seguridad.php', { method: 'POST', body: fd, credentials: 'include' })
        .then(r => r.text()).then(data => {
          document.getElementById('msgContainer').innerHTML = data;
          if (data.includes('mensajeExito')) form.reset();
          setTimeout(() => {
            const m = document.getElementById('mensajeExito') || document.getElementById('mensajeError');
            if (m) m.remove();
          }, 5000);
        })
        .catch(console.error);
    }

    document.addEventListener('click', function(e) {
      if (e.target.classList.contains('toggle-password')) {
        const tgt = document.getElementById(e.target.dataset.target);
        if (tgt) {
          if (tgt.type === 'password') {
            tgt.type = 'text';
            e.target.textContent = '🔒';
          } else {
            tgt.type = 'password';
            e.target.textContent = '👁️';
          }
        }
      }
    });

    document.addEventListener('input', function(e) {
      if (e.target.id === 'new_password') {
        const p = e.target.value;
        const ok = p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p);
        e.target.style.borderColor = p ? (ok ? '#27ae60' : '#e74c3c') : '#ddd';
      }
    });
  </script>
</body>
</html>

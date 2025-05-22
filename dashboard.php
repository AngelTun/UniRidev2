<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard - Compartir Viajes</title>
  <!-- TailwindCSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Leaflet CSS & JS para OpenStreetMap -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

  <!-- Nuestros CSS personalizados -->
  <link rel="stylesheet" href="dashboard.css" />
  <link rel="stylesheet" href="perfil.css" />
  <link rel="stylesheet" href="mensajes.css" />
  <link rel="stylesheet" href="seguridad.css" />
  <link rel="stylesheet" href="cerrarsesion.css" />
  <link rel="stylesheet" href="publicar_viaje.css" />

  <!-- Estilos para sugerencias OSM -->
  <style>
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
    @keyframes messageSent {
      from { transform: translateX(10px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    @keyframes messageReceived {
      from { transform: translateX(-10px); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
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
    @keyframes typingAnimation {
      0%, 60%, 100% { transform: translateY(0); }
      30% { transform: translateY(-5px); }
    }
    #msgContainerPublicar {
      margin-bottom: 1.5rem;
    }
    /* Estilos para sugerencias OSM */
    .autocomplete-list {
      position: absolute;
      z-index: 1000;
      background: white;
      border: 1px solid #ccc;
      border-radius: 4px;
      max-height: 200px;
      overflow-y: auto;
      width: calc(100% - 2px);
      margin-top: 2px;
    }
    .autocomplete-list li {
      padding: 6px 8px;
      cursor: pointer;
    }
    .autocomplete-list li:hover {
      background: #f0f0f0;
    }
    .sidebar {
      width: 200px;
      transform: translateX(0);
      transition: transform 0.3s ease-in-out;
    }
    .sidebar-hidden {
      transform: translateX(-100%);
    }
    .main-content {
      transition: margin-left 0.3s ease-in-out;
    }
    .seguir-btn {
      background: #3B82F6;
      color: white;
      padding: 8px 16px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s;
    }
    .seguir-btn:hover {
      filter: brightness(90%);
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      color: white;
      margin-left: 10px;
    }
    .bg-blue-500 { background-color: #3B82F6; }
    .card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      transition: transform 0.2s;
    }
    .card:hover {
      transform: translateY(-2px);
    }
    .map-container {
      border-radius: 12px;
      overflow: hidden;
      margin-top: 1rem;
    }
  </style>

  <script>
    // Variables globales
    let conductorMap = null;
    let conductorMarker = null;
    let ubicacionInterval = null;
    let markerOrigen = null;
    let markerDestino = null;
    window.messagePollingInterval = null;
    window.globalPollingInterval = null;
    window.currentChatEmail = '';
    window.unreadMessages = 0;
    window.lastNotificationTime = 0;
    window.unreadCounts = {};
    window.lastTripId = 0;
    window.tripsPollingInterval = null;
    let isSubmitting = false;

    // ========== FUNCIONALIDADES DE RESERVA Y SEGUIMIENTO ========== //
    function initLeafletMap() {
      const mapEl = document.getElementById('map');
      if (!mapEl) return;
      
      conductorMap = L.map(mapEl).setView([20.9671, -89.6236], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(conductorMap);
      
      conductorMarker = L.marker([20.9671, -89.6236])
        .addTo(conductorMap)
        .bindPopup('Ubicación del Conductor');
    }

    function actualizarUbicacionConductor(viajeId) {
      if(ubicacionInterval) clearInterval(ubicacionInterval);
      
      ubicacionInterval = setInterval(async () => {
        try {
          const response = await fetch(`obtener_ubicacion_conductor.php?viaje_id=${viajeId}`, {
            credentials: 'include'
          });
          const data = await response.json();
          
          if(data.success) {
            conductorMarker.setLatLng([data.lat, data.lng]);
            if(!conductorMap.getBounds().contains(conductorMarker.getLatLng())) {
              conductorMap.setView(conductorMarker.getLatLng(), 14);
            }
          }
        } catch(error) {
          console.error('Error actualizando ubicación:', error);
        }
      }, 5000);
    }

    function setupViajesInteractions() {
      document.getElementById('viajesContainer').addEventListener('click', async (e) => {
        if(e.target.classList.contains('reservar-btn')) {
          const viajeId = e.target.dataset.id;
          
          try {
            const response = await fetch('reservar_viaje.php', {
              method: 'POST',
              headers: {'Content-Type': 'application/x-www-form-urlencoded'},
              body: `viaje_id=${viajeId}`,
              credentials: 'include'
            });
            
            const data = await response.json();
            
            if(data.success) {
              Swal.fire({
                icon: 'success',
                title: '¡Reserva exitosa!',
                text: data.msg,
                confirmButtonColor: '#4f46e5'
              });
              actualizarViajes();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.error,
                confirmButtonColor: '#4f46e5'
              });
            }
          } catch(error) {
            console.error('Error:', error);
          }
        }
        
        if(e.target.classList.contains('seguir-btn')) {
          const viajeId = e.target.dataset.id;
          actualizarUbicacionConductor(viajeId);
          
          Swal.fire({
            title: 'Seguimiento activado',
            html: `<button class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700" 
                    onclick="clearInterval(ubicacionInterval)">
              Detener seguimiento
            </button>`,
            showConfirmButton: false
          });
        }
      });
    }

    // ========== FUNCIONALIDADES EXISTENTES ========== //
    const LOCATIONIQ_API_KEY = "pk.5c9995b97f3c62de4ee3ac8990f17d1c";

    async function geocode(query) {
      const resp = await fetch(
        `https://us1.locationiq.com/v1/search?key=${LOCATIONIQ_API_KEY}&q=${encodeURIComponent(query)}&format=json&limit=5`
      );
      return resp.json();
    }

    function initOSMAutocompletePublicar() {
      const cont = document.getElementById('mapaViaje');
      if (!cont) return;

      if (cont._leaflet_id) {
        cont._leaflet_id = null;
        cont.innerHTML = "";
      }

      const map = L.map(cont).setView([20.9671, -89.6236], 12);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
      }).addTo(map);

      function setupAutocomplete(inputId, which) {
        const input = document.getElementById(inputId);
        if (!input) return;
        let list = input.parentNode.querySelector('.autocomplete-list');
        if (!list) {
          list = document.createElement('ul');
          list.className = 'autocomplete-list';
          input.parentNode.style.position = 'relative';
          input.parentNode.appendChild(list);
        }

        input.oninput = null;

        input.addEventListener('input', async function () {
          const term = input.value.trim();
          if (term.length < 3) {
            list.innerHTML = '';
            return;
          }
          list.innerHTML = '<li style="color:#aaa;padding:6px 8px;">Buscando...</li>';
          try {
            const places = await geocode(term);
            list.innerHTML = '';
            if (!places.length) {
              list.innerHTML = '<li style="color:#aaa;padding:6px 8px;">Sin resultados</li>';
            }
            places.forEach(p => {
              const item = document.createElement('li');
              item.textContent = p.display_name;
              item.addEventListener('click', () => {
                input.value = p.display_name;
                list.innerHTML = '';
                const latlng = [parseFloat(p.lat), parseFloat(p.lon)];
                if (which === 'origen') {
                  if (markerOrigen) {
                    map.removeLayer(markerOrigen);
                  }
                  markerOrigen = L.marker(latlng).addTo(map);
                  markerOrigen.setLatLng(latlng);
                } else {
                  if (markerDestino) {
                    map.removeLayer(markerDestino);
                  }
                  markerDestino = L.marker(latlng).addTo(map);
                  markerDestino.setLatLng(latlng);
                }
                map.setView(latlng, 14);
              });
              list.appendChild(item);
            });
          } catch (err) {
            list.innerHTML = '<li style="color:red;padding:6px 8px;">Error buscando</li>';
          }
        });

        document.addEventListener('click', function docClick(e) {
          if (!input.parentNode.contains(e.target)) {
            list.innerHTML = '';
          }
        });
      }

      setupAutocomplete('origen',  'origen');
      setupAutocomplete('destino', 'destino');
    }

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
  initLeafletMap();

  // ✅ Remueve notificaciones visuales si existen
  const badge = document.querySelector('.sidebar-link[data-page="Inicio"] .notification-badge');
  if (badge) badge.remove();
  window.lastTripId = 0; // opcional: reset para detectar nuevos
}else {
        document.getElementById('contentInicio').classList.add('hidden');
        fetch(page + '.php', { credentials: 'include' })
          .then(r => r.text())
          .then(html => {
            document.getElementById('contentContainer').innerHTML = html;
            document.getElementById('contentContainer').classList.remove('hidden');

            if (page === 'misViajes') {
              const form = document.getElementById('filtrosForm');
              const resultados = document.getElementById('resultados');
              const btnExportar = document.getElementById('btnExportar');

              form?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                const params = new URLSearchParams(formData);

                try {
                  const response = await fetch(`filtrar_viajes.php?${params}`, {
                    credentials: 'include',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                  });

                  if (!response.ok) throw new Error('Error al filtrar');

                  resultados.innerHTML = await response.text();
                  window.history.replaceState({}, '', `misViajes.php?${params}`);
                  btnExportar.href = `exportar_viajes.php?${params}`;

                } catch (error) {
                  resultados.innerHTML = `<div class="text-red-500 text-center p-4">${error.message}</div>`;
                }
              });
              document.addEventListener('click', e => {
  if (e.target && e.target.classList.contains('cancelar-btn') && !e.target.disabled) {
    const reservaId = e.target.getAttribute('data-id');

    Swal.fire({
      title: '¿Cancelar reserva?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Sí, cancelar'
    }).then(result => {
      if (result.isConfirmed) {
        fetch('cancelar_reserva.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `reserva_id=${reservaId}`,
          credentials: 'include'
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            Swal.fire('Cancelado', 'Tu reserva ha sido cancelada.', 'success');
            changeSection('misViajes');
          } else {
            Swal.fire('Error', data.error || 'No se pudo cancelar la reserva.', 'error');
          }
        })
        .catch(() => {
          Swal.fire('Error', 'Error al procesar la solicitud.', 'error');
        });
      }
    });
  }
});


            }

            if (page === 'mensajes') initializeMessages();
            if (page === 'publicarViaje') setupPublicarViaje();
          })
          .catch(() => {
            document.getElementById('contentContainer').innerHTML = '<p>Error al cargar el contenido.</p>';
            document.getElementById('contentContainer').classList.remove('hidden');
          });
      }
    }

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
              form.reset();
              scrollToBottom();
              checkNewMessages(currentChatEmail);
            } else alert('Error: ' + data.error);
          })
          .catch(() => alert('Error al enviar el mensaje'));
      }

      function scrollToBottom() {
        const cm = document.getElementById('chatMessages');
        if (cm) cm.scrollTop = cm.scrollHeight;
      }

      function startMessagePolling(email) {
        stopMessagePolling();
        checkNewMessages(email);
        window.messagePollingInterval = setInterval(() => checkNewMessages(email), 3000);
      }

      function stopMessagePolling() {
        if (window.messagePollingInterval) {
          clearInterval(window.messagePollingInterval);
          window.messagePollingInterval = null;
        }
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
                  cm.appendChild(d);
                  newFlag = true;
                  if (email!==currentChatEmail||!document.hasFocus()) {
                    const now = Date.now();
                    if (now-window.lastNotificationTime>2000) {
                      new Notification(`Nuevo mensaje de ${msg.remitente}`, {
                        body: msg.mensaje.length>50 ? msg.mensaje.substring(0,50)+'...' : msg.mensaje,
                        icon: 'Images/notification-icon.png'
                      });
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
        }).then(()=>{
          checkUnreadMessages();
          updateContactBadge(email,0);
        });
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

    function setupPublicarViaje() {
      const cc = document.getElementById('contentContainer');
      if (!document.getElementById('msgContainerPublicar')) {
        const div = document.createElement('div');
        div.id = 'msgContainerPublicar';
        cc.prepend(div);
      }
      initOSMAutocompletePublicar();

      const form = document.getElementById('formPublicarViaje');
      if (form) {
        form.addEventListener('submit', enviarFormularioViaje);
      }
    }

    function enviarFormularioViaje(event) {
      event.preventDefault();
      
      if (isSubmitting) return false;
      isSubmitting = true;
      
      const form = document.getElementById('formPublicarViaje');
      const mc = document.getElementById('msgContainerPublicar');
      const fd = new FormData(form);

      // Validación de fecha y hora
      const fecha = form.fecha.value;
      const hora = form.hora.value;
      const fechaHora = new Date(`${fecha}T${hora}`);
      
      if (fechaHora < new Date()) {
        mc.innerHTML = '<div class="error-alert">La fecha y hora deben ser futuras</div>';
        setTimeout(() => mc.innerHTML = '', 5000);
        isSubmitting = false;
        return false;
      }

      // Mostrar loader
      mc.innerHTML = '<div class="text-blue-500">Publicando viaje, por favor espere...</div>';

      fetch('procesar_publicar_viaje.php', {
        method: 'POST',
        body: fd,
        credentials: 'include'
      })
      .then(response => response.text())
      .then(html => {
        mc.innerHTML = html;
        if (html.includes('mensajeExito')) {
          form.reset();
          actualizarViajes();
          
          // Limpiar marcadores del mapa si existen
          if (markerOrigen) {
            markerOrigen.remove();
            markerOrigen = null;
          }
          if (markerDestino) {
            markerDestino.remove();
            markerDestino = null;
          }
        }
        setTimeout(() => mc.innerHTML = '', 5000);
        isSubmitting = false;
      })
      .catch(error => {
        console.error('Error:', error);
        mc.innerHTML = '<div class="error-alert">Error al enviar el formulario</div>';
        setTimeout(() => mc.innerHTML = '', 5000);
        isSubmitting = false;
      });

      return false;
    }

    function actualizarViajes() {
      fetch('obtener_viajes.php')
        .then(r => r.text())
        .then(html => document.getElementById('viajesContainer').innerHTML = html);
    }

    function checkNewTrips() {
      const inicioLink = document.querySelector('.sidebar-link[data-page="Inicio"]');
      const isActive = inicioLink.classList.contains('active');
      if (isActive) {
        const old = inicioLink.querySelector('.notification-badge');
        if (old) old.remove();
        actualizarViajes();
      }
      fetch(`obtener_nuevos_viajes.php?ultimo_id=${window.lastTripId}`, {credentials: 'include'})
        .then(r => r.json())
        .then(data => {
          if (data.success && data.count > 0) {
            data.viajes.forEach(v => {
              if (Notification.permission === 'granted') {
                new Notification('Nuevo viaje disponible', {
                  body: `${v.origen} → ${v.destino} · ${v.fecha} ${v.hora}`
                });
              }
            });
            if (!isActive) {
              let badge = inicioLink.querySelector('.notification-badge') || document.createElement('span');
              badge.className = 'notification-badge';
              badge.textContent = data.count > 9 ? '9+' : data.count;
              if (!inicioLink.contains(badge)) inicioLink.appendChild(badge);
            }
            if (isActive) actualizarViajes();
            window.lastTripId = data.viajes[data.viajes.length - 1].id;
          }
        }).catch(console.error);
    }

    function startTripsPolling() {
      if (window.tripsPollingInterval) clearInterval(window.tripsPollingInterval);
      document.querySelectorAll('#viajesContainer .card').forEach(c => {
        const id = parseInt(c.getAttribute('data-id'), 10);
        if (id > window.lastTripId) window.lastTripId = id;
      });
      checkNewTrips();
      window.tripsPollingInterval = setInterval(checkNewTrips, 30000);
    }

    // Inicialización
    document.addEventListener('DOMContentLoaded', () => {
      changeSection('Inicio');
      if ('Notification' in window) Notification.requestPermission();
      startGlobalMessagePolling();
      actualizarViajes();
      startTripsPolling();
      setupViajesInteractions();
      initLeafletMap();

      document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('-translate-x-full');
        document.getElementById('mainContent').classList.toggle('ml-0');
        document.getElementById('mainContent').classList.toggle('ml-[200px]');
      });

      document.querySelector('.sidebar-link[data-page="Inicio"]').addEventListener('click', () => {
        const b = document.querySelector('.sidebar-link[data-page="Inicio"] .notification-badge');
        if (b) b.remove();
      });

      document.addEventListener('submit', e => {
        if (e.target && e.target.id === 'formPublicarViaje') {
          enviarFormularioViaje(e);
        }
      });
    });
  </script>
</head>
<body class="bg-gray-100">
  <!-- Encabezado -->
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

    <!-- Contenido -->
    <main id="mainContent" class="main-content ml-[200px] transition-all duration-300">
      <div id="contentInicio" class="content-section">
        <h2 class="main-title">Viajes Disponibles</h2>
        <div class="grid-container" id="viajesContainer">
          <?php include 'obtener_viajes.php'; ?>
        </div>
        <h2 class="main-title mt-8">Seguimiento del Conductor</h2>
        <div id="map" class="map-container" style="height: 400px;"></div>
      </div>
      <div id="contentContainer" class="content-section hidden"></div>
    </main>
  </div>

  <!-- Scripts adicionales -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script>
    // Toggle Sidebar
    document.getElementById('toggleSidebar').addEventListener('click', function () {
      document.getElementById('sidebar').classList.toggle('sidebar-hidden');
      document.getElementById('mainContent').classList.toggle('expanded');
    });

    // Perfil
    function enviarFormularioPerfil() {
      const form = document.getElementById('formPerfil'), fd = new FormData(form);
      fetch('perfil.php', {method: 'POST', body: fd, credentials: 'include'})
        .then(r => r.text()).then(data => {
          document.getElementById('contentContainer').innerHTML = data;
          const me = document.getElementById('mensajeExito'), mr = document.getElementById('mensajeError');
          if (me) setTimeout(() => me.remove(), 5000);
          if (mr) setTimeout(() => mr.remove(), 5000);
        }).catch(console.error);
    }

    // Seguridad
    function enviarFormularioSeguridad() {
      const form = document.getElementById('formSeguridad'), fd = new FormData(form);
      fetch('seguridad.php', {method: 'POST', body: fd, credentials: 'include'})
        .then(r => r.text()).then(data => {
          document.getElementById('msgContainer').innerHTML = data;
          if (data.includes('mensajeExito')) form.reset();
          setTimeout(() => {
            const m = document.getElementById('mensajeExito') || document.getElementById('mensajeError');
            if (m) m.remove();
          }, 5000);
        }).catch(console.error);
    }

    // Toggle Password
    document.addEventListener('click', e => {
      if (e.target.classList.contains('toggle-password')) {
        const tgt = document.getElementById(e.target.dataset.target);
        if (tgt) {
          tgt.type = tgt.type === 'password' ? 'text' : 'password';
          e.target.textContent = tgt.type === 'password' ? '👁️' : '🔒';
        }
      }
    });

    // Validación Password
    document.addEventListener('input', e => {
      if (e.target.id === 'new_password') {
        const p = e.target.value, ok = p.length >= 8 && /[A-Z]/.test(p) && /[0-9]/.test(p);
        e.target.style.borderColor = p ? (ok ? '#27ae60' : '#e74c3c') : '#ddd';
      }
    });

    function validarFiltros() {
      const campos = [
          document.querySelector('input[name="fecha"]').value.trim(),
          document.querySelector('input[name="origen"]').value.trim(),
          document.querySelector('input[name="destino"]').value.trim(),
          document.querySelector('input[name="asientos"]').value.trim()
      ];

      const todosVacios = campos.every(valor => valor === '');

      if (todosVacios) {
          Swal.fire({
              icon: 'warning',
              title: 'Filtros vacíos',
              text: 'Debes completar al menos un campo para realizar la búsqueda',
              confirmButtonColor: '#4f46e5',
              confirmButtonText: 'Entendido'
          });
          return false;
      }
      return true;
    }
  </script>
</body>
</html>

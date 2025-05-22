// Variables globales para el mapa y seguimiento
let conductorMap = null;
let conductorMarker = null;
let ubicacionInterval = null;

/**
 * Inicializa el mapa para seguimiento del conductor
 */
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

/**
 * Actualiza la ubicación del conductor en tiempo real
 * @param {number} viajeId - ID del viaje a seguir
 */
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

/**
 * Configura los event listeners para los botones de reservar y seguir
 */
function setupViajesInteractions() {
  const viajesContainer = document.getElementById('viajesContainer');
  if (!viajesContainer) return;

  viajesContainer.addEventListener('click', async (e) => {
    // Manejar clic en botón Reservar
    if(e.target.classList.contains('reservar-btn')) {
      const viajeId = e.target.dataset.id;
      e.target.disabled = true;
      e.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Reservando...';
      
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
          e.target.disabled = false;
          e.target.textContent = 'Reservar';
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.error,
            confirmButtonColor: '#4f46e5'
          });
        }
      } catch(error) {
        console.error('Error:', error);
        e.target.disabled = false;
        e.target.textContent = 'Reservar';
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Ocurrió un error al procesar tu reserva',
          confirmButtonColor: '#4f46e5'
        });
      }
    }
    
    // Manejar clic en botón Seguir
    if(e.target.classList.contains('seguir-btn')) {
      const viajeId = e.target.dataset.id;
      actualizarUbicacionConductor(viajeId);
      
      Swal.fire({
        title: 'Seguimiento activado',
        html: `<button class="mt-4 px-4 py-2 bg-red-500 text-white rounded hover:bg-red-700" 
                onclick="detenerSeguimiento()">
          Detener seguimiento
        </button>`,
        showConfirmButton: false,
        allowOutsideClick: false
      });
    }
  });
}

/**
 * Detiene el seguimiento del conductor
 */
function detenerSeguimiento() {
  if(ubicacionInterval) {
    clearInterval(ubicacionInterval);
    ubicacionInterval = null;
  }
  Swal.close();
}

/**
 * Actualiza la lista de viajes disponibles
 */
function actualizarViajes() {
  fetch('obtener_viajes.php')
    .then(r => r.text())
    .then(html => {
      document.getElementById('viajesContainer').innerHTML = html;
    })
    .catch(err => {
      console.error('Error actualizando viajes:', err);
    });
}

// Inicialización cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
  initLeafletMap();
  setupViajesInteractions();
});

// Hacer funciones accesibles globalmente
window.detenerSeguimiento = detenerSeguimiento;
window.actualizarViajes = actualizarViajes;
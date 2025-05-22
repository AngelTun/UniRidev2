<?php 
session_start(); ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

<!-- CONTENEDOR DE ALERTAS -->
<div id="msgContainerPublicar"></div>

<!-- FORMULARIO DE PUBLICAR VIAJE -->
<div class="publicar-viaje-container">
    <h2 class="section-title">Publicar Nuevo Viaje</h2>
    <form id="formPublicarViaje" method="POST" class="viaje-form" autocomplete="off">
        <div class="form-grid">
            <!-- Origen -->
            <div class="input-group">
                <label for="origen">Origen:</label>
                <input type="text" id="origen" name="origen" required class="input-field" placeholder="Selecciona o escribe el origen">
            </div>
            <!-- Destino -->
            <div class="input-group">
                <label for="destino">Destino:</label>
                <input type="text" id="destino" name="destino" required class="input-field" placeholder="Selecciona o escribe el destino">
            </div>
            <!-- Fecha -->
            <div class="input-group">
                <label for="fecha">Fecha:</label>
                <input type="date" id="fecha" name="fecha" required class="input-field" min="<?= date('Y-m-d') ?>">
            </div>
            <!-- Hora -->
            <div class="input-group">
                <label for="hora">Hora:</label>
                <input type="time" id="hora" name="hora" required class="input-field">
            </div>
            <!-- Asientos -->
            <div class="input-group">
                <label for="asientos">Asientos disponibles:</label>
                <input type="number" id="asientos" name="asientos" required class="input-field" min="1" max="6" value="1">
            </div>
            <!-- Precio -->
            <div class="input-group">
                <label for="precio">Precio por asiento:</label>
                <div class="price-input">
                    <span class="currency">$</span>
                    <input type="number" id="precio" name="precio" required class="input-field" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
        </div>
        <!-- Detalles -->
        <div class="input-group full-width">
            <label for="detalles">Detalles adicionales:</label>
            <textarea id="detalles" name="detalles" class="input-field textarea-field"
                placeholder="Ejemplo: Punto de encuentro, paradas, normas del viaje..."></textarea>
        </div>
        <!-- Mapa OSM para autocompletar -->
        <div style="height: 350px; margin-bottom: 20px;">
            <div id="mapaViaje" style="width: 100%; height: 100%; border-radius: 8px;"></div>
        </div>
        <!-- Botón publicar -->
        <button type="submit" class="submit-btn">Publicar Viaje</button>
    </form>
</div>
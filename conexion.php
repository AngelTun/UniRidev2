<?php
// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$database = "UniRide";

// Configuración de zona horaria (ajusta según tu ubicación)
date_default_timezone_set('America/Merida'); // Para PHP

// Conexión mysqli
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Error de conexión (mysqli): ". $conn->connect_error);
}

// Establecer zona horaria para mysqli
$conn->query("SET time_zone = '-06:00';"); // Debe coincidir con PHP

// Conexión PDO
try {
    $pdo = new PDO("mysql:host=$servername;dbname=$database;charset=utf8mb4", $username, $password);
    
    // Configuración importante para PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Establecer zona horaria para PDO
    $pdo->exec("SET time_zone = '-06:00';"); // Debe coincidir con lo anterior
    $pdo->exec("SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'");
    
} catch (PDOException $e) {
    die("Error de conexión (PDO): ". $e->getMessage());
}

// Función para depuración mejorada
function debug_db($var, $exit = true) {
    echo '<pre style="background:#f5f5f5;padding:10px;border:1px solid #ddd;">';
    if(is_array($var) || is_object($var)) {
        print_r($var);
    } else {
        var_dump($var);
    }
    echo '</pre>';
    if($exit) exit;
}

// Función para obtener la hora actual consistente
function db_now() {
    global $pdo;
    $stmt = $pdo->query("SELECT NOW() as now");
    return $stmt->fetchColumn();
}
?>
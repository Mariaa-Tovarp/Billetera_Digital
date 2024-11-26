<?php
$servername = "localhost";
$username = "root";  // Asegúrate de usar las credenciales correctas
$password = "";      // La contraseña por defecto de XAMPP es vacía
$dbname = "app_virtual"; // Nombre de tu base de datos

// Crear la conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar si la conexión fue exitosa
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}
?>

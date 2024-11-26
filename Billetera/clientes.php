<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billetera Snow - Clientes</title>
    <link rel="stylesheet" href="Estilos_clientes.css">
</head>
<body>

<header>
    <h1>Billetera Snow</h1>
    <a href="index.php">Regresar</a>
</header>

<?php
// Incluir el archivo de conexión
include 'conexion.php';

// Verificar si el formulario ha sido enviado para crear un nuevo cliente
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["crear_cliente"])) {
    // Capturar los datos enviados desde el formulario
    $documento = $_POST["documento"];
    $nombre = $_POST["nombre"];
    $telefono = $_POST["telefono"];
    $correo = $_POST["correo"];
    $contrasena = $_POST["contrasena"]; // No encriptar la contraseña

    // Verificar si el teléfono ya existe
    $sql_check = "SELECT * FROM clientes WHERE Telefono = '$telefono'";
    $result_check = $conn->query($sql_check);

    if ($result_check->num_rows > 0) {
        // Si el número de teléfono ya está registrado
        echo "<p class='error'>Error: El número de teléfono ya está registrado.</p>";
    } else {
        // Si el teléfono no está registrado, proceder con la inserción del cliente
        $sql = "INSERT INTO clientes (Documento, Nombre, Telefono, Correo, Contraseña, Fecha_registro) 
                VALUES ('$documento', '$nombre', '$telefono', '$correo', '$contrasena', CURRENT_TIMESTAMP)";
        
        if ($conn->query($sql) === TRUE) {
            // Mostrar mensaje de confirmación con JavaScript
            echo "<script>
                    alert('Cliente creado exitosamente.');
                  </script>";
        } else {
            echo "<p class='error'>Error: " . $sql . "<br>" . $conn->error . "</p>";
        }
    }
}

// Cerrar la conexión
$conn->close();
?>

<!-- Formulario para crear un nuevo cliente -->
<h3><center>Complete los datos para crear su cuenta</center></h3>
<form method="post" action="clientes.php">
    Documento: <input type="text" name="documento" required><br>
    Nombre: <input type="text" name="nombre" required><br>
    Teléfono: <input type="text" name="telefono" required><br>
    Correo: <input type="email" name="correo" required><br>
    Contraseña: <input type="password" name="contrasena" required><br>
    <input type="submit" name="crear_cliente" value="Crear">
</form>

<footer>
    <p>&copy; 2024 Snow Billetera Digital.</p>
</footer>

</body>
</html>

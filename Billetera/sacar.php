<?php
// Iniciar sesión al principio del archivo
session_start();

// Incluir el archivo de conexión
include 'conexion.php';

// Verificar conexión a la base de datos
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['cliente'])) {
    $cliente = $_SESSION['cliente'];
    $telefono = $cliente['Telefono'];  // Número de teléfono del cliente

    // Verificar si el formulario fue enviado
    if (isset($_POST['programar'])) {
        $monto = $_POST['monto'];
        $medio_transaccion = $_POST['medio_transaccion'];
        $ubicacion = $_POST['ubicacion'];

        // Insertar la transacción de retiro en la base de datos
        $sql = "INSERT INTO transacciones (Id_Cliente, Monto, Medio_Transaccion, Ubicacion, Fecha_Transaccion)
                VALUES (?, ?, ?, ?, NOW())";

        // Preparar la consulta
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("idss", $cliente['ID_Cliente'], $monto, $medio_transaccion, $ubicacion);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Transacción realizada correctamente, mostrar alerta
            echo "<script>
                    alert('Retiro programado exitosamente. El monto de $$monto será retirado usando $medio_transaccion.');
                  </script>";
        } else {
            // Error en la inserción
            echo "<script>
                    alert('Hubo un error al programar el retiro. Por favor, inténtelo de nuevo.');
                  </script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Programar Retiro</title>
    <style>
        /* Estilo general para la página */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc; /* Blanco claro */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        /* Contenedor principal */
        .main-container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        /* Títulos */
        h3 {
            color: #3498db; /* Azul */
            font-size: 24px;
            margin-bottom: 20px;
        }

        /* Texto de saldo */
        p {
            font-size: 18px;
            color: #333; /* Gris oscuro */
            margin-bottom: 20px;
        }

        /* Estilo del formulario */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        /* Etiquetas de los campos */
        label {
            font-weight: bold;
            color: #2980b9; /* Azul más oscuro */
        }

        /* Campos de entrada */
        input[type="number"], input[type="text"], select {
            padding: 10px;
            font-size: 16px;
            border: 2px solid #2980b9; /* Azul más oscuro */
            border-radius: 8px;
            width: 100%;
            background-color: #fafafa; /* Fondo suave */
            color: #333; /* Texto gris oscuro */
        }

        /* Efecto al hacer foco en los campos */
        input[type="number"]:focus, input[type="text"]:focus, select:focus {
            border-color: #3498db; /* Azul */
            outline: none;
        }

        /* Botón de submit */
        input[type="submit"] {
            background-color: #3498db;
            color: white;
            font-size: 18px;
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        input[type="submit"]:hover {
            background-color: #2980b9; /* Azul más oscuro */
            transform: scale(1.05);
        }

        /* Mensajes de error */
        .error {
            color: #3498db;
            background-color: #f8d7da;
            padding: 12px;
            margin-top: 15px;
            border-radius: 8px;
            font-size: 1.1em;
        }

        /* Mensajes de éxito */
        .success {
            color: #3498db;
            background-color: #d4edda;
            padding: 12px;
            margin-top: 15px;
            border-radius: 8px;
            font-size: 1.1em;
        }

        /* Botón de regresar */
        .regresar-btn {
            display: block;
            margin-top: 20px;
            background-color: #2980b9; /* Azul más oscuro */
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-size: 1.1em;
            text-align: center;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .regresar-btn:hover {
            background-color: #3498db;
            color: white;
        }

        /* Responsivo */
        @media (max-width: 768px) {
            .main-container {
                width: 95%;
                padding: 20px;
            }

            input[type="submit"], .regresar-btn {
                font-size: 1em;
                padding: 10px 20px;
            }
        }

    </style>
</head>
<body>

    <!-- Formulario para programar retiro -->
    <div class="main-container">
        <h3>Programar Retiro</h3>
        <form id="retiroForm" method="POST">
            <label for="monto">Monto a retirar:</label>
            <input type="number" name="monto" id="monto" required>

            <label for="medio_transaccion">Medio de Transacción:</label>
            <select name="medio_transaccion" id="medio_transaccion" required>
                <option value="Corresponsal">Corresponsal</option>
                <option value="Cajero">Cajero</option>
            </select>

            <label for="ubicacion">Ubicación de Retiro:</label>
            <input type="text" name="ubicacion" id="ubicacion" required>

            <input type="submit" name="programar" value="Programar Retiro">
        </form>

        <?php
        // Mostrar el saldo disponible
        if (isset($_SESSION['cliente'])) {
            echo "<p><strong>Saldo Disponible:</strong> $" . number_format($cliente['Saldo_Disponible'], 2) . "</p>";
        }
        ?>

        <a href="iniciar_sesion.php" class="regresar-btn">Regresar</a>
    </div>

    <script>
        document.getElementById('retiroForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Evita que el formulario se envíe y recargue la página

            var monto = document.getElementById('monto').value;
            var medioTransaccion = document.getElementById('medio_transaccion').value;
            var ubicacion = document.getElementById('ubicacion').value;

            // Mostrar alerta de confirmación
            alert('Retiro programado exitosamente. El monto de $' + monto + ' será retirado usando ' + medioTransaccion + ' en la ubicación ' + ubicacion + '.');

            // Simular el envío del formulario sin recargar la página
            this.submit(); // Esto enviará el formulario al servidor
        });
    </script>

</body>
</html>

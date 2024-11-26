<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billetera Snow - Iniciar Sesión</title>
    <link rel="stylesheet" href="Estilos_iniciar_sesion.css">
</head>

<body>

    <header>
        <h1>Billetera Snow</h1>
        <a href="index.php">Regresar</a>
    </header>

    <div class="container">
        <?php
        // Incluir el archivo de conexión
        include 'conexion.php';

        session_start();

        // Verificar si el usuario ya ha iniciado sesión
        if (isset($_SESSION['cliente'])) {
            // Obtener los datos del cliente de la sesión
            $cliente = $_SESSION['cliente'];

            echo "<center><h3><p class='success'>Bienvenid@, " . $cliente['Nombre'] . "</p></h3></center>";
            echo "<h3>Detalles de tu cuenta:</h3>";
            echo "<p><strong>Nombre:</strong> " . $cliente['Nombre'] . "</p>";
            echo "<p><strong>Documento:</strong> " . $cliente['Documento'] . "</p>";
            echo "<p><strong>Teléfono:</strong> " . $cliente['Telefono'] . "</p>";
            echo "<p><strong>Saldo Disponible:</strong> $" . $cliente['Saldo_Disponible'] . "</p>";

            // Mostrar gestión de cuenta y transacciones
            echo "<h3>Servicios</h3>";
            echo '<div class="botones-container">';
            echo '<button class="boton" onclick="window.location.href=\'envia.php\'">Enviar</button>';
            echo '<button class="boton" onclick="window.location.href=\'consignar.php\'">Consignar</button>';
            echo '<button class="boton" onclick="window.location.href=\'sacar.php\'">Sacar</button>';
            echo '<button class="boton" onclick="window.location.href=\'pagos.php\'">Pagos Servicio</button>';
            echo '<button class="boton" onclick="window.location.href=\'recargas.php\'">Recargas</button>';
            echo '</div>';


            // Consultar las transacciones asociadas al teléfono del cliente
            $sql_transacciones = "
            SELECT transacciones.Cuenta_Destino, transacciones.Monto, transacciones.Descripcion, transacciones.Fecha_Transaccion
            FROM transacciones
            INNER JOIN clientes ON clientes.Id_Cliente = transacciones.Id_Cliente  /* Relacionamos las tablas por Id_Cliente */
            WHERE clientes.Telefono = ?";  // Filtrar por el teléfono del cliente

            $stmt_transacciones = $conn->prepare($sql_transacciones);
            $stmt_transacciones->bind_param("s", $cliente['Telefono']); // Filtrar por el teléfono del cliente
            $stmt_transacciones->execute();
            $result_transacciones = $stmt_transacciones->get_result();

            echo "<h4>Transacciones Realizadas</h4>";

            if ($result_transacciones->num_rows > 0) {
                echo "<table>
                        <tr>
                            <th>Cuenta Destino</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Fecha de Transacción</th>
                        </tr>";
                while ($transaccion = $result_transacciones->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $transaccion['Cuenta_Destino'] . "</td>
                            <td>$" . $transaccion['Monto'] . "</td>
                            <td>" . $transaccion['Descripcion'] . "</td>
                            <td>" . $transaccion['Fecha_Transaccion'] . "</td>
                          </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No hay transacciones para mostrar.</p>";
            }

            // Mostrar el botón de cerrar sesión solo si el usuario está logueado
            echo '<div class="cerrar-sesion-container">
            <button class="cerrar-sesion" onclick="window.location.href=\'cerrar_sesion.php\';">
                Cerrar sesión
              </button>
          </div>';

        } else {
            // Mostrar el formulario de inicio de sesión si no ha iniciado sesión
            echo '
            <form method="POST" action="iniciar_sesion.php">
                <h3>Por favor, ingrese sus datos</h3>
                <label for="telefono">Teléfono:</label>
                <input type="text" id="telefono" name="telefono" required><br>

                <label for="contrasena">Contraseña:</label>
                <input type="password" id="contrasena" name="contrasena" required><br>

                <input type="submit" name="iniciar_sesion" value="Iniciar Sesión">
            </form>
            ';
        }

        // Verificar si el formulario ha sido enviado
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["iniciar_sesion"])) {
            // Capturar los datos del formulario y sanitizar
            $telefono = mysqli_real_escape_string($conn, $_POST["telefono"]);
            $contrasena = mysqli_real_escape_string($conn, $_POST["contrasena"]);

            // Preparar la consulta para evitar inyección SQL
            $sql = "SELECT * FROM clientes WHERE Telefono = ? AND Contraseña = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $telefono, $contrasena); // 'ss' indica que ambos parámetros son strings

            // Ejecutar la consulta
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Si los datos son correctos, iniciar sesión
                $cliente = $result->fetch_assoc();
                $_SESSION['cliente'] = $cliente;  // Guardar los datos del cliente en la sesión

                // Recargar la página para que no muestre el formulario
                header("Location: iniciar_sesion.php");
                exit();
            } else {
                // Si los datos son incorrectos
                echo "<p class='error'>Error: Teléfono o contraseña incorrectos.</p>";
            }

            // Cerrar el statement
            $stmt->close();
        }

        // Cerrar la conexión
        $conn->close();
        ?>

    </div>

    <footer>
        <p>&copy; 2024 Snow Billetera Digital.</p>
    </footer>

</body>

</html>

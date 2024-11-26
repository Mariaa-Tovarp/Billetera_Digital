<?php
// Incluir el archivo de conexión
include 'conexion.php';

// Iniciar sesión
session_start();

// Verificar si el usuario ha iniciado sesión
if (isset($_SESSION['cliente'])) {
    // Obtener los datos del cliente desde la sesión
    $cliente = $_SESSION['cliente'];
    $telefono = $cliente['Telefono'];

    // Si el formulario ha sido enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["enviar"])) {
        // Obtener los valores del formulario
        $cuenta_destino = mysqli_real_escape_string($conn, $_POST["cuenta_destino"]);
        $monto = mysqli_real_escape_string($conn, $_POST["monto"]);
        $detalle_referencia = mysqli_real_escape_string($conn, $_POST["detalle_referencia"]);
        $medio_transaccion = mysqli_real_escape_string($conn, $_POST["medio_transaccion"]);

        // Validar que el saldo disponible sea suficiente para la recarga
        if ($cliente['Saldo_Disponible'] >= $monto) {
            // Iniciar transacción
            $conn->begin_transaction();

            // Verificar si la cuenta destino existe
            $sql_check_destino = "SELECT Id_Cliente, Saldo_Disponible FROM clientes WHERE Telefono = ?";
            $stmt_destino = $conn->prepare($sql_check_destino);
            $stmt_destino->bind_param("s", $cuenta_destino);
            $stmt_destino->execute();
            $result_destino = $stmt_destino->get_result();

            if ($result_destino->num_rows == 1) {
                // Obtener el cliente destino y su saldo
                $destino = $result_destino->fetch_assoc();
                $saldo_destino = $destino['Saldo_Disponible'];

                // Descontar el saldo disponible para la recarga
                $stmt_emisor = $conn->prepare("UPDATE clientes SET Saldo_Disponible = Saldo_Disponible - ? WHERE Telefono = ?");
                $stmt_emisor->bind_param("ds", $monto, $telefono);

                // Ejecutar la actualización del saldo del emisor
                if ($stmt_emisor->execute()) {
                    // Registrar la transacción (recarga)
                    $sql_insertar_transaccion = "INSERT INTO transacciones (Id_Cliente, Cuenta_Destino, Detalle_Referencia, Monto, Descripcion, Fecha_Transaccion, Id_TipoTransaccion, Medio_Transaccion) 
                    VALUES (?, ?, ?, ?, 'Recarga de servicio', NOW(), 4, ?)";
                    $stmt_transaccion = $conn->prepare($sql_insertar_transaccion);
                    $stmt_transaccion->bind_param("issds", $cliente['Id_Cliente'], $cuenta_destino, $detalle_referencia, $monto, $medio_transaccion);

                    if ($stmt_transaccion->execute()) {
                        // Confirmar la transacción
                        $conn->commit();
                        echo "<p class='success'>La recarga fue realizada con éxito.</p>";
                    } else {
                        // Si falla la inserción de la transacción, deshacer los cambios
                        $conn->rollback();
                        echo "<p class='error'>Error al registrar la transacción.</p>";
                    }
                } else {
                    // Si hubo un problema al actualizar el saldo, deshacer la transacción
                    $conn->rollback();
                    echo "<p class='error'>Hubo un error al descontar el saldo. Por favor, inténtelo nuevamente.</p>";
                }
            } else {
                // Si no existe la cuenta destino
                echo "<p class='error'>La cuenta destino no existe.</p>";
                $conn->rollback();
            }
        } else {
            echo "<p class='error'>No tienes suficiente saldo para realizar la recarga.</p>";
        }
    }

    // Consulta para obtener las transacciones del cliente
    $sql_transacciones = "
    SELECT 
        transacciones.Cuenta_Destino, 
        transacciones.Detalle_Referencia,
        transacciones.Monto, 
        transacciones.Descripcion, 
        transacciones.Fecha_Transaccion, 
        transacciones.Medio_Transaccion
    FROM transacciones
    INNER JOIN clientes ON clientes.Id_Cliente = transacciones.Id_Cliente
    WHERE clientes.Telefono = ?";

    // Preparar la consulta
    $stmt_transacciones = $conn->prepare($sql_transacciones);
    $stmt_transacciones->bind_param("s", $telefono); // "s" indica que el parámetro es un string (teléfono)

    // Ejecutar la consulta
    $stmt_transacciones->execute();
    $result_transacciones = $stmt_transacciones->get_result();
} else {
    // Si no está iniciada la sesión
    echo "<p class='error'>Por favor, inicia sesión para realizar una recarga.</p>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recarga de Servicio</title>
    <style>
        /* Tipografía */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            color: #333;
        }

        h3 {
            color: #3498db;
        }

        /* Estilos generales */
        .success {
            color: #2ecc71;
            font-weight: bold;
        }

        .error {
            color: #e74c3c;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        tr:hover {
            background-color: #fafafa;
        }

        form {
            background-color: #fff;
            padding: 20px;
            margin-top: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        input[type="text"], input[type="number"], select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border-radius: 5px;
            border: 1px solid #ccc;
        }

        input[type="submit"] {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
            width: 100%;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .regresar-btn {
            display: inline-block;
            margin-top: 20px;
            background-color: #f2f2f2;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            color: #333;
            border: 1px solid #ddd;
        }

        .regresar-btn:hover {
            background-color: #fafafa;
        }

        /* Responsive */
        @media (max-width: 768px) {
            table, form {
                width: 90%;
            }
        }
    </style>
</head>
<body>
    <h3>Realizar Recarga de Servicio</h3>
    <form method="POST" action="recargas.php">
        <label for="cuenta_destino">Cuenta Destino:</label>
        <input type="text" id="cuenta_destino" name="cuenta_destino" required><br>

        <label for="monto">Monto a Recargar:</label>
        <input type="number" id="monto" name="monto" required><br>

        <label for="detalle_referencia">Operador:</label>
        <select name="detalle_referencia" required>
            <option value="TIGO">TIGO</option>
            <option value="CLARO">CLARO</option>
            <option value="WOM">WOM</option>
            <option value="MOVISTAR">MOVISTAR</option>
        </select><br>

        <label for="medio_transaccion">Medio de Transacción:</label>
        <select name="medio_transaccion" required>
            <option value="APP VIRTUAL">APP VIRTUAL</option>
            <option value="CORRESPONSAL">CORRESPONSAL</option>
            <option value="OFICINA FISICA">OFICINA FISICA</option>
        </select><br>

        <p><strong>Fecha de Recarga:</strong> <?php echo date("Y-m-d H:i:s"); ?></p>

        <input type="submit" name="enviar" value="Realizar Recarga">
    </form>

    <div>
        <h3>Historial de Transacciones</h3>
        <table>
            <thead>
                <tr>
                    <th>Cuenta Destino</th>
                    <th>Referencia</th>
                    <th>Monto</th>
                    <th>Descripción</th>
                    <th>Fecha</th>
                    <th>Medio</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mostrar las transacciones
                while ($row = $result_transacciones->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['Cuenta_Destino']}</td>
                            <td>{$row['Detalle_Referencia']}</td>
                            <td>{$row['Monto']}</td>
                            <td>{$row['Descripcion']}</td>
                            <td>{$row['Fecha_Transaccion']}</td>
                            <td>{$row['Medio_Transaccion']}</td>
                          </tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <a href="iniciar_sesion.php" class="regresar-btn">Regresar</a>
</body>
</html>

<?php
// Cerrar la conexión
$conn->close();
?>

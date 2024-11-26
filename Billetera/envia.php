<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transferencia de Dinero</title>
    <style>
        /* Estilos generales */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; /* Fuente consistente */
    background-color: #f4f7fc; /* Blanco claro */
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

/* Contenedor principal */
.container {
    width: 80%;
    max-width: 800px;
    margin: 50px auto;
    padding: 40px;
    background-color: #fff; /* Fondo blanco */
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); /* Sombra ligera */
    border-radius: 10px;
}

/* Títulos */
h3 {
    color: #2980b9; /* Azul más oscuro */
    font-size: 24px;
    text-align: center;
    margin-bottom: 20px;
}

/* Tabla de transacciones */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd; /* Bordes suaves */
}

th {
    background-color: #3498db; /* Azul */
    color: white;
}

td {
    background-color: #fafafa; /* Blanco grisáceo */
    color: #333; /* Gris oscuro */
}

tr:nth-child(even) td {
    background-color: #f2f2f2; /* Gris claro alternado */
}

/* Formularios */
.form-group {
    margin-bottom: 15px;
}

label {
    font-weight: bold;
    color: #2980b9; /* Azul más oscuro */
}

input[type="text"], input[type="number"] {
    width: 100%;
    padding: 12px;
    margin-top: 5px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    background-color: #fafafa; /* Fondo blanco grisáceo */
}

input[type="submit"] {
    background-color: #3498db; 
    color: white;
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 1.2em;
    width: 100%;
    transition: background-color 0.3s ease, color 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #3498db; /* Azul */
    color: white;
}

/* Mensajes de error y éxito */
.error {
    color: #e16162; /* Rojo coral */
    background-color: #f8d7da;
    padding: 12px;
    margin-top: 15px;
    border-radius: 8px;
    font-size: 1.1em;
    text-align: center;
}

.success {
    color: #3498db;
    background-color: #d4edda;
    padding: 12px;
    margin-top: 15px;
    border-radius: 8px;
    font-size: 1.1em;
    text-align: center;
}

/* Botón de regresar */
.regresar-btn {
    display: block;
    width: fit-content;
    margin: 30px auto 0;
    background-color: #3498db;
    color: white;
    padding: 12px 25px;
    text-decoration: none;
    border-radius: 8px;
    font-size: 1.1em;
    text-align: center;
    transition: background-color 0.3s ease, color 0.3s ease;
}

.regresar-btn:hover {
    background-color: #3498db; /* Azul */
    color: white;
}

/* Párrafos */
p {
    font-size: 16px;
    color: #333; /* Gris oscuro */
    margin-top: 20px;
    text-align: center;
}

/* Responsivo */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 20px;
    }

    input[type="submit"], .regresar-btn {
        font-size: 1em;
        padding: 10px 20px;
    }

    table, th, td {
        font-size: 14px;
    }
}

    </style>
</head>
<body>


    <div class="container">
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
                // Obtener el número de cuenta destino y el monto a enviar
                $cuenta_destino = mysqli_real_escape_string($conn, $_POST["cuenta_destino"]);
                $monto = mysqli_real_escape_string($conn, $_POST["monto"]);

                // Validar que el saldo disponible sea suficiente para la transacción
                if ($cliente['Saldo_Disponible'] >= $monto) {
                    // Iniciar transacción
                    $conn->begin_transaction();

                    // Verificar si el tipo de transacción 'transferencia' existe
                    $sql_check_tipo_transaccion = "SELECT Id_TipoTransaccion FROM tipo_transaccion WHERE Descripcion = 'transferencia'";
                    $result_check = $conn->query($sql_check_tipo_transaccion);

                    if ($result_check->num_rows == 0) {
                        // Si no existe, insertar el tipo de transacción
                        $sql_insert_tipo_transaccion = "INSERT INTO tipo_transaccion (Id_TipoTransaccion, Descripcion) VALUES (2, 'transferencia')";
                        if (!$conn->query($sql_insert_tipo_transaccion)) {
                            // Si falla la inserción del tipo de transacción, deshacer los cambios
                            $conn->rollback();
                            echo "<p class='error'>Error al insertar el tipo de transacción.</p>";
                            exit();
                        }
                    }

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

                        // Comenzamos la actualización de los saldos
                        $stmt_emisor = $conn->prepare("UPDATE clientes SET Saldo_Disponible = Saldo_Disponible - ? WHERE Telefono = ?");
                        $stmt_emisor->bind_param("ds", $monto, $telefono);

                        $stmt_receptor = $conn->prepare("UPDATE clientes SET Saldo_Disponible = Saldo_Disponible + ? WHERE Telefono = ?");
                        $stmt_receptor->bind_param("ds", $monto, $cuenta_destino);

                        // Ejecutar ambas consultas
                        if ($stmt_emisor->execute() && $stmt_receptor->execute()) {
                            // Registrar la transacción
                            $sql_insertar_transaccion = "INSERT INTO transacciones (Id_Cliente, Cuenta_Destino, Monto, Descripcion, Fecha_Transaccion, Id_TipoTransaccion) 
                                                         VALUES (?, ?, ?, 'Envío de dinero', NOW(), 2)";
                            $stmt_transaccion = $conn->prepare($sql_insertar_transaccion);
                            $stmt_transaccion->bind_param("isd", $cliente['Id_Cliente'], $cuenta_destino, $monto);

                            if ($stmt_transaccion->execute()) {
                                // Confirmar la transacción
                                $conn->commit();
                                echo "<p class='success'>La transacción fue realizada con éxito.</p>";
                            } else {
                                // Si falla la inserción de la transacción, deshacer los cambios
                                $conn->rollback();
                                echo "<p class='error'>Error al registrar la transacción.</p>";
                            }
                        } else {
                            // Si hubo un problema al actualizar los saldos, deshacer la transacción
                            $conn->rollback();
                            echo "<p class='error'>Hubo un error al actualizar los saldos. Por favor, inténtelo nuevamente.</p>";
                        }
                    } else {
                        // Si no existe la cuenta destino
                        echo "<p class='error'>La cuenta destino no existe.</p>";
                        $conn->rollback();
                    }
                } else {
                    echo "<p class='error'>No tienes suficiente saldo para realizar esta transacción.</p>";
                }
            }

            // Mostrar el saldo disponible del cliente
            echo "<h2><strong>Saldo Disponible:</strong> $" . $cliente['Saldo_Disponible'] . "</h2>";

            // Mostrar el formulario para enviar dinero
            echo '
            <h3>Enviar Dinero</h3>
            <form method="POST" action="envia.php">
                <label for="cuenta_destino">Cuenta Destino:</label>
                <input type="text" id="cuenta_destino" name="cuenta_destino" required><br>

                <label for="monto">Monto a Enviar:</label>
                <input type="number" id="monto" name="monto" required><br>

                <p><strong>Descripción:</strong> Envío de dinero a cuenta ' . (isset($_POST["cuenta_destino"]) ? $_POST["cuenta_destino"] : '') . '</p>
                <p><strong>Fecha de Transacción:</strong> ' . date("Y-m-d H:i:s") . '</p>

                <input type="submit" name="enviar" value="Enviar Dinero">
            </form>';

            // Consulta para obtener las transacciones del cliente
            $stmt_transacciones = $conn->prepare("SELECT Cuenta_Destino, Monto, Descripcion, Fecha_Transaccion FROM transacciones WHERE Id_Cliente = ? ORDER BY Fecha_Transaccion DESC");
            $stmt_transacciones->bind_param("i", $cliente['Id_Cliente']);
            $stmt_transacciones->execute();
            $result_transacciones = $stmt_transacciones->get_result();

            // Mostrar las transacciones realizadas
            if ($result_transacciones->num_rows > 0) {
                echo "<h3>Transacciones Realizadas</h3>";
                echo "<table>
                        <tr>
                            <th>Cuenta Destino</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Fecha de Transacción</th>
                        </tr>";

                while ($row = $result_transacciones->fetch_assoc()) {
                    echo "<tr>
                            <td>" . $row['Cuenta_Destino'] . "</td>
                            <td>$" . $row['Monto'] . "</td>
                            <td>" . $row['Descripcion'] . "</td>
                            <td>" . $row['Fecha_Transaccion'] . "</td>
                          </tr>";
                }

                echo "</table>";
            } else {
                echo "<p>No hay transacciones para mostrar.</p>";
            }

            // Botón de regresar
            echo "<a href='iniciar_sesion.php' class='regresar-btn'>Regresar</a>";
        } else {
            // Si no ha iniciado sesión, redirigir al inicio de sesión
            header("Location: iniciar_sesion.php");
            exit();
        }

        // Cerrar la conexión
        $conn->close();
        ?>
    </div>
</body>
</html>

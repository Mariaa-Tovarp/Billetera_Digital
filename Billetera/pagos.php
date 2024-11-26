<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        /* Estilos generales */
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7fc;
    color: #333;
    margin: 0;
    padding: 20px;
}

h3 {
    color: #2980b9;
    text-align: center;
}

/* Estilo de los formularios */
form {
    background-color: #fafafa;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    max-width: 400px;
    margin: 20px auto;
}

form label {
    display: block;
    font-size: 14px;
    margin-bottom: 8px;
}

form input, form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    box-sizing: border-box;
}

form input[type="submit"] {
    background-color: #2ecc71;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 16px;
}

form input[type="submit"]:hover {
    background-color: #27ae60;
}

/* Estilo de las tablas */
table {
    width: 100%;
    margin-top: 20px;
    border-collapse: collapse;
    background-color: #ffffff;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

table th {
    background-color: #2980b9;
    color: white;
}

table tr:hover {
    background-color: #f4f7fc;
}

/* Estilos de los mensajes */
.success {
    color: #27ae60;
    font-weight: bold;
}

.error {
    color: #e74c3c;
    font-weight: bold;
}

/* Botón regresar */
.regresar-btn {
    display: inline-block;
    margin-top: 20px;
    background-color: #3498db;
    color: white;
    padding: 10px 20px;
    text-decoration: none;
    border-radius: 4px;
    font-size: 16px;
}

.regresar-btn:hover {
    background-color: #2980b9;
}
    </style>
</head>
<body>
    
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
    $documento_destino = mysqli_real_escape_string($conn, $_POST["documento_destino"]);
    $monto = mysqli_real_escape_string($conn, $_POST["monto"]);
    $detalle_referencia = mysqli_real_escape_string($conn, $_POST["detalle_referencia"]);
    $medio_transaccion = mysqli_real_escape_string($conn, $_POST["medio_transaccion"]);

    // Validar que el saldo disponible sea suficiente para la transacción
    if ($cliente['Saldo_Disponible'] >= $monto) {
        // Iniciar transacción
        $conn->begin_transaction();

        // Verificar si el tipo de transacción 'PagoServicio' existe
        $sql_check_tipo_transaccion = "SELECT Id_TipoTransaccion FROM tipo_transaccion WHERE Descripcion = 'PagoServicio'";
        $result_check = $conn->query($sql_check_tipo_transaccion);

        if ($result_check->num_rows == 0) {
            // Si no existe, insertar el tipo de transacción
            $sql_insert_tipo_transaccion = "INSERT INTO tipo_transaccion (Id_TipoTransaccion, Descripcion) VALUES (4, 'PagoServicio')";
            if (!$conn->query($sql_insert_tipo_transaccion)) {
                // Si falla la inserción del tipo de transacción, deshacer los cambios
                $conn->rollback();
                echo "<p class='error'>Error al insertar el tipo de transacción.</p>";
                exit();
            }
        }

        // Verificar si la cuenta destino existe
        $sql_check_destino = "SELECT Id_Cliente, Saldo_Disponible, Documento FROM clientes WHERE Telefono = ?";
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
                $sql_insertar_transaccion = "INSERT INTO transacciones (Id_Cliente, Cuenta_Destino, Documento_Destino, Detalle_Referencia, Monto, Descripcion, Fecha_Transaccion, Id_TipoTransaccion, Medio_Transaccion) 
                                             VALUES (?, ?, ?, ?, ?, 'Pago de servicio', NOW(), 4, ?)";
                $stmt_transaccion = $conn->prepare($sql_insertar_transaccion);
                $stmt_transaccion->bind_param("issdss", $cliente['Id_Cliente'], $cuenta_destino, $documento_destino, $monto, $detalle_referencia, $medio_transaccion);

                if ($stmt_transaccion->execute()) {
                    // Confirmar la transacción
                    $conn->commit();
                    echo "<p class='success'>El pago fue realizado con éxito.</p>";
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
        echo "<p class='error'>No tienes suficiente saldo para realizar el pago.</p>";
    }
}

// Mostrar el formulario para el pago del servicio
echo '
<h3>Realizar Pago de Servicio</h3>
<form method="POST" action="pagos.php">
    <label for="cuenta_destino">Cuenta Destino:</label>
    <input type="text" id="cuenta_destino" name="cuenta_destino" required><br>

    <label for="documento_destino">Documento/NIT de la Cuenta Destino:</label>
    <input type="text" id="documento_destino" name="documento_destino" required><br>

    <label for="monto">Monto a Pagar:</label>
    <input type="number" id="monto" name="monto" required><br>

    <label for="detalle_referencia">Referencia:</label>
    <select name="detalle_referencia" required>
        <option value="AIRE">AIRE</option>
        <option value="TRIPLE AA">TRIPLE AA</option>
    </select><br>

    <label for="medio_transaccion">Medio de Transacción:</label>
    <select name="medio_transaccion" required>
        <option value="APP VIRTUAL">APP VIRTUAL</option>
        <option value="CORRESPONSAL">CORRESPONSAL</option>
        <option value="OFICINA FISICA">OFICINA FISICA</option>
    </select><br>

    <p><strong>Fecha de Pago:</strong> ' . date("Y-m-d H:i:s") . '</p>

    <input type="submit" name="enviar" value="Pagar Servicio">
</form>';

// Consulta para obtener las transacciones del cliente
$sql_transacciones = "
SELECT 
    transacciones.Cuenta_Destino, 
    transacciones.Documento_Destino, 
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

// Mostrar los resultados
echo "<h3>Transacciones Realizadas</h3>";
if ($result_transacciones->num_rows > 0) {
    echo "<table>
            <tr>
                <th>Cuenta Destino</th>
                <th>Documento/NIT</th>
                <th>Referencia</th>
                <th>Total</th>
                <th>Concepto</th>
                <th>Fecha de Pago</th>
                <th>Medio de Transacción</th>
            </tr>";

    while ($transaccion = $result_transacciones->fetch_assoc()) {
        echo "<tr>
                <td>" . $transaccion['Cuenta_Destino'] . "</td>
                <td>" . $transaccion['Documento_Destino'] . "</td>
                <td>" . $transaccion['Detalle_Referencia'] . "</td>
                <td>$" . $transaccion['Monto'] . "</td>
                <td>" . $transaccion['Descripcion'] . "</td>
                <td>" . $transaccion['Fecha_Transaccion'] . "</td>
                <td>" . $transaccion['Medio_Transaccion'] . "</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "<p>No hay transacciones para mostrar.</p>";
}

// Mostrar el saldo disponible del cliente
echo "<p><strong>Saldo Disponible:</strong> $" . $cliente['Saldo_Disponible'] . "</p>";

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

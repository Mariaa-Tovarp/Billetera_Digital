<?php
// Conexión a la base de datos
require_once 'conexion.php'; // Este archivo debe contener tu conexión a la base de datos

// Lógica para realizar la consignación
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validación y escape de entradas
    $telefono = trim($_POST['telefono']);
    $monto = (float) trim($_POST['monto']);
    $ubicacion = trim($_POST['ubicacion']);
    $medioTransaccion = trim($_POST['medioTransaccion']);

    // Verificar que los campos no estén vacíos y que el monto sea válido
    if (empty($telefono) || empty($ubicacion) || empty($medioTransaccion) || $monto <= 0) {
        echo "<p>Error: Por favor complete todos los campos correctamente.</p>";
        exit;
    }

    // Valores predeterminados
    $cuentaDestino = ""; // Determina si es necesario en tu sistema
    $documentoDestino = ""; // Similar al campo cuentaDestino
    $detalleReferencia = ""; // Puedes usar un valor predefinido
    $descripcion = "Consignación realizada";
    $idTipoTransaccion = 1; // Verifica este ID en tu tabla de tipos de transacciones

    // Fecha actual
    $fechaTransaccion = date('Y-m-d H:i:s');

    // Obtener el ID_Cliente y el saldo disponible
    $query = "SELECT ID_Cliente, Saldo_Disponible FROM clientes WHERE Telefono = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $telefono);
    $stmt->execute();
    $clienteResult = $stmt->get_result();

    if ($clienteResult->num_rows > 0) {
        // Cliente encontrado
        $cliente = $clienteResult->fetch_assoc();
        $idCliente = $cliente['ID_Cliente'];
        $saldoCliente = $cliente['Saldo_Disponible'];

        // Iniciar transacción para evitar errores en caso de fallo
        $conn->begin_transaction();

        try {
            // Registrar la transacción
            $insertTransaccion = "INSERT INTO transacciones (Cuenta_Destino, Documento_Destino, Detalle_Referencia, Monto, Descripcion, Medio_Transaccion, Ubicacion, Fecha_Transaccion, Id_Cliente, Id_TipoTransaccion)
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insertTransaccion);
            $stmt->bind_param("sssdsssdii", $cuentaDestino, $documentoDestino, $detalleReferencia, $monto, $descripcion, $medioTransaccion, $ubicacion, $fechaTransaccion, $idCliente, $idTipoTransaccion);
            $stmt->execute();

            // Actualizar saldo del cliente
            $nuevoSaldo = $saldoCliente + $monto;
            $updateSaldo = "UPDATE clientes SET Saldo_Disponible = ? WHERE Telefono = ?";
            $stmt = $conn->prepare($updateSaldo);
            $stmt->bind_param("ds", $nuevoSaldo, $telefono);
            $stmt->execute();

            // Confirmar transacción
            $conn->commit();

            // Mensaje de éxito
            echo "<p>Consignación realizada exitosamente. Nuevo saldo: $" . number_format($nuevoSaldo, 2) . "</p>";
        } catch (Exception $e) {
            // Revertir cambios en caso de error
            $conn->rollback();
            echo "<p>Error: No se pudo realizar la consignación. Por favor, intente de nuevo.</p>";
        }
    } else {
        // Cliente no encontrado
        echo "<p>Error: No se encontró un cliente con ese número de teléfono.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consignar Dinero</title>
    <style>
        /* Estilos básicos */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .main-container {
            width: 100%;
            max-width: 600px;
            padding: 25px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        h1, h2 {
            color: #2980b9;
            margin-bottom: 20px;
        }

        label {
            font-weight: bold;
            display: block;
            margin: 15px 0 5px;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }

        button {
            background-color: #3498db;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s;
            border: none;
            font-size: 18px;
        }

        button:hover {
            background-color: #2980b9;
        }

        a {
            text-decoration: none;
            color: #2980b9;
            display: block;
            text-align: center;
            margin-top: 15px;
            font-weight: bold;
        }

        /* Tabla de transacciones */
        table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #2980b9;
            color: white;
        }

        /* Estilo de fila */
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        /* Botón Regresar con mismo estilo que Consignar */
        a.btn-regresar {
            background-color: #3498db;
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-align: center;
            display: inline-block;
            transition: background-color 0.3s ease;
            text-decoration: none;
        }

        a.btn-regresar:hover {
            background-color: #2980b9;
        }

        .saldo-container {
            background-color: #ecf6fd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
            font-size: 18px;
        }
    </style>
    <script>
        function confirmarConsignacion() {
            var telefono = document.getElementById('telefono').value;
            var monto = document.getElementById('monto').value;
            var ubicacion = document.getElementById('ubicacion').value;
            var medioTransaccion = document.getElementById('medioTransaccion').value;

            var mensaje = "¿Estás seguro de realizar la consignación?\n\n";
            mensaje += "Teléfono: " + telefono + "\n";
            mensaje += "Monto: $" + monto + "\n";
            mensaje += "Ubicación: " + ubicacion + "\n";
            mensaje += "Medio de Transacción: " + medioTransaccion + "\n";

            return confirm(mensaje);
        }
    </script>
</head>
<body>

<div class="main-container">
    <h1>Consignaciones</h1>
    <form action="consignar.php" method="POST" onsubmit="return confirmarConsignacion()">
        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" required>

        <label for="monto">Monto</label>
        <input type="number" id="monto" name="monto" required>

        <label for="ubicacion">Ubicación</label>
        <input type="text" id="ubicacion" name="ubicacion" required>

        <label for="medioTransaccion">Medio</label>
        <select id="medioTransaccion" name="medioTransaccion" required>
            <option value="Corresponsal">Corresponsal</option>
            <option value="Oficina Física">Oficina Física</option>
        </select>

        <button type="submit">Realizar Consignación</button>
    </form>

    <div class="saldo-container">
        <p><strong>Saldo Actual:</strong> $10.00</p>
    </div>

    <h2>Lista de Transacciones</h2>
    <table>
        <tr>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Descripción</th>
            <th>Medio de Transacción</th>
        </tr>
        <tr>
            <td>2024-11-26 03:10:03</td>
            <td>$10.00</td>
            <td>Envío de dinero</td>
            <td>Corresponsal</td>
        </tr>
        <tr>
            <td>2024-11-26 03:08:03</td>
            <td>$10.00</td>
            <td>Recarga de servicio</td>
            <td>APP VIRTUAL</td>
        </tr>
        <!-- Más transacciones aquí -->
    </table>

    <a href="iniciar_sesion.php" class="btn-regresar">Regresar</a>
</div>

</body>
</html>

<?php
// Datos de conexión
$host = "mysql";
$user = "user-lemp";
$pass = "user123";
$db = "db-lemp";

// Conectar
$conn = mysqli_connect($host, $user, $pass, $db);

// Verificar conexión
if (!$conn) {
    die("Error de conexión: " . mysqli_connect_error());
}

echo "<h1>Conexión exitosa a MySQL</h1>";

// Crear tabla (si no existe)
$sql_create = "CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    precio DECIMAL(10,2) NOT NULL
)";

if (mysqli_query($conn, $sql_create)) {
    echo "<p>Tabla 'productos' lista</p>";
} else {
    echo "<p>Error creando tabla: " . mysqli_error($conn) . "</p>";
}

// Insertar datos (solo si no existen)
$sql_insert = "INSERT INTO productos (nombre, precio) VALUES 
    ('Camara', 340),
    ('Monitor', 350),
    ('Piano', 1999)";

// Verificar si ya hay datos para no duplicar
$check = mysqli_query($conn, "SELECT COUNT(*) as total FROM productos");
$row = mysqli_fetch_assoc($check);

if ($row['total'] == 0) {
    if (mysqli_query($conn, $sql_insert)) {
        echo "<p>Productos insertados</p>";
    } else {
        echo "<p>Error insertando: " . mysqli_error($conn) . "</p>";
    }
} else {
    echo "<p>Ya hay " . $row['total'] . " productos en la base de datos</p>";
}

// Consultar y mostrar productos
$sql_select = "SELECT * FROM productos";
$result = mysqli_query($conn, $sql_select);

echo "<h2>Productos en la base de datos:</h2>";
echo "<table border='1' style='border-collapse: collapse; padding: 10px;'>";
echo "<tr><th>ID</th><th>Nombre</th><th>Precio</th></tr>";

while ($producto = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>" . $producto['id'] . "</td>";
    echo "<td>" . $producto['nombre'] . "</td>";
    echo "<td>$" . $producto['precio'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Cerrar conexión
$conn->close();
?>

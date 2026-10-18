<?php
ini_set("display_errors", "0");
header("Content-Type: text/plain");
$conn=null;
try {
  // Datos de conexión
  $host = "mysql";
  $user = getenv("MYSQL_USER");
  $pass = getenv("MYSQL_PASSWORD");
  $db   = getenv("MYSQL_DATABASE");
  // Conectar
  $conn = mysqli_connect($host, $user, $pass, $db);
  mysqli_query($conn, "SELECT 1");
  http_response_code(200);
  echo "OK";
  $conn->close();
}

catch (Exception $e) {
  http_response_code(503);
  error_log($e->getMessage());
  echo "FALLO";
}
?>

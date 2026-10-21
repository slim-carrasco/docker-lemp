<?php
defined('RECETARIO') or exit;

if ($method !== 'GET') {
    jsonOut(['error' => 'Metodo no soportado'], 405);
}

// Sin esto, GROUP_CONCAT se corta a 1024 caracteres
$pdo->exec("SET SESSION group_concat_max_len = 8192");

$carpetas = $pdo->query("SELECT * FROM carpetas ORDER BY nombre")->fetchAll();
$recetas  = $pdo->query("
    SELECT r.*, c.nombre AS carpeta_nombre, c.color AS carpeta_color,
           (SELECT COUNT(*) FROM ingredientes WHERE receta_id = r.id) AS n_ingredientes,
           (SELECT COUNT(*) FROM pasos        WHERE receta_id = r.id) AS n_pasos,
           (SELECT COUNT(*) FROM errores      WHERE receta_id = r.id) AS n_errores,
           (SELECT GROUP_CONCAT(nombre SEPARATOR '|') FROM ingredientes WHERE receta_id = r.id) AS ingredientes_txt
    FROM recetas r
    LEFT JOIN carpetas c ON c.id = r.carpeta_id
    ORDER BY r.fecha DESC
")->fetchAll();

foreach ($recetas as &$r) {
    $r['altitud'] = (bool)$r['altitud'];
}
unset($r);

jsonOut(compact('carpetas', 'recetas'));

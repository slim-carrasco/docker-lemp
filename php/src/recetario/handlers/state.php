<?php
defined('RECETARIO') or exit;

// Sin esto, GROUP_CONCAT se corta a 1024 caracteres
$pdo->exec("SET SESSION group_concat_max_len = 8192");

$st = $pdo->prepare("SELECT id, nombre, descripcion, color, fecha FROM carpetas WHERE local_id = ? ORDER BY nombre");
$st->execute([$usuario['local_id']]);
$carpetas = $st->fetchAll();

$st = $pdo->prepare("
    SELECT r.*, c.nombre AS carpeta_nombre, c.color AS carpeta_color,
           (SELECT COUNT(*) FROM ingredientes WHERE receta_id = r.id) AS n_ingredientes,
           (SELECT COUNT(*) FROM pasos        WHERE receta_id = r.id) AS n_pasos,
           (SELECT COUNT(*) FROM errores      WHERE receta_id = r.id) AS n_errores,
           (SELECT GROUP_CONCAT(nombre SEPARATOR '|') FROM ingredientes WHERE receta_id = r.id) AS ingredientes_txt,
           (SELECT COUNT(*) FROM menu_dia m WHERE m.receta_id = r.id AND m.fecha = ?) AS en_menu
    FROM recetas r
    LEFT JOIN carpetas c ON c.id = r.carpeta_id
    WHERE r.local_id = ?
    ORDER BY r.fecha DESC
");
$st->execute([date('Y-m-d'), $usuario['local_id']]);
$recetas = $st->fetchAll();

foreach ($recetas as &$r) {
    $r['altitud']        = (bool)$r['altitud'];
    $r['disponible_hoy'] = (int)$r['en_menu'] > 0;
    $r['precio_venta']   = ($usuario['rol'] === 'ayudante' || $r['precio_venta'] === null)
        ? null : (float)$r['precio_venta'];
    unset($r['en_menu'], $r['local_id']);
}
unset($r);

jsonOut(compact('carpetas', 'recetas'));

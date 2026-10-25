<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];
$hoy     = date('Y-m-d');

switch ($method) {
    case 'GET':
        $st = $pdo->prepare(
            "SELECT r.id, r.nombre, r.descripcion, r.precio_venta
             FROM menu_dia m JOIN recetas r ON r.id = m.receta_id
             WHERE m.fecha = ? AND r.local_id = ?
             ORDER BY r.nombre"
        );
        $st->execute([$hoy, $localId]);
        $platos = $st->fetchAll();
        foreach ($platos as &$p) {
            $p['precio_venta'] = ($usuario['rol'] === 'ayudante' || $p['precio_venta'] === null)
                ? null : (float)$p['precio_venta'];
        }
        unset($p);
        jsonOut(['fecha' => $hoy, 'platos' => $platos]);

    case 'POST':
        $rid = (int)($body['receta_id'] ?? 0);
        if (!$rid || !existeEnLocal($pdo, 'recetas', $rid, $localId)) {
            fallar('Receta no encontrada', 404);
        }
        $pdo->prepare("INSERT IGNORE INTO menu_dia (receta_id, fecha) VALUES (?, ?)")->execute([$rid, $hoy]);
        jsonOut(['ok' => true]);

    case 'DELETE':
        if (!$id || !existeEnLocal($pdo, 'recetas', $id, $localId)) {
            fallar('Receta no encontrada', 404);
        }
        $pdo->prepare("DELETE FROM menu_dia WHERE receta_id = ? AND fecha = ?")->execute([$id, $hoy]);
        jsonOut(['ok' => true]);
}

fallar('Método no soportado', 405);

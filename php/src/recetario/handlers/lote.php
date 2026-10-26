<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];

if (!is_array($body) || !isset($body['ids']) || !is_array($body['ids'])) {
    fallar('Se espera una lista de ids', 400);
}
$ids = array_values(array_filter(
    array_unique(array_map('intval', $body['ids'])),
    fn($v) => $v > 0
));
if (count($ids) === 0 || count($ids) > 500) {
    fallar('Selecciona entre 1 y 500 recetas', 400);
}
$marcas = implode(',', array_fill(0, count($ids), '?'));

// Todas deben ser del local; si alguna no lo es, no se toca ninguna
$st = $pdo->prepare("SELECT COUNT(*) FROM recetas WHERE local_id = ? AND id IN ($marcas)");
$st->execute(array_merge([$localId], $ids));
if ((int)$st->fetchColumn() !== count($ids)) {
    fallar('Alguna receta no existe', 404);
}

if ($entity === 'mover') {
    $cid = null;
    if (!empty($body['carpeta_id'])) {
        $cid = (int)$body['carpeta_id'];
        if (!existeEnLocal($pdo, 'carpetas', $cid, $localId)) {
            fallar('Carpeta no válida', 400);
        }
    }
    $st = $pdo->prepare("UPDATE recetas SET carpeta_id = ? WHERE local_id = ? AND id IN ($marcas)");
    $st->execute(array_merge([$cid, $localId], $ids));
    jsonOut(['ok' => true, 'afectadas' => count($ids)]);
}

// eliminar: más de 5 recetas exige confirmar escribiendo ELIMINAR
if (count($ids) > 5 && ($body['confirmar'] ?? '') !== 'ELIMINAR') {
    fallar('Escribe ELIMINAR para confirmar', 400);
}
$st = $pdo->prepare("DELETE FROM recetas WHERE local_id = ? AND id IN ($marcas)");
$st->execute(array_merge([$localId], $ids));
jsonOut(['ok' => true, 'afectadas' => count($ids)]);

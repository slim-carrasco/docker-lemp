<?php
defined('RECETARIO') or exit;

if ($method !== 'POST') {
    jsonOut(['error' => 'Metodo no soportado'], 405);
}
if (!is_array($body) || !array_is_list($body) || count($body) === 0) {
    jsonOut(['error' => 'Se espera un arreglo de recetas'], 400);
}
// Validar todo antes de abrir la transaccion
foreach ($body as $r) {
    exigirNombre($r);
}

$ids = transaccion($pdo, function () use ($pdo, $body) {
    $ids = [];
    foreach ($body as $r) {
        $ids[] = insertReceta($pdo, $r);
    }
    return $ids;
});

jsonOut(['ok' => true, 'imported' => count($ids), 'ids' => $ids]);

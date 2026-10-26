<?php
defined('RECETARIO') or exit;

if (!is_array($body) || !array_is_list($body) || count($body) === 0) {
    fallar('Se espera un arreglo de recetas', 400);
}
if (count($body) > 500) {
    fallar('Máximo 500 recetas por importación', 400);
}
// Validar todo antes de abrir la transacción
foreach ($body as $r) {
    validarReceta($r);
}

$ids = transaccion($pdo, function () use ($pdo, $body, $usuario) {
    $ids = [];
    foreach ($body as $r) {
        $ids[] = insertReceta($pdo, $r, $usuario);
    }
    return $ids;
});

jsonOut(['ok' => true, 'imported' => count($ids), 'ids' => $ids]);

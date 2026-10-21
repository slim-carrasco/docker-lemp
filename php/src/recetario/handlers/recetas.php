<?php
defined('RECETARIO') or exit;

// GET de una receta con todos sus hijos
if ($entity === 'receta') {
    if ($method !== 'GET' || !$id) {
        jsonOut(['error' => 'Solicitud invalida'], 400);
    }
    $rec = fetchReceta($pdo, $id);
    if (!$rec) jsonOut(['error' => 'No encontrada'], 404);
    jsonOut($rec);
}

// CRUD de recetas
switch ($method) {
    case 'POST':
        exigirNombre($body);
        $rid = transaccion($pdo, fn() => insertReceta($pdo, $body));
        jsonOut(['id' => $rid, 'receta' => fetchReceta($pdo, $rid)]);

    case 'PUT':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        exigirNombre($body);
        transaccion($pdo, function () use ($pdo, $id, $body) {
            $pdo->prepare(
                "UPDATE recetas
                 SET carpeta_id=?, nombre=?, descripcion=?, porciones_base=?, altitud=?, notas=?
                 WHERE id=?"
            )->execute([
                ($body['carpeta_id'] ?? null) ?: null,
                $body['nombre'],
                $body['descripcion'] ?? null,
                $body['porciones_base'] ?? 4,
                !empty($body['altitud']) ? 1 : 0,
                $body['notas'] ?? null,
                $id,
            ]);
            $pdo->prepare("DELETE FROM ingredientes WHERE receta_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM pasos        WHERE receta_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM errores      WHERE receta_id=?")->execute([$id]);
            guardarHijos($pdo, $id, $body);
        });
        jsonOut(['ok' => true, 'receta' => fetchReceta($pdo, $id)]);

    case 'DELETE':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        $pdo->prepare("DELETE FROM recetas WHERE id=?")->execute([$id]);
        jsonOut(['ok' => true]);
}

jsonOut(['error' => 'Metodo no soportado'], 405);

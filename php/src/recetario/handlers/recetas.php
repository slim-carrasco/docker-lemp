<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];

// GET de una receta con todos sus hijos
if ($entity === 'receta') {
    if (!$id) {
        fallar('Solicitud inválida', 400);
    }
    $rec = fetchReceta($pdo, $id, $localId);
    if (!$rec) {
        fallar('No encontrada', 404);
    }
    jsonOut($usuario['rol'] === 'ayudante' ? ocultarPreciosReceta($rec) : $rec);
}

switch ($method) {
    case 'POST':
        validarReceta($body);
        $rid = transaccion($pdo, fn() => insertReceta($pdo, $body, $usuario));
        jsonOut(['id' => $rid, 'receta' => fetchReceta($pdo, $rid, $localId)]);

    case 'PUT':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        $st = $pdo->prepare("SELECT precio_venta FROM recetas WHERE id = ? AND local_id = ?");
        $st->execute([$id, $localId]);
        $actual = $st->fetch();
        if (!$actual) {
            fallar('No encontrada', 404);
        }
        validarReceta($body);
        // Si el cliente no envía precio_venta, se conserva el actual
        $precioVenta = array_key_exists('precio_venta', $body)
            ? numOrNull($body['precio_venta'])
            : $actual['precio_venta'];

        transaccion($pdo, function () use ($pdo, $id, $body, $localId, $precioVenta) {
            $pdo->prepare(
                "UPDATE recetas
                 SET carpeta_id=?, nombre=?, descripcion=?, porciones_base=?, altitud=?, notas=?, precio_venta=?
                 WHERE id=? AND local_id=?"
            )->execute([
                resolverCarpeta($pdo, $body, $localId),
                trim($body['nombre']),
                $body['descripcion'] ?? null,
                (int)($body['porciones_base'] ?? 4) ?: 4,
                !empty($body['altitud']) ? 1 : 0,
                $body['notas'] ?? null,
                $precioVenta,
                $id, $localId,
            ]);
            $pdo->prepare("DELETE FROM ingredientes WHERE receta_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM pasos        WHERE receta_id=?")->execute([$id]);
            $pdo->prepare("DELETE FROM errores      WHERE receta_id=?")->execute([$id]);
            guardarHijos($pdo, $id, $body);
        });
        jsonOut(['ok' => true, 'receta' => fetchReceta($pdo, $id, $localId)]);

    case 'DELETE':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!existeEnLocal($pdo, 'recetas', $id, $localId)) {
            fallar('No encontrada', 404);
        }
        $pdo->prepare("DELETE FROM recetas WHERE id=? AND local_id=?")->execute([$id, $localId]);
        jsonOut(['ok' => true]);
}

fallar('Método no soportado', 405);

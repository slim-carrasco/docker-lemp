<?php
defined('RECETARIO') or exit;

switch ($method) {
    case 'POST':
        exigirNombre($body);
        $pdo->prepare("INSERT INTO carpetas (nombre, descripcion, color) VALUES (?,?,?)")
            ->execute([$body['nombre'], $body['descripcion'] ?? null, $body['color'] ?? '#4a9eff']);
        jsonOut(['id' => (int)$pdo->lastInsertId()]);

    case 'PUT':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        exigirNombre($body);
        $pdo->prepare("UPDATE carpetas SET nombre=?, descripcion=?, color=? WHERE id=?")
            ->execute([$body['nombre'], $body['descripcion'] ?? null, $body['color'] ?? '#4a9eff', $id]);
        jsonOut(['ok' => true]);

    case 'DELETE':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        // Las recetas quedan con carpeta_id NULL por el ON DELETE SET NULL
        $pdo->prepare("DELETE FROM carpetas WHERE id=?")->execute([$id]);
        jsonOut(['ok' => true]);
}

jsonOut(['error' => 'Metodo no soportado'], 405);

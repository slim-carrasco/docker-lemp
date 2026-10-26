<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];

function colorValido($body): string {
    $c = (string)($body['color'] ?? '');
    return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#4a9eff';
}

switch ($method) {
    case 'POST':
        exigirNombre($body, 100);
        $pdo->prepare("INSERT INTO carpetas (local_id, nombre, descripcion, color) VALUES (?,?,?,?)")
            ->execute([
                $localId,
                trim($body['nombre']),
                isset($body['descripcion']) ? mb_substr((string)$body['descripcion'], 0, 255) : null,
                colorValido($body),
            ]);
        jsonOut(['id' => (int)$pdo->lastInsertId()]);

    case 'PUT':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!existeEnLocal($pdo, 'carpetas', $id, $localId)) {
            fallar('No encontrada', 404);
        }
        exigirNombre($body, 100);
        $pdo->prepare("UPDATE carpetas SET nombre=?, descripcion=?, color=? WHERE id=? AND local_id=?")
            ->execute([
                trim($body['nombre']),
                isset($body['descripcion']) ? mb_substr((string)$body['descripcion'], 0, 255) : null,
                colorValido($body),
                $id, $localId,
            ]);
        jsonOut(['ok' => true]);

    case 'DELETE':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!existeEnLocal($pdo, 'carpetas', $id, $localId)) {
            fallar('No encontrada', 404);
        }
        // Las recetas quedan con carpeta_id NULL por el ON DELETE SET NULL
        $pdo->prepare("DELETE FROM carpetas WHERE id=? AND local_id=?")->execute([$id, $localId]);
        jsonOut(['ok' => true]);
}

fallar('Método no soportado', 405);

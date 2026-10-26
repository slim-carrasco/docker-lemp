<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];

function exigirPrecio($body): void {
    exigirNombre($body, 200);
    if (!isset($body['precio'], $body['unidad']) || !is_numeric($body['precio']) || (float)$body['precio'] < 0
        || trim((string)$body['unidad']) === '') {
        fallar('Precio (número no negativo) y unidad son obligatorios', 400);
    }
    if (mb_strlen((string)$body['unidad']) > 40) {
        fallar('La unidad no puede superar 40 caracteres', 400);
    }
}

switch ($method) {
    case 'GET':
        $st = $pdo->prepare(
            "SELECT id, nombre, precio, unidad, categoria, notas, actualizado
             FROM precios WHERE local_id = ? ORDER BY categoria, nombre"
        );
        $st->execute([$localId]);
        jsonOut($st->fetchAll());

    case 'POST':
        exigirPrecio($body);
        $pdo->prepare("INSERT INTO precios (local_id, nombre, precio, unidad, categoria, notas) VALUES (?,?,?,?,?,?)")
            ->execute([
                $localId, trim($body['nombre']), $body['precio'], trim($body['unidad']),
                mb_substr((string)($body['categoria'] ?? 'Otros'), 0, 50) ?: 'Otros',
                $body['notas'] ?? null,
            ]);
        jsonOut(['id' => (int)$pdo->lastInsertId()]);

    case 'PUT':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!existeEnLocal($pdo, 'precios', $id, $localId)) {
            fallar('No encontrado', 404);
        }
        exigirPrecio($body);
        $pdo->prepare("UPDATE precios SET nombre=?, precio=?, unidad=?, categoria=?, notas=? WHERE id=? AND local_id=?")
            ->execute([
                trim($body['nombre']), $body['precio'], trim($body['unidad']),
                mb_substr((string)($body['categoria'] ?? 'Otros'), 0, 50) ?: 'Otros',
                $body['notas'] ?? null,
                $id, $localId,
            ]);
        jsonOut(['ok' => true]);

    case 'DELETE':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!existeEnLocal($pdo, 'precios', $id, $localId)) {
            fallar('No encontrado', 404);
        }
        $pdo->prepare("DELETE FROM precios WHERE id=? AND local_id=?")->execute([$id, $localId]);
        jsonOut(['ok' => true]);
}

fallar('Método no soportado', 405);

<?php
defined('RECETARIO') or exit;

function exigirPrecio($body): void {
    exigirNombre($body);
    if (!isset($body['precio'], $body['unidad']) || !is_numeric($body['precio']) || (float)$body['precio'] < 0) {
        jsonOut(['error' => 'Precio (numero no negativo) y unidad son obligatorios'], 400);
    }
}

switch ($method) {
    case 'GET':
        jsonOut($pdo->query("SELECT * FROM precios ORDER BY categoria, nombre")->fetchAll());

    case 'POST':
        exigirPrecio($body);
        $pdo->prepare("INSERT INTO precios (nombre, precio, unidad, categoria, notas) VALUES (?,?,?,?,?)")
            ->execute([$body['nombre'], $body['precio'], $body['unidad'], $body['categoria'] ?? 'Otros', $body['notas'] ?? null]);
        jsonOut(['id' => (int)$pdo->lastInsertId()]);

    case 'PUT':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        exigirPrecio($body);
        $pdo->prepare("UPDATE precios SET nombre=?, precio=?, unidad=?, categoria=?, notas=? WHERE id=?")
            ->execute([$body['nombre'], $body['precio'], $body['unidad'], $body['categoria'] ?? 'Otros', $body['notas'] ?? null, $id]);
        jsonOut(['ok' => true]);

    case 'DELETE':
        if (!$id) jsonOut(['error' => 'Falta el id'], 400);
        $pdo->prepare("DELETE FROM precios WHERE id=?")->execute([$id]);
        jsonOut(['ok' => true]);
}

jsonOut(['error' => 'Metodo no soportado'], 405);

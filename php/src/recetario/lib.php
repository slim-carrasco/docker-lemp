<?php
defined('RECETARIO') or exit;

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function numOrNull($v) {
    return ($v === null || $v === '') ? null : $v;
}

function exigirNombre($body): void {
    if (!is_array($body) || trim((string)($body['nombre'] ?? '')) === '') {
        jsonOut(['error' => 'El campo nombre es obligatorio'], 400);
    }
}

function transaccion(PDO $pdo, callable $fn) {
    $pdo->beginTransaction();
    try {
        $res = $fn();
        $pdo->commit();
        return $res;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

// Resuelve la carpeta por id o, si viene el nombre, la busca o la crea
function resolverCarpeta(PDO $pdo, array $r): ?int {
    if (!empty($r['carpeta_id'])) return (int)$r['carpeta_id'];
    $nombre = trim((string)($r['carpeta'] ?? ''));
    if ($nombre === '') return null;
    $st = $pdo->prepare("SELECT id FROM carpetas WHERE nombre = ?");
    $st->execute([$nombre]);
    $id = $st->fetchColumn();
    if ($id) return (int)$id;
    $pdo->prepare("INSERT INTO carpetas (nombre) VALUES (?)")->execute([$nombre]);
    return (int)$pdo->lastInsertId();
}

function guardarHijos(PDO $pdo, int $rid, array $r): void {
    $stIng = $pdo->prepare(
        "INSERT INTO ingredientes (receta_id, orden, nombre, cantidad, unidad, precio)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    foreach (($r['ingredientes'] ?? []) as $i => $ing) {
        $stIng->execute([
            $rid, $i,
            $ing['nombre'],
            numOrNull($ing['cantidad'] ?? null),
            $ing['unidad'] ?? null,
            numOrNull($ing['precio'] ?? null),
        ]);
    }

    $stPaso = $pdo->prepare(
        "INSERT INTO pasos (receta_id, orden, titulo, descripcion, timer)
         VALUES (?, ?, ?, ?, ?)"
    );
    foreach (($r['pasos'] ?? []) as $i => $p) {
        $stPaso->execute([
            $rid, $i,
            $p['titulo'] ?? null,
            $p['descripcion'],
            !empty($p['timer']) ? (int)$p['timer'] : null,
        ]);
    }

    $stErr = $pdo->prepare("INSERT INTO errores (receta_id, descripcion) VALUES (?, ?)");
    foreach (($r['errores'] ?? []) as $e) {
        $stErr->execute([$rid, $e]);
    }
}

function insertReceta(PDO $pdo, array $r): int {
    $pdo->prepare(
        "INSERT INTO recetas (carpeta_id, nombre, descripcion, porciones_base, altitud, notas, fecha)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        resolverCarpeta($pdo, $r),
        $r['nombre'],
        $r['descripcion'] ?? null,
        $r['porciones_base'] ?? 4,
        !empty($r['altitud']) ? 1 : 0,
        $r['notas'] ?? null,
        $r['fecha'] ?? date('Y-m-d H:i:s'),
    ]);
    $rid = (int)$pdo->lastInsertId();
    guardarHijos($pdo, $rid, $r);
    return $rid;
}

function fetchReceta(PDO $pdo, int $id): ?array {
    $st = $pdo->prepare("SELECT * FROM recetas WHERE id = ?");
    $st->execute([$id]);
    $rec = $st->fetch();
    if (!$rec) return null;

    $st = $pdo->prepare("SELECT * FROM ingredientes WHERE receta_id = ? ORDER BY orden");
    $st->execute([$id]);
    $rec['ingredientes'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT * FROM pasos WHERE receta_id = ? ORDER BY orden");
    $st->execute([$id]);
    $rec['pasos'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT descripcion FROM errores WHERE receta_id = ?");
    $st->execute([$id]);
    $rec['errores'] = array_column($st->fetchAll(), 'descripcion');

    $rec['altitud'] = (bool)$rec['altitud'];
    return $rec;
}

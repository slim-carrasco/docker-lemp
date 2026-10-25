<?php
defined('RECETARIO') or exit;

class ErrorApi extends Exception {}

function fallar(string $mensaje, int $codigo = 400): void {
    throw new ErrorApi($mensaje, $codigo);
}

function jsonOut($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function numOrNull($v) {
    return ($v === null || $v === '') ? null : $v;
}

function exigirNombre($body, int $max = 200): void {
    if (!is_array($body)) {
        fallar('Cuerpo de la solicitud inválido', 400);
    }
    $n = trim((string)($body['nombre'] ?? ''));
    if ($n === '') {
        fallar('El campo nombre es obligatorio', 400);
    }
    if (mb_strlen($n) > $max) {
        fallar("El nombre no puede superar $max caracteres", 400);
    }
}

function transaccion(PDO $pdo, callable $fn) {
    $pdo->beginTransaction();
    try {
        $res = $fn();
        $pdo->commit();
        return $res;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

// ¿La fila pertenece al local? Evita que un local toque datos de otro.
function existeEnLocal(PDO $pdo, string $tabla, int $id, int $localId): bool {
    if (!in_array($tabla, ['recetas', 'carpetas', 'precios'], true)) {
        throw new InvalidArgumentException('Tabla no permitida');
    }
    $st = $pdo->prepare("SELECT 1 FROM `$tabla` WHERE id = ? AND local_id = ?");
    $st->execute([$id, $localId]);
    return (bool)$st->fetchColumn();
}

// Resuelve la carpeta por id (del mismo local) o por nombre; la crea si no existe
function resolverCarpeta(PDO $pdo, array $r, int $localId): ?int {
    if (!empty($r['carpeta_id'])) {
        $cid = (int)$r['carpeta_id'];
        if (!existeEnLocal($pdo, 'carpetas', $cid, $localId)) {
            fallar('Carpeta no válida', 400);
        }
        return $cid;
    }
    $nombre = trim((string)($r['carpeta'] ?? ''));
    if ($nombre === '') {
        return null;
    }
    $nombre = mb_substr($nombre, 0, 100);
    $st = $pdo->prepare("SELECT id FROM carpetas WHERE nombre = ? AND local_id = ?");
    $st->execute([$nombre, $localId]);
    $id = $st->fetchColumn();
    if ($id) {
        return (int)$id;
    }
    $pdo->prepare("INSERT INTO carpetas (local_id, nombre) VALUES (?, ?)")->execute([$localId, $nombre]);
    return (int)$pdo->lastInsertId();
}

function validarReceta($r): void {
    exigirNombre($r, 200);
    foreach (['ingredientes', 'pasos', 'errores'] as $k) {
        if (isset($r[$k]) && !is_array($r[$k])) {
            fallar("El campo $k debe ser una lista", 400);
        }
    }
    if (isset($r['porciones_base']) && $r['porciones_base'] !== ''
        && (!is_numeric($r['porciones_base']) || (int)$r['porciones_base'] < 1 || (int)$r['porciones_base'] > 1000)) {
        fallar('Las porciones deben ser un número entre 1 y 1000', 400);
    }
    if (isset($r['precio_venta']) && $r['precio_venta'] !== ''
        && (!is_numeric($r['precio_venta']) || (float)$r['precio_venta'] < 0)) {
        fallar('El precio de venta debe ser un número igual o mayor que 0', 400);
    }
    foreach (($r['ingredientes'] ?? []) as $ing) {
        if (!is_array($ing) || trim((string)($ing['nombre'] ?? '')) === '') {
            fallar('Cada ingrediente necesita un nombre', 400);
        }
        if (mb_strlen((string)$ing['nombre']) > 200) {
            fallar('El nombre de un ingrediente no puede superar 200 caracteres', 400);
        }
        if (mb_strlen((string)($ing['unidad'] ?? '')) > 20) {
            fallar('La unidad no puede superar 20 caracteres', 400);
        }
        foreach (['cantidad', 'precio'] as $k) {
            if (isset($ing[$k]) && $ing[$k] !== ''
                && (!is_numeric($ing[$k]) || (float)$ing[$k] < 0)) {
                fallar('Las cantidades y los precios no pueden ser negativos', 400);
            }
        }
    }
    foreach (($r['pasos'] ?? []) as $p) {
        if (!is_array($p) || trim((string)($p['descripcion'] ?? '')) === '') {
            fallar('Cada paso necesita una descripción', 400);
        }
        if (mb_strlen((string)($p['titulo'] ?? '')) > 200) {
            fallar('El título de un paso no puede superar 200 caracteres', 400);
        }
    }
    foreach (($r['errores'] ?? []) as $e) {
        if (!is_string($e)) {
            fallar('Los errores deben ser texto', 400);
        }
    }
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

function insertReceta(PDO $pdo, array $r, array $u): int {
    $pdo->prepare(
        "INSERT INTO recetas (local_id, creado_por, carpeta_id, nombre, descripcion, porciones_base,
                              altitud, notas, precio_venta, fecha)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $u['local_id'],
        $u['id'],
        resolverCarpeta($pdo, $r, $u['local_id']),
        trim($r['nombre']),
        $r['descripcion'] ?? null,
        (int)($r['porciones_base'] ?? 4) ?: 4,
        !empty($r['altitud']) ? 1 : 0,
        $r['notas'] ?? null,
        numOrNull($r['precio_venta'] ?? null),
        $r['fecha'] ?? date('Y-m-d H:i:s'),
    ]);
    $rid = (int)$pdo->lastInsertId();
    guardarHijos($pdo, $rid, $r);
    return $rid;
}

// Devuelve null si la receta no existe o es de otro local
function fetchReceta(PDO $pdo, int $id, int $localId): ?array {
    $st = $pdo->prepare(
        "SELECT r.*, u.nombre AS creado_por_nombre,
                (SELECT COUNT(*) FROM menu_dia m WHERE m.receta_id = r.id AND m.fecha = ?) AS en_menu
         FROM recetas r
         LEFT JOIN usuarios u ON u.id = r.creado_por
         WHERE r.id = ? AND r.local_id = ?"
    );
    $st->execute([date('Y-m-d'), $id, $localId]);
    $rec = $st->fetch();
    if (!$rec) {
        return null;
    }

    $st = $pdo->prepare("SELECT * FROM ingredientes WHERE receta_id = ? ORDER BY orden");
    $st->execute([$id]);
    $rec['ingredientes'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT * FROM pasos WHERE receta_id = ? ORDER BY orden");
    $st->execute([$id]);
    $rec['pasos'] = $st->fetchAll();

    $st = $pdo->prepare("SELECT descripcion FROM errores WHERE receta_id = ?");
    $st->execute([$id]);
    $rec['errores'] = array_column($st->fetchAll(), 'descripcion');

    $rec['altitud']         = (bool)$rec['altitud'];
    $rec['disponible_hoy']  = (int)$rec['en_menu'] > 0;
    $rec['precio_venta']    = $rec['precio_venta'] !== null ? (float)$rec['precio_venta'] : null;
    unset($rec['en_menu'], $rec['local_id']);
    return $rec;
}

// El ayudante no ve ningún precio: se quitan en el servidor, no solo en pantalla
function ocultarPreciosReceta(array $rec): array {
    $rec['precio_venta'] = null;
    if (isset($rec['ingredientes'])) {
        foreach ($rec['ingredientes'] as &$i) {
            $i['precio'] = null;
        }
        unset($i);
    }
    return $rec;
}

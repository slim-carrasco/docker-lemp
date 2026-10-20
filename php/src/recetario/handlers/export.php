<?php
defined('RECETARIO') or exit;

if ($method !== 'GET') jsonOut(['error' => 'Metodo no soportado'], 405);

$carp = $pdo->query("SELECT id, nombre FROM carpetas")->fetchAll(PDO::FETCH_KEY_PAIR);
$ids  = $pdo->query("SELECT id FROM recetas ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

$out = [];
foreach ($ids as $rid) {
    $r = fetchReceta($pdo, (int)$rid);
    $out[] = [
        'nombre'         => $r['nombre'],
        'descripcion'    => $r['descripcion'],
        'carpeta'        => $carp[$r['carpeta_id']] ?? null,
        'porciones_base' => $r['porciones_base'],
        'altitud'        => $r['altitud'],
        'notas'          => $r['notas'],
        'fecha'          => $r['fecha'],
        'ingredientes'   => array_map(fn($i) => [
            'nombre' => $i['nombre'], 'cantidad' => $i['cantidad'],
            'unidad' => $i['unidad'], 'precio' => $i['precio']], $r['ingredientes']),
        'pasos'          => array_map(fn($p) => [
            'titulo' => $p['titulo'], 'descripcion' => $p['descripcion'],
            'timer' => $p['timer']], $r['pasos']),
        'errores'        => $r['errores'],
    ];
}
jsonOut($out);

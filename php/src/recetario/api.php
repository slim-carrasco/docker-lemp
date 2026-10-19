<?php
define('RECETARIO', true);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';

$method = $_SERVER['REQUEST_METHOD'];
$entity = $_GET['entity'] ?? '';
$id     = (int)($_GET['id'] ?? 0) ?: null;
$body   = json_decode(file_get_contents('php://input'), true);

// Lista cerrada: solo estos nombres pueden cargar un handler
$rutas = [
    'state'    => 'state',
    'receta'   => 'recetas',
    'recetas'  => 'recetas',
    'carpetas' => 'carpetas',
    'precios'  => 'precios',
    'import'   => 'import',
];

if (!isset($rutas[$entity])) {
    jsonOut(['error' => 'Entidad no encontrada'], 404);
}

try {
    require __DIR__ . '/handlers/' . $rutas[$entity] . '.php';
} catch (Throwable $e) {
    error_log('Recetario: ' . $e->getMessage());
    jsonOut(['error' => 'Error interno del servidor'], 500);
}

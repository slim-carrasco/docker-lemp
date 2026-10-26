<?php
define('RECETARIO', true);
ini_set('display_errors', '0');
date_default_timezone_set('America/Lima');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require __DIR__ . '/db.php';
require __DIR__ . '/lib.php';
require __DIR__ . '/auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$entity = is_string($_GET['entity'] ?? null) ? $_GET['entity'] : '';
$id     = (int)($_GET['id'] ?? 0) ?: null;
$body   = json_decode(file_get_contents('php://input'), true);

// Lista cerrada: entidad => archivo del controlador
$rutas = [
    'login'        => 'sesion',
    'logout'       => 'sesion',
    'me'           => 'sesion',
    'clave'        => 'sesion',
    'registro'     => 'registro',
    'state'        => 'state',
    'receta'       => 'recetas',
    'recetas'      => 'recetas',
    'carpetas'     => 'carpetas',
    'precios'      => 'precios',
    'import'       => 'import',
    'export'       => 'export',
    'usuarios'     => 'usuarios',
    'menu'         => 'menu',
    'menu_publico' => 'menu_publico',
    'ajustes'      => 'ajustes',
    'mover'        => 'lote',
    'eliminar'     => 'lote',
];

// entidad => método => '*' (público), 'auth' (cualquier sesión) o lista de roles
$permisos = [
    'login'        => ['POST' => '*'],
    'logout'       => ['POST' => '*'],
    'registro'     => ['GET' => '*', 'POST' => '*'],
    'menu_publico' => ['GET' => '*'],
    'me'           => ['GET' => 'auth'],
    'clave'        => ['POST' => 'auth'],
    'state'        => ['GET' => 'auth'],
    'receta'       => ['GET' => 'auth'],
    'recetas'      => ['POST' => ['dueno', 'chef'], 'PUT' => ['dueno', 'chef'], 'DELETE' => ['dueno']],
    'carpetas'     => ['POST' => ['dueno', 'chef'], 'PUT' => ['dueno', 'chef'], 'DELETE' => ['dueno', 'chef']],
    'precios'      => ['GET' => ['dueno', 'chef'], 'POST' => ['dueno', 'chef'], 'PUT' => ['dueno', 'chef'], 'DELETE' => ['dueno', 'chef']],
    'import'       => ['POST' => ['dueno']],
    'export'       => ['GET' => ['dueno']],
    'usuarios'     => ['GET' => ['dueno'], 'POST' => ['dueno'], 'PUT' => ['dueno']],
    'menu'         => ['GET' => 'auth', 'POST' => ['dueno', 'chef'], 'DELETE' => ['dueno', 'chef']],
    'ajustes'      => ['PUT' => ['dueno']],
    'mover'        => ['POST' => ['dueno', 'chef']],
    'eliminar'     => ['POST' => ['dueno']],
];

try {
    if (!isset($rutas[$entity])) {
        fallar('Entidad no encontrada', 404);
    }
    if (!isset($permisos[$entity][$method])) {
        fallar('Método no soportado', 405);
    }

    $regla   = $permisos[$entity][$method];
    $usuario = null;
    if ($regla !== '*') {
        $usuario = usuarioActual($pdo);
        if (!$usuario) {
            fallar('Inicia sesión para continuar', 401);
        }
        if (is_array($regla) && !in_array($usuario['rol'], $regla, true)) {
            fallar('No tienes permiso para esta acción', 403);
        }
    }

    require __DIR__ . '/handlers/' . $rutas[$entity] . '.php';
} catch (ErrorApi $e) {
    jsonOut(['error' => $e->getMessage()], $e->getCode() ?: 400);
} catch (Throwable $e) {
    error_log('Recetario: ' . $e->getMessage());
    jsonOut(['error' => 'Error interno del servidor'], 500);
}

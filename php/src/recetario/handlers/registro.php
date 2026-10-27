<?php
defined('RECETARIO') or exit;

$permitido = getenv('PERMITIR_REGISTRO') === '1';
sembrarUsuariosIniciales($pdo);

if ($method === 'GET') {
    jsonOut(['permitido' => $permitido]);
}
if (!$permitido) {
    fallar('El registro de locales está desactivado', 403);
}
if (!is_array($body)) {
    fallar('Cuerpo de la solicitud inválido', 400);
}

$nombreLocal = trim((string)($body['local_nombre'] ?? ''));
$nombre      = trim((string)($body['nombre'] ?? ''));
$nuevo       = strtolower(trim((string)($body['usuario'] ?? '')));
$clave       = (string)($body['clave'] ?? '');

if (mb_strlen($nombreLocal) < 3 || mb_strlen($nombreLocal) > 100) {
    fallar('El nombre del local debe tener entre 3 y 100 caracteres', 400);
}
if ($nombre === '' || mb_strlen($nombre) > 100) {
    fallar('Tu nombre es obligatorio (máximo 100 caracteres)', 400);
}
validarUsuario($nuevo);
validarClave($clave);

$base = slugify($nombreLocal);
if ($base === '') {
    fallar('El nombre del local debe incluir letras o números', 400);
}

[$uid] = transaccion($pdo, function () use ($pdo, $base, $nombreLocal, $nuevo, $nombre, $clave) {
    $slug = $base;
    $n    = 1;
    $st   = $pdo->prepare("SELECT 1 FROM locales WHERE slug = ?");
    while (true) {
        $st->execute([$slug]);
        if (!$st->fetchColumn()) {
            break;
        }
        $n++;
        $slug = $base . '-' . $n;
    }
    $pdo->prepare("INSERT INTO locales (nombre, slug) VALUES (?, ?)")->execute([$nombreLocal, $slug]);
    $lid = (int)$pdo->lastInsertId();
    $uid = crearUsuario($pdo, $lid, $nuevo, $nombre, 'dueno', $clave);
    return [$uid, $slug];
});

iniciarSesion();
session_regenerate_id(true);
$_SESSION['uid'] = $uid;
jsonOut(datosSesion(usuarioActual($pdo)));

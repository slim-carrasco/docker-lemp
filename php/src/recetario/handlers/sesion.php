<?php
defined('RECETARIO') or exit;

if ($entity === 'me') {
    jsonOut(datosSesion($usuario));
}

if ($entity === 'logout') {
    cerrarSesion();
    jsonOut(['ok' => true]);
}

if ($entity === 'clave') {
    if (!is_array($body)) {
        fallar('Cuerpo de la solicitud inválido', 400);
    }
    $st = $pdo->prepare("SELECT clave_hash FROM usuarios WHERE id = ?");
    $st->execute([$usuario['id']]);
    if (!password_verify((string)($body['actual'] ?? ''), (string)$st->fetchColumn())) {
        fallar('La contraseña actual no es correcta', 400);
    }
    $nueva = (string)($body['nueva'] ?? '');
    validarClave($nueva);
    $pdo->prepare("UPDATE usuarios SET clave_hash = ? WHERE id = ?")
        ->execute([password_hash($nueva, PASSWORD_DEFAULT), $usuario['id']]);
    session_regenerate_id(true);
    jsonOut(['ok' => true]);
}

// ---- login ----
if (!is_array($body)) {
    fallar('Cuerpo de la solicitud inválido', 400);
}
sembrarUsuariosIniciales($pdo);

$slug          = slugify((string)($body['local'] ?? ''));
$nombreUsuario = strtolower(trim((string)($body['usuario'] ?? '')));
$clave         = (string)($body['clave'] ?? '');
$generico      = 'Local, usuario o contraseña incorrectos';

$st = $pdo->prepare(
    "SELECT u.* FROM usuarios u JOIN locales l ON l.id = u.local_id
     WHERE l.slug = ? AND u.usuario = ?"
);
$st->execute([$slug, $nombreUsuario]);
$u = $st->fetch();
if (!$u) {
    fallar($generico, 401);
}

if ($u['bloqueado_hasta'] && strtotime($u['bloqueado_hasta']) > time()) {
    fallar('Demasiados intentos. Espera 5 minutos e inténtalo de nuevo', 429);
}

if (!password_verify($clave, $u['clave_hash'])) {
    $intentos = (int)$u['intentos'] + 1;
    $bloqueo  = null;
    if ($intentos >= 5) {
        $bloqueo  = date('Y-m-d H:i:s', time() + 300);
        $intentos = 0;
    }
    $pdo->prepare("UPDATE usuarios SET intentos = ?, bloqueado_hasta = ? WHERE id = ?")
        ->execute([$intentos, $bloqueo, $u['id']]);
    fallar($generico, 401);
}
if (!$u['activo']) {
    fallar('Tu cuenta está desactivada. Habla con el dueño del local', 403);
}

$pdo->prepare("UPDATE usuarios SET intentos = 0, bloqueado_hasta = NULL WHERE id = ?")->execute([$u['id']]);
iniciarSesion();
session_regenerate_id(true);
$_SESSION['uid'] = (int)$u['id'];

$actual = usuarioActual($pdo);
if (!$actual) {
    fallar('No se pudo iniciar la sesión', 500);
}
jsonOut(datosSesion($actual));

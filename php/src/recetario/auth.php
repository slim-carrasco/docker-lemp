<?php
defined('RECETARIO') or exit;

const ROLES = ['dueno', 'chef', 'ayudante'];

function slugify(string $s): string {
    $s = mb_strtolower(trim($s), 'UTF-8');
    $s = strtr($s, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
    ]);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function iniciarSesion(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.gc_maxlifetime', '28800');
    $seguro = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_name('recetario');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $seguro,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function cerrarSesion(): void {
    iniciarSesion();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $p['path'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'],
        ]);
    }
    session_destroy();
}

// Usuario de la sesión, leído de la base en cada petición:
// si lo desactivan o le cambian el rol, el cambio rige de inmediato.
function usuarioActual(PDO $pdo): ?array {
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    iniciarSesion();
    $uid = (int)($_SESSION['uid'] ?? 0);
    if ($uid === 0) {
        return $cache = null;
    }
    $st = $pdo->prepare(
        "SELECT u.id, u.local_id, u.usuario, u.nombre, u.rol, u.activo,
                l.nombre AS local_nombre, l.slug AS local_slug, l.mostrar_precios
         FROM usuarios u JOIN locales l ON l.id = u.local_id
         WHERE u.id = ?"
    );
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u || !$u['activo']) {
        cerrarSesion();
        return $cache = null;
    }
    return $cache = [
        'id'              => (int)$u['id'],
        'local_id'        => (int)$u['local_id'],
        'usuario'         => $u['usuario'],
        'nombre'          => $u['nombre'],
        'rol'             => $u['rol'],
        'local_nombre'    => $u['local_nombre'],
        'local_slug'      => $u['local_slug'],
        'mostrar_precios' => (bool)$u['mostrar_precios'],
    ];
}

function datosSesion(array $u): array {
    return [
        'id'      => $u['id'],
        'usuario' => $u['usuario'],
        'nombre'  => $u['nombre'],
        'rol'     => $u['rol'],
        'local'   => [
            'id'              => $u['local_id'],
            'nombre'          => $u['local_nombre'],
            'slug'            => $u['local_slug'],
            'mostrar_precios' => $u['mostrar_precios'],
        ],
    ];
}

function validarUsuario(string $u): void {
    if (!preg_match('/^[a-z0-9._-]{3,30}$/', $u)) {
        fallar('El usuario debe tener de 3 a 30 caracteres: minúsculas, números, punto, guion o guion bajo', 400);
    }
}

function validarClave(string $c): void {
    $n = strlen($c);
    if ($n < 8 || $n > 72) {
        fallar('La contraseña debe tener entre 8 y 72 caracteres', 400);
    }
}

function esDuplicado(PDOException $e): bool {
    return isset($e->errorInfo[1]) && (int)$e->errorInfo[1] === 1062;
}

function crearUsuario(PDO $pdo, int $localId, string $usuario, string $nombre, string $rol, string $clave): int {
    $pdo->prepare(
        "INSERT INTO usuarios (local_id, usuario, nombre, rol, clave_hash) VALUES (?, ?, ?, ?, ?)"
    )->execute([$localId, $usuario, $nombre, $rol, password_hash($clave, PASSWORD_DEFAULT)]);
    return (int)$pdo->lastInsertId();
}

// Primer arranque: si no hay usuarios, crea los del .env en el local 1
function sembrarUsuariosIniciales(PDO $pdo): void {
    $admin      = strtolower(trim((string)getenv('ADMIN_USER')));
    $claveAdmin = (string)getenv('ADMIN_PASSWORD');
    if ($admin === '' || $claveAdmin === '') {
        return;
    }
    if ((int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn() > 0) {
        return;
    }

    try {
        validarUsuario($admin);
        validarClave($claveAdmin);
        $nombreAdmin = trim((string)getenv('ADMIN_NOMBRE')) ?: 'Dueño del local';
        $candidatos  = [[$admin, $nombreAdmin, 'dueno', $claveAdmin]];
        foreach ([['CHEF', 'Chef', 'chef'], ['AYUDANTE', 'Ayudante de cocina', 'ayudante']] as [$pref, $nombre, $rol]) {
            $u = strtolower(trim((string)getenv($pref . '_USER')));
            $c = (string)getenv($pref . '_PASSWORD');
            if ($u === '' || $c === '') {
                continue;
            }
            validarUsuario($u);
            validarClave($c);
            $candidatos[] = [$u, $nombre, $rol, $c];
        }
    } catch (ErrorApi $e) {
        error_log('Recetario: usuarios iniciales no creados: ' . $e->getMessage());
        return;
    }

    $nombreLocal = trim((string)getenv('LOCAL_NOMBRE'));
    try {
        transaccion($pdo, function () use ($pdo, $candidatos, $nombreLocal) {
            if ($nombreLocal !== '') {
                $slug = slugify($nombreLocal) ?: 'local-de-ejemplo';
                $pdo->prepare("UPDATE locales SET nombre = ?, slug = ? WHERE id = 1")
                    ->execute([$nombreLocal, $slug]);
            }
            foreach ($candidatos as [$u, $n, $r, $c]) {
                crearUsuario($pdo, 1, $u, $n, $r, $c);
            }
        });
    } catch (PDOException $e) {
        error_log('Recetario: usuarios iniciales: ' . $e->getMessage());
    }
}

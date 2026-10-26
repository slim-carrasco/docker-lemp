<?php
defined('RECETARIO') or exit;

$localId = $usuario['local_id'];

switch ($method) {
    case 'GET':
        $st = $pdo->prepare(
            "SELECT id, usuario, nombre, rol, activo, creado FROM usuarios
             WHERE local_id = ? ORDER BY FIELD(rol, 'dueno', 'chef', 'ayudante'), usuario"
        );
        $st->execute([$localId]);
        $filas = $st->fetchAll();
        foreach ($filas as &$f) {
            $f['activo'] = (bool)$f['activo'];
        }
        unset($f);
        jsonOut($filas);

    case 'POST':
        if (!is_array($body)) {
            fallar('Cuerpo de la solicitud inválido', 400);
        }
        $nuevo  = strtolower(trim((string)($body['usuario'] ?? '')));
        $nombre = trim((string)($body['nombre'] ?? ''));
        $rol    = (string)($body['rol'] ?? '');
        $clave  = (string)($body['clave'] ?? '');
        validarUsuario($nuevo);
        if ($nombre === '' || mb_strlen($nombre) > 100) {
            fallar('El nombre es obligatorio (máximo 100 caracteres)', 400);
        }
        if (!in_array($rol, ROLES, true)) {
            fallar('Rol no válido', 400);
        }
        validarClave($clave);
        try {
            $nid = crearUsuario($pdo, $localId, $nuevo, $nombre, $rol, $clave);
        } catch (PDOException $e) {
            if (esDuplicado($e)) {
                fallar('Ese usuario ya existe en este local', 409);
            }
            throw $e;
        }
        jsonOut(['id' => $nid]);

    case 'PUT':
        if (!$id) {
            fallar('Falta el id', 400);
        }
        if (!is_array($body)) {
            fallar('Cuerpo de la solicitud inválido', 400);
        }
        $st = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND local_id = ?");
        $st->execute([$id, $localId]);
        $objetivo = $st->fetch();
        if (!$objetivo) {
            fallar('Usuario no encontrado', 404);
        }

        $nombre = array_key_exists('nombre', $body) ? trim((string)$body['nombre']) : $objetivo['nombre'];
        $rol    = array_key_exists('rol', $body) ? (string)$body['rol'] : $objetivo['rol'];
        $activo = array_key_exists('activo', $body) ? (!empty($body['activo']) ? 1 : 0) : (int)$objetivo['activo'];

        if ($nombre === '' || mb_strlen($nombre) > 100) {
            fallar('El nombre es obligatorio (máximo 100 caracteres)', 400);
        }
        if (!in_array($rol, ROLES, true)) {
            fallar('Rol no válido', 400);
        }

        // Siempre debe quedar al menos un dueño activo
        if ($objetivo['rol'] === 'dueno' && $objetivo['activo'] && ($rol !== 'dueno' || !$activo)) {
            $st = $pdo->prepare(
                "SELECT COUNT(*) FROM usuarios WHERE local_id = ? AND rol = 'dueno' AND activo = 1 AND id <> ?"
            );
            $st->execute([$localId, $id]);
            if ((int)$st->fetchColumn() === 0) {
                fallar('Debe quedar al menos un dueño activo', 400);
            }
        }

        $pdo->prepare("UPDATE usuarios SET nombre = ?, rol = ?, activo = ? WHERE id = ? AND local_id = ?")
            ->execute([$nombre, $rol, $activo, $id, $localId]);

        if (!empty($body['clave'])) {
            validarClave((string)$body['clave']);
            $pdo->prepare(
                "UPDATE usuarios SET clave_hash = ?, intentos = 0, bloqueado_hasta = NULL WHERE id = ? AND local_id = ?"
            )->execute([password_hash((string)$body['clave'], PASSWORD_DEFAULT), $id, $localId]);
        }
        jsonOut(['ok' => true]);
}

fallar('Método no soportado', 405);

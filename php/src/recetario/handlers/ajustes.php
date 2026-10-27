<?php
defined('RECETARIO') or exit;

if (!is_array($body) || !array_key_exists('mostrar_precios', $body)) {
    fallar('Falta mostrar_precios', 400);
}
$pdo->prepare("UPDATE locales SET mostrar_precios = ? WHERE id = ?")
    ->execute([!empty($body['mostrar_precios']) ? 1 : 0, $usuario['local_id']]);
jsonOut(['ok' => true]);

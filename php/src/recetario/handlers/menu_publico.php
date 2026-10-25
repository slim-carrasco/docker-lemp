<?php
defined('RECETARIO') or exit;

$slug = slugify(is_string($_GET['local'] ?? null) ? $_GET['local'] : '');
if ($slug === '') {
    fallar('Falta el local', 400);
}

$st = $pdo->prepare("SELECT id, nombre, mostrar_precios FROM locales WHERE slug = ?");
$st->execute([$slug]);
$loc = $st->fetch();
if (!$loc) {
    fallar('Local no encontrado', 404);
}

$hoy = date('Y-m-d');
// Solo nombre, descripción y, si el local lo permite, el precio de venta.
// Nunca ingredientes, costos ni notas.
$st = $pdo->prepare(
    "SELECT r.nombre, r.descripcion, r.precio_venta
     FROM menu_dia m JOIN recetas r ON r.id = m.receta_id
     WHERE m.fecha = ? AND r.local_id = ?
     ORDER BY r.nombre"
);
$st->execute([$hoy, $loc['id']]);

$mostrar = (bool)$loc['mostrar_precios'];
$platos  = [];
foreach ($st->fetchAll() as $f) {
    $platos[] = [
        'nombre'       => $f['nombre'],
        'descripcion'  => $f['descripcion'],
        'precio_venta' => ($mostrar && $f['precio_venta'] !== null) ? (float)$f['precio_venta'] : null,
    ];
}

jsonOut([
    'local'           => $loc['nombre'],
    'fecha'           => $hoy,
    'mostrar_precios' => $mostrar,
    'platos'          => $platos,
]);

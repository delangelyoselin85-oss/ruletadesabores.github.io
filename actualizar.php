<?php
session_start();
include("conexion.php");

if (!isset($categorias_validas)) {
    $categorias_validas = ['Pasteles', 'Cupcakes', 'Galletas', 'Especiales'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    die("CSRF inválido. Recarga el formulario.");
}

$id          = (int)($_POST['id'] ?? 0);
$nombre      = trim($_POST['nombre'] ?? '');
$categoria   = $_POST['categoria'] ?? '';
$precio      = (float)($_POST['precio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$existencia  = (int)($_POST['existencia'] ?? 0);
$imagen_actual = $_POST['imagen_actual'] ?? '';

if ($id <= 0 || $nombre === '' || !in_array($categoria, $categorias_validas, true)) {
    header("Location: index.php?mensaje=error"); exit;
}

$imagen_final   = $imagen_actual;
$borrar_despues = null;

if (!empty($_FILES['nueva_imagen']['name']) && $_FILES['nueva_imagen']['error'] === UPLOAD_ERR_OK) {
    $val = validarImagen($_FILES['nueva_imagen']);
    if (!$val['ok']) {
        header("Location: editar.php?id=$id&mensaje=" . urlencode($val['error'])); exit;
    }

    $nueva = guardarImagen($_FILES['nueva_imagen'], $val['extension']);
    if ($nueva === null) {
        header("Location: editar.php?id=$id&mensaje=" . urlencode("No se pudo guardar la nueva imagen.")); exit;
    }

    $imagen_final   = $nueva;
    $borrar_despues = $imagen_actual;
}

$stmt = $conexion->prepare(
    "UPDATE productos SET nombre=?, categoria=?, precio=?, descripcion=?, existencia=?, imagen=? WHERE id=?"
);
if (!$stmt) {
    die("Error en prepare: " . $conexion->error);
}
$stmt->bind_param("ssdsisi", $nombre, $categoria, $precio, $descripcion, $existencia, $imagen_final, $id);

if ($stmt->execute()) {
    $stmt->close();
    if ($borrar_despues && file_exists("img/" . $borrar_despues)) unlink("img/" . $borrar_despues);
    header("Location: index.php?mensaje=actualizado"); exit;
} else {
    error_log("Error UPDATE: " . $stmt->error);
    if ($borrar_despues && file_exists("img/" . $imagen_final)) unlink("img/" . $imagen_final);
    $stmt->close();
    header("Location: index.php?mensaje=error"); exit;
}
?>
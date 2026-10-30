<?php
session_start();
include("conexion.php");

// Verificaciones defensivas
if (!isset($categorias_validas)) {
    $categorias_validas = ['Pasteles', 'Cupcakes', 'Galletas', 'Especiales'];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

// CSRF
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)($_POST['csrf_token'] ?? ''))) {
    die("CSRF inválido. Recarga el formulario.");
}

$nombre      = trim($_POST['nombre'] ?? '');
$categoria   = $_POST['categoria'] ?? '';
$precio      = (float)($_POST['precio'] ?? 0);
$descripcion = trim($_POST['descripcion'] ?? '');
$existencia  = (int)($_POST['existencia'] ?? 0);

if ($nombre === '' || !in_array($categoria, $categorias_validas, true)) {
    header("Location: index.php?mensaje=error"); exit;
}

// Verificar que venga la imagen
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    header("Location: index.php?mensaje=" . urlencode("Debes subir una imagen válida.")); exit;
}

$val = validarImagen($_FILES['imagen']);
if (!$val['ok']) { header("Location: index.php?mensaje=" . urlencode($val['error'])); exit; }

$nombre_imagen = guardarImagen($_FILES['imagen'], $val['extension']);
if ($nombre_imagen === null) { header("Location: index.php?mensaje=" . urlencode("No se pudo guardar la imagen.")); exit; }

$stmt = $conexion->prepare(
    "INSERT INTO productos (nombre, categoria, precio, descripcion, existencia, imagen) VALUES (?, ?, ?, ?, ?, ?)"
);
if (!$stmt) {
    die("Error en prepare: " . $conexion->error);
}
$stmt->bind_param("ssdsis", $nombre, $categoria, $precio, $descripcion, $existencia, $nombre_imagen);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: index.php?mensaje=guardado"); exit;
} else {
    error_log("Error INSERT: " . $stmt->error);
    if (file_exists("img/" . $nombre_imagen)) unlink("img/" . $nombre_imagen);
    $stmt->close();
    header("Location: index.php?mensaje=error"); exit;
}
?>
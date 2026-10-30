<?php
session_start();
include("conexion.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

// CSRF
if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    die("CSRF inválido.");
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { header("Location: index.php?mensaje=error"); exit; }

// Obtener imagen
$stmt = $conexion->prepare("SELECT imagen FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res_img = $stmt->get_result();
$img = ($res_img->num_rows > 0) ? $res_img->fetch_assoc() : null;
$stmt->close();

// Eliminar
$stmt = $conexion->prepare("DELETE FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $stmt->close();
    if ($img && !empty($img['imagen']) && file_exists("img/" . $img['imagen'])) {
        unlink("img/" . $img['imagen']);
    }
    header("Location: index.php?mensaje=eliminado"); exit;
} else {
    error_log("Error DELETE: " . $stmt->error);
    $stmt->close();
    header("Location: index.php?mensaje=error"); exit;
}
?>
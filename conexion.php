<?php
$servidor   = "localhost";
$usuario    = "root";
$password   = "";
$base_datos = "pasteleria";

$conexion = new mysqli($servidor, $usuario, $password, $base_datos);
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}
$conexion->set_charset("utf8mb4");

// ⚠️ ESTA LÍNEA ES LA QUE FALTA EN TU CÓDIGO
$categorias_validas = ['Pasteles', 'Cupcakes', 'Galletas', 'Especiales'];

if (!file_exists("img")) { mkdir("img", 0755, true); }

function validarImagen(array $archivo): array {
    if (!isset($archivo['error']) || $archivo['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Error al subir el archivo.'];
    }
    if ($archivo['size'] > 5 * 1024 * 1024) {
        return ['ok' => false, 'error' => 'La imagen no debe superar 5 MB.'];
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $archivo['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return ['ok' => false, 'error' => 'Formato no permitido. Usa JPG, PNG o WEBP.'];
    }
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return ['ok' => false, 'error' => 'Extensión no permitida.'];
    }
    return ['ok' => true, 'error' => '', 'extension' => $ext];
}

function guardarImagen(array $archivo, string $ext): ?string {
    $nombre = bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    return move_uploaded_file($archivo['tmp_name'], "img/" . $nombre) ? $nombre : null;
}
?>
<?php
session_start();
include("conexion.php");

if (!isset($categorias_validas)) {
    $categorias_validas = ['Pasteles', 'Cupcakes', 'Galletas', 'Especiales'];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: index.php?mensaje=error"); exit;
}

$stmt = $conexion->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $stmt->close();
    header("Location: index.php?mensaje=" . urlencode("Producto no encontrado.")); exit;
}
$producto = $res->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Editar producto · Ruleta de Sabores</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --rosa-1:#ff5c8a; --rosa-2:#e91e63; --rosa-3:#ff8fab;
            --rosa-5:#ffd9e2; --rosa-6:#ffeef2; --rosa-tinta:#6b1f3a;
            --rosa-linea:#ffb8c9; --blanco:#fff; --blanco-2:#fffafb;
            --texto:#3d2630; --texto-suave:#8a6b77;
            --sombra-lg:0 24px 56px rgba(233,30,99,.16);
            --grad-rosa:linear-gradient(135deg,#ff5c8a 0%,#e91e63 100%);
            --grad-vino:linear-gradient(135deg,#6b1f3a 0%,#b8336a 100%);
        }
        * { -webkit-font-smoothing: antialiased; }
        body { background:var(--blanco-2); font-family:'Inter',sans-serif; color:var(--texto); line-height:1.55; }
        h1,h2,h3,h4,h5 { font-family:'Fraunces',serif; color:var(--rosa-tinta); font-weight:800; letter-spacing:-.025em; line-height:1.05; }
        .navbar { background:var(--blanco); box-shadow:0 1px 0 var(--rosa-linea); padding:18px 0; }
        .navbar-brand { display:flex; align-items:center; gap:14px; text-decoration:none; }
        .brand-mark { width:4px; height:44px; background:var(--grad-rosa); border-radius:2px; }
        .brand-title { font-family:'Fraunces',serif; font-weight:800; font-size:1.7rem; color:var(--rosa-tinta); line-height:1; letter-spacing:-.03em; }
        .brand-title em { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-weight:400; }
        .brand-sub { font-size:.68rem; letter-spacing:2.5px; color:var(--texto-suave); text-transform:uppercase; font-weight:600; margin-top:4px; }
        .edit-container { background:var(--blanco); border-radius:4px; box-shadow:var(--sombra-lg); padding:52px; margin-top:50px; border-top:4px solid var(--rosa-2); }
        .form-label { font-size:.72rem; font-weight:700; color:var(--rosa-tinta); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:10px; }
        .form-control, .form-select { border:1.5px solid var(--rosa-linea); border-radius:4px; padding:13px 16px; font-size:.92rem; background:var(--blanco); color:var(--texto); transition:all .2s ease; }
        .form-control:focus, .form-select:focus { border-color:var(--rosa-2); box-shadow:0 0 0 3px rgba(233,30,99,.1); }
        .input-group-text { background:var(--rosa-6); border:1.5px solid var(--rosa-linea); border-right:none; color:var(--rosa-2); border-radius:4px 0 0 4px; padding:0 18px; font-size:1.05rem; }
        .input-group .form-control, .input-group .form-select { border-left:none; border-radius:0 4px 4px 0; }
        .input-group:focus-within .input-group-text { border-color:var(--rosa-2); background:var(--rosa-5); }
        .btn-rosa { background:var(--grad-rosa); color:#fff; border-radius:4px; padding:15px 24px; font-weight:700; width:100%; border:none; font-size:.85rem; letter-spacing:2px; text-transform:uppercase; box-shadow:0 8px 20px rgba(233,30,99,.25); transition:all .25s ease; }
        .btn-rosa:hover { background:var(--grad-vino); color:#fff; transform:translateY(-2px); }
        .preview-img { max-width:180px; border-radius:4px; border:1.5px solid var(--rosa-linea); box-shadow:0 8px 20px rgba(233,30,99,.15); }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <div class="brand-mark"></div>
            <div>
                <div class="brand-title">Ruleta <em>de Sabores</em></div>
                <div class="brand-sub">Editar producto</div>
            </div>
        </a>
    </div>
</nav>

<div class="container">
    <div class="edit-container">
        <h1 class="mb-4">Editar <em style="font-family:'DM Serif Display',serif;color:var(--rosa-2);font-weight:400;">producto</em></h1>

        <form action="actualizar.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
            <input type="hidden" name="id" value="<?php echo (int)$producto['id']; ?>">
            <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($producto['imagen']); ?>">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nombre del postre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-cupcake"></i></span>
                        <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($producto['nombre']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Categoría</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-layers"></i></span>
                        <select name="categoria" class="form-select" required>
                            <?php foreach ($categorias_validas as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php if($producto['categoria'] === $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Precio ($)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                        <input type="number" step="0.01" name="precio" class="form-control" value="<?php echo htmlspecialchars($producto['precio']); ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Stock</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-box"></i></span>
                        <input type="number" name="existencia" class="form-control" value="<?php echo (int)$producto['existencia']; ?>" required>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                        <textarea name="descripcion" class="form-control" rows="2" required><?php echo htmlspecialchars($producto['descripcion']); ?></textarea>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">Nueva fotografía (JPG / PNG / WEBP · máx. 5MB)</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-camera"></i></span>
                        <input type="file" name="nueva_imagen" class="form-control" accept="image/*">
                    </div>
                    <small class="text-muted d-block mt-1" style="font-size:.75rem;">Deja vacío para mantener la foto actual.</small>
                </div>

                <?php if (!empty($producto['imagen']) && file_exists("img/" . $producto['imagen'])): ?>
                    <div class="col-12 mt-3">
                        <label class="form-label">Foto actual</label>
                        <br>
                        <img src="img/<?php echo htmlspecialchars($producto['imagen']); ?>" alt="Actual" class="preview-img">
                    </div>
                <?php endif; ?>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-rosa"><i class="bi bi-check-lg"></i> Actualizar producto</button>
                <a href="index.php" class="btn btn-outline-secondary w-100 mt-2" style="border-radius:4px; font-size:.85rem; letter-spacing:1px; text-transform:uppercase; font-weight:700; padding:13px;">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
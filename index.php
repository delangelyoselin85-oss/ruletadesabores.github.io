<?php
session_start();
include("conexion.php");

// Verificación por si conexion.php no tiene $categorias_validas
if (!isset($categorias_validas)) {
    $categorias_validas = ['Pasteles', 'Cupcakes', 'Galletas', 'Especiales'];
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$busqueda = trim($_GET['buscar'] ?? '');
$filtro_categoria = $_GET['filtro'] ?? '';

$sql    = "SELECT * FROM productos WHERE 1=1";
$params = [];
$types  = "";

if ($busqueda !== '') {
    $sql .= " AND nombre LIKE ?";
    $params[] = "%$busqueda%";
    $types .= "s";
}
if ($filtro_categoria !== '' && in_array($filtro_categoria, $categorias_validas, true)) {
    $sql .= " AND categoria = ?";
    $params[] = $filtro_categoria;
    $types .= "s";
}
$sql .= " ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
if ($params) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$resultado = $stmt->get_result();

$mensajes = [
    'guardado'    => 'Producto guardado correctamente.',
    'actualizado' => 'Producto actualizado correctamente.',
    'eliminado'   => 'Producto eliminado correctamente.',
    'error'       => 'Ocurrió un error. Intenta de nuevo.',
];
$mensaje_ok = $mensaje_err = null;
if (isset($_GET['mensaje'])) {
    $m = (string) $_GET['mensaje'];   // ⚠️ cast a string
    if (isset($mensajes[$m])) {
        if ($m === 'error') { $mensaje_err = $mensajes[$m]; }
        else                { $mensaje_ok  = $mensajes[$m]; }
    } else {
        $mensaje_err = htmlspecialchars($m);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ruleta de Sabores · Gestión de Inventario</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root {
            --rosa-1:#ff5c8a; --rosa-2:#e91e63; --rosa-3:#ff8fab;
            --rosa-4:#ffc2d1; --rosa-5:#ffd9e2; --rosa-6:#ffeef2;
            --rosa-tinta:#6b1f3a; --rosa-linea:#ffb8c9;
            --blanco:#fff; --blanco-2:#fffafb;
            --texto:#3d2630; --texto-suave:#8a6b77;
            --sombra-sm:0 2px 8px rgba(233,30,99,.08);
            --sombra-md:0 12px 32px rgba(233,30,99,.12);
            --sombra-lg:0 24px 56px rgba(233,30,99,.16);
            --grad-rosa:linear-gradient(135deg,#ff5c8a 0%,#e91e63 100%);
            --grad-vino:linear-gradient(135deg,#6b1f3a 0%,#b8336a 100%);
        }
        * { -webkit-font-smoothing: antialiased; }
        body { background:var(--blanco-2); font-family:'Inter',sans-serif; color:var(--texto); line-height:1.55; letter-spacing:-.005em; }
        h1,h2,h3,h4,h5 { font-family:'Fraunces',serif; color:var(--rosa-tinta); font-weight:800; letter-spacing:-.025em; line-height:1.05; }

        .navbar { background:var(--blanco); box-shadow:0 1px 0 var(--rosa-linea); padding:18px 0; }
        .navbar-brand { display:flex; align-items:center; gap:14px; text-decoration:none; }
        .brand-mark { width:4px; height:44px; background:var(--grad-rosa); border-radius:2px; }
        .brand-title { font-family:'Fraunces',serif; font-weight:800; font-size:1.7rem; color:var(--rosa-tinta); line-height:1; letter-spacing:-.03em; }
        .brand-title em { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-weight:400; }
        .brand-sub { font-size:.68rem; letter-spacing:2.5px; color:var(--texto-suave); text-transform:uppercase; font-weight:600; margin-top:4px; }
        .nav-link { color:var(--texto); font-weight:600; font-size:.85rem; padding:.55rem 1.1rem !important; transition:color .2s ease; border-bottom:2px solid transparent; border-radius:0; }
        .nav-link i { margin-right:6px; color:var(--rosa-1); }
        .nav-link:hover { color:var(--rosa-2); }
        .nav-link.active { color:var(--rosa-2); border-bottom:2px solid var(--rosa-2); }

        .hero { background-image:url('img/fondo.png'); background-size:cover; background-position:center; background-repeat:no-repeat; position:relative; padding:130px 0 170px; overflow:hidden; isolation:isolate; }
        .hero::before { content:""; position:absolute; inset:0; background:linear-gradient(to right, rgba(255,255,255,.55) 0%, rgba(255,255,255,.25) 45%, rgba(255,255,255,0) 75%); z-index:0; pointer-events:none; }
        .hero::after { content:""; position:absolute; top:40px; right:-80px; width:320px; height:320px; border:2px solid var(--rosa-3); border-radius:50%; opacity:.25; pointer-events:none; z-index:1; }
        .hero-content { position:relative; z-index:2; max-width:780px; }
        .hero-label { display:inline-block; font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-size:1.15rem; margin-bottom:12px; letter-spacing:-.01em; }
        .hero h1 { font-size:clamp(2.8rem, 6.5vw, 5.4rem); color:var(--rosa-tinta); margin:0 0 20px; line-height:.95; }
        .hero h1 em { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-weight:400; }
        .hero-tagline { font-size:1.05rem; color:var(--texto-suave); max-width:520px; margin:0 0 28px; line-height:1.6; }
        .hero-tags { display:flex; flex-wrap:wrap; gap:0; border-top:1px solid var(--rosa-linea); padding-top:22px; max-width:560px; }
        .hero-tags span { display:inline-flex; align-items:center; gap:8px; font-size:.82rem; font-weight:600; color:var(--rosa-tinta); padding:0 22px; border-right:1px solid var(--rosa-linea); letter-spacing:.02em; }
        .hero-tags span:first-child { padding-left:0; }
        .hero-tags span:last-child { border-right:none; }
        .hero-tags i { color:var(--rosa-1); font-size:.7rem; }

        .main-container { background:var(--blanco); border-radius:4px; box-shadow:var(--sombra-lg); padding:52px; margin-top:-90px; position:relative; z-index:10; border-top:4px solid var(--rosa-2); }

        .custom-tabs { border:none; gap:0; margin-bottom:34px; display:inline-flex; border-bottom:2px solid var(--rosa-linea); padding:0; background:transparent; }
        .custom-tabs .nav-link { border:none; border-radius:0; color:var(--texto-suave); font-weight:700; padding:14px 0; margin-right:32px; font-size:.9rem; text-transform:uppercase; letter-spacing:1.5px; background:transparent; border-bottom:2px solid transparent; margin-bottom:-2px; transition:all .25s ease; }
        .custom-tabs .nav-link i { margin-right:8px; color:var(--rosa-1); }
        .custom-tabs .nav-link:hover { color:var(--rosa-2); }
        .custom-tabs .nav-link.active { color:var(--rosa-2); border-bottom-color:var(--rosa-2); background:transparent; box-shadow:none; }
        .custom-tabs .nav-link.active i { color:var(--rosa-2); }

        .form-label { font-size:.72rem; font-weight:700; color:var(--rosa-tinta); text-transform:uppercase; letter-spacing:1.5px; margin-bottom:10px; }
        .form-control, .form-select { border:1.5px solid var(--rosa-linea); border-radius:4px; padding:13px 16px; font-size:.92rem; background:var(--blanco); color:var(--texto); transition:all .2s ease; }
        .form-control::placeholder { color:#c9a8b4; }
        .form-control:focus, .form-select:focus { border-color:var(--rosa-2); box-shadow:0 0 0 3px rgba(233,30,99,.1); background:var(--blanco); }
        .input-group-text { background:var(--rosa-6); border:1.5px solid var(--rosa-linea); border-right:none; color:var(--rosa-2); border-radius:4px 0 0 4px; padding:0 18px; font-size:1.05rem; }
        .input-group .form-control, .input-group .form-select { border-left:none; border-radius:0 4px 4px 0; }
        .input-group:focus-within .input-group-text { border-color:var(--rosa-2); color:var(--rosa-tinta); background:var(--rosa-5); }

        .btn-rosa { background:var(--grad-rosa); color:#fff; border-radius:4px; padding:15px 24px; font-weight:700; width:100%; border:none; font-size:.85rem; letter-spacing:2px; text-transform:uppercase; box-shadow:0 8px 20px rgba(233,30,99,.25); transition:all .25s ease; }
        .btn-rosa i { margin-right:8px; }
        .btn-rosa:hover { background:var(--grad-vino); color:#fff; transform:translateY(-2px); box-shadow:0 14px 28px rgba(107,31,58,.3); }

        .side-card { background:var(--rosa-6); border-radius:4px; padding:40px 32px; text-align:left; height:100%; display:flex; flex-direction:column; justify-content:center; border-left:4px solid var(--rosa-2); position:relative; }
        .side-card-label { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-size:1rem; margin-bottom:14px; letter-spacing:-.01em; }
        .side-card h3 { font-family:'Fraunces',serif; font-size:1.7rem; color:var(--rosa-tinta); line-height:1.15; margin-bottom:24px; font-weight:800; letter-spacing:-.02em; }
        .side-card h3 em { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-weight:400; }
        .side-card img { width:100%; border-radius:4px; object-fit:cover; box-shadow:0 16px 36px rgba(233,30,99,.18); }

        .catalog-header { display:flex; justify-content:space-between; align-items:flex-end; margin:70px 0 36px; flex-wrap:wrap; gap:18px; padding-bottom:22px; border-bottom:2px solid var(--rosa-linea); }
        .catalog-title-wrap h2 { font-size:clamp(2.2rem, 4.5vw, 3.2rem); margin:0; display:flex; align-items:baseline; gap:14px; flex-wrap:wrap; }
        .catalog-title-wrap h2 em { font-family:'DM Serif Display',serif; font-style:italic; color:var(--rosa-2); font-weight:400; font-size:.8em; }
        .catalog-title-wrap p { color:var(--texto-suave); margin:8px 0 0; font-size:.9rem; }

        .search-filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
        .search-filters .form-control, .search-filters .form-select { border-radius:4px; padding:10px 16px; font-size:.85rem; border:1.5px solid var(--rosa-linea); background:#fff; }
        .search-filters .form-control:focus, .search-filters .form-select:focus { border-color:var(--rosa-2); box-shadow:0 0 0 3px rgba(233,30,99,.1); }
        .search-filters .btn-rosa { width:auto; padding:10px 20px; border-radius:4px; letter-spacing:1px; font-size:.78rem; }

        .card-producto { border:1.5px solid var(--rosa-linea); border-radius:4px; overflow:hidden; background:#fff; box-shadow:var(--sombra-sm); transition:all .3s cubic-bezier(.2,.8,.2,1); height:100%; display:flex; flex-direction:column; position:relative; }
        .card-producto:hover { transform:translateY(-6px); box-shadow:var(--sombra-md); border-color:var(--rosa-1); }
        .img-container { height:210px; overflow:hidden; position:relative; background:var(--rosa-6); }
        .img-container img { width:100%; height:100%; object-fit:cover; transition:transform .7s cubic-bezier(.2,.8,.2,1); }
        .card-producto:hover .img-container img { transform:scale(1.08); }
        .badge-categoria { position:absolute; top:0; left:0; background:var(--rosa-2); color:#fff; padding:7px 14px; font-size:.68rem; font-weight:700; letter-spacing:1.5px; text-transform:uppercase; border-radius:0; }
        .card-body { padding:24px; display:flex; flex-direction:column; flex-grow:1; text-align:left; }
        .card-title { font-family:'Fraunces',serif; font-size:1.3rem; margin-bottom:8px; color:var(--rosa-tinta); font-weight:800; line-height:1.15; letter-spacing:-.02em; }
        .card-text { font-size:.82rem; color:var(--texto-suave); margin-bottom:18px; flex-grow:1; line-height:1.55; display:-webkit-box; -webkit-box-orient:vertical; -webkit-line-clamp:2; line-clamp:2; overflow:hidden; }
        .precio { font-family:'DM Serif Display',serif; font-size:1.7rem; font-weight:400; color:var(--rosa-2); letter-spacing:-.02em; line-height:1; }
        .stock-badge { font-size:.72rem; color:var(--texto-suave); display:flex; align-items:center; gap:6px; font-weight:600; text-transform:uppercase; letter-spacing:.5px; }
        .stock-dot { width:6px; height:6px; border-radius:50%; background:#2eb872; display:inline-block; box-shadow:0 0 0 3px rgba(46,184,114,.2); }
        .stock-dot.agotado { background:#e63946; box-shadow:0 0 0 3px rgba(230,57,70,.2); }
        .btn-card-edit { background:var(--rosa-6); color:var(--rosa-2); border:1.5px solid var(--rosa-linea); border-radius:4px; font-size:.78rem; font-weight:700; padding:10px; width:50%; transition:all .2s ease; text-transform:uppercase; letter-spacing:1px; }
        .btn-card-edit:hover { background:var(--grad-rosa); color:#fff; border-color:transparent; transform:translateY(-1px); }
        .btn-card-delete { background:#fff; color:var(--texto-suave); border:1.5px solid #e8d5db; border-radius:4px; font-size:.78rem; font-weight:700; padding:10px; width:50%; transition:all .2s ease; text-transform:uppercase; letter-spacing:1px; }
        .btn-card-delete:hover { background:#fee2e2; color:#c81e3f; border-color:#fca5a5; transform:translateY(-1px); }

        .alert { border-radius:4px; border:none; font-size:.88rem; font-weight:600; padding:16px 20px; box-shadow:var(--sombra-sm); border-left:4px solid; }
        .alert-success { background:#e8f9ee; color:#1e7e48; border-left-color:#2eb872; }
        .alert-danger { background:#fdecee; color:#a01e35; border-left-color:#e63946; }

        footer { background:var(--blanco); padding:60px 0 50px; text-align:center; border-top:2px solid var(--rosa-linea); margin-top:100px; }
        footer .script { font-family:'DM Serif Display',serif; font-style:italic; font-size:2.2rem; color:var(--rosa-tinta); margin:0 0 10px; letter-spacing:-.02em; }
        footer .script em { color:var(--rosa-2); font-weight:400; }
        footer .script-sub { font-size:.72rem; letter-spacing:3px; text-transform:uppercase; color:var(--texto-suave); font-weight:700; margin:0; }
        footer .line-deco { width:60px; height:2px; background:var(--grad-rosa); margin:0 auto 20px; }

        @media (max-width:768px) {
            .main-container { padding:30px 22px; margin-top:-50px; }
            .hero { padding:80px 0 110px; }
            .hero-tags span { padding:0 12px; font-size:.76rem; }
            .catalog-header { margin:50px 0 26px; }
            .search-filters { width:100%; }
            .search-filters .form-control, .search-filters .form-select { flex:1; min-width:120px; }
            .custom-tabs .nav-link { margin-right:18px; font-size:.8rem; }
        }
    </style>
</head>
<body>

<nav class="navbar sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <div class="brand-mark"></div>
            <div>
                <div class="brand-title">Ruleta <em>de Sabores</em></div>
                <div class="brand-sub">Pasteles · Cupcakes · Galletas · Más</div>
            </div>
        </a>
        <div class="d-none d-md-flex gap-1 align-items-center">
            <a href="index.php" class="nav-link active"><i class="bi bi-house-door-fill"></i> Inicio</a>
            <a href="#catalogo" class="nav-link"><i class="bi bi-box-seam"></i> Inventario</a>
        </div>
    </div>
</nav>

<div class="hero">
    <div class="container">
        <div class="hero-content">
            <span class="hero-label">Panel de administración</span>
            <h1>Gestión de <em>inventario</em></h1>
            <p class="hero-tagline">Administra cada producto de tu pastelería con la precisión de un artesano y la dulzura de siempre.</p>
            <div class="hero-tags">
                <span><i class="bi bi-circle-fill"></i> Pasteles</span>
                <span><i class="bi bi-circle-fill"></i> Cupcakes</span>
                <span><i class="bi bi-circle-fill"></i> Galletas</span>
                <span><i class="bi bi-circle-fill"></i> Especiales</span>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <?php if ($mensaje_ok): ?>
        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert" aria-live="polite">
            <i class="bi bi-check-circle-fill me-1"></i> <?php echo $mensaje_ok; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($mensaje_err): ?>
        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" aria-live="polite">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo $mensaje_err; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="main-container">
        <div class="row g-4">
            <div class="col-lg-8">
                <ul class="nav custom-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="bi bi-plus-lg"></i> Agregar
                        </a>
                    </li>
                </ul>

                <form action="guardar.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombre del postre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-cupcake"></i></span>
                                <input type="text" name="nombre" class="form-control" placeholder="Ej. Pastel de Fresa" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Categoría</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-layers"></i></span>
                                <select name="categoria" class="form-select" required>
                                    <?php foreach ($categorias_validas as $cat): ?>
                                        <option value="<?php echo $cat; ?>"><?php echo $cat; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Precio ($)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-currency-dollar"></i></span>
                                <input type="number" step="0.01" name="precio" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Stock</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-box"></i></span>
                                <input type="number" name="existencia" class="form-control" placeholder="Cantidad" required>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-pencil"></i></span>
                                <textarea name="descripcion" class="form-control" rows="2" placeholder="Describe tu producto..." required></textarea>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Fotografía (JPG / PNG / WEBP · máx. 5MB)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-camera"></i></span>
                                <input type="file" name="imagen" class="form-control" accept="image/*" required>
                            </div>
                            <small class="text-muted d-block mt-1" style="font-size:.75rem;">*Requerido</small>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-rosa"><i class="bi bi-plus-lg"></i> Guardar producto en catálogo</button>
                    </div>
                </form>
            </div>

            <div class="col-lg-4 d-none d-lg-block">
                <div class="side-card">
                    <span class="side-card-label">— Hecho con amor</span>
                    <h3>Los mejores momentos saben mejor <em>con un postre</em></h3>
                    <img src="img/cupcake.png" alt="Cupcake decorado">
                </div>
            </div>
        </div>
    </div>

    <div class="catalog-header" id="catalogo">
        <div class="catalog-title-wrap">
            <h2>Nuestro <em>catálogo</em></h2>
            <p>Descubre nuestras delicias horneadas</p>
        </div>
        <form action="index.php#catalogo" method="GET" class="search-filters">
            <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($busqueda); ?>" style="min-width:200px;">
            <select name="filtro" class="form-select form-select-sm" style="min-width:150px;">
                <option value="">Todas</option>
                <?php foreach ($categorias_validas as $cat): ?>
                    <option value="<?php echo $cat; ?>" <?php if($filtro_categoria === $cat) echo 'selected'; ?>><?php echo $cat; ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-rosa btn-sm"><i class="bi bi-search"></i> Buscar</button>
        </form>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5">
        <?php if ($resultado->num_rows > 0): ?>
            <?php while ($producto = $resultado->fetch_assoc()):
                $ruta_img = "img/" . $producto['imagen'];
                $mostrar_img = (!empty($producto['imagen']) && file_exists($ruta_img))
                    ? $ruta_img
                    : "https://images.unsplash.com/photo-1578985545062-69928b1d9587?q=80&w=600&auto=format&fit=crop";
                $agotado = ((int)$producto['existencia'] <= 0);
            ?>
                <div class="col">
                    <div class="card card-producto">
                        <div class="img-container">
                            <img src="<?php echo $mostrar_img; ?>" alt="<?php echo htmlspecialchars($producto['nombre']); ?>">
                            <span class="badge-categoria"><?php echo htmlspecialchars($producto['categoria']); ?></span>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title"><?php echo htmlspecialchars($producto['nombre']); ?></h4>
                            <p class="card-text"><?php echo htmlspecialchars($producto['descripcion']); ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto mb-3">
                                <span class="precio">$<?php echo number_format($producto['precio'], 2); ?></span>
                                <span class="stock-badge">
                                    <span class="stock-dot <?php echo $agotado ? 'agotado' : ''; ?>"></span>
                                    <?php echo (int)$producto['existencia']; ?>
                                </span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="editar.php?id=<?php echo (int)$producto['id']; ?>" class="btn btn-card-edit">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                                <form method="POST" action="eliminar.php" class="m-0" style="width:50%;"
                                      onsubmit="return confirm('¿Estás seguro de eliminar este producto?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
                                    <input type="hidden" name="id" value="<?php echo (int)$producto['id']; ?>">
                                    <button type="submit" class="btn btn-card-delete w-100">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-inbox display-1 text-muted opacity-50"></i>
                <h4 class="mt-3">No se encontraron productos</h4>
                <p>Prueba con otra búsqueda o agrega uno nuevo.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<footer>
    <div class="container">
        <div class="line-deco"></div>
        <p class="script">Ruleta <em>de Sabores</em></p>
        <p class="script-sub">Gestión de inventario · Control total de tu pastelería</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
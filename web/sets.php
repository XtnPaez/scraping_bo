<?php
define('DB_PATH', 'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

$mensaje = null; $tipo = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $alias       = trim($_POST['alias'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($alias === '') {
        $mensaje = 'El alias es obligatorio.'; $tipo = 'danger';
    } elseif (strlen($alias) > 20) {
        $mensaje = 'El alias no puede superar los 20 caracteres.'; $tipo = 'danger';
    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Seleccioná un archivo CSV.'; $tipo = 'danger';
    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $mensaje = 'El archivo debe tener extensión .csv.'; $tipo = 'danger';
    } else {
        $con  = get_con();
        $stmt = $con->prepare("SELECT id FROM sets WHERE alias = ?");
        $stmt->execute([$alias]);
        if ($stmt->fetch()) {
            $mensaje = 'Ya existe un set con el alias "' . htmlspecialchars($alias) . '". Elegí otro nombre.';
            $tipo    = 'danger';
        } else {
            $lineas = file($_FILES['csv_file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lineas = array_map(function($l) {
                return iconv('Windows-1252', 'UTF-8//IGNORE', $l);
            }, $lineas);
            $palabras = array_values(array_unique(array_filter(array_map('trim', $lineas), fn($l) => $l !== '')));

            if (empty($palabras)) {
                $mensaje = 'El CSV no contiene entradas válidas.'; $tipo = 'danger';
            } else {
                try {
                    $con->beginTransaction();
                    $stmt = $con->prepare("INSERT INTO sets (alias, descripcion, fecha_creacion) VALUES (?, ?, ?)");
                    $stmt->execute([$alias, $descripcion, date('Y-m-d')]);
                    $set_id = $con->lastInsertId();
                    $stmt2  = $con->prepare("INSERT INTO palabras (set_id, palabra) VALUES (?, ?)");
                    foreach ($palabras as $p) $stmt2->execute([$set_id, $p]);
                    $con->commit();
                    $mensaje = 'Set "' . htmlspecialchars($alias) . '" creado con ' . count($palabras) . ' entrada(s).';
                    $tipo    = 'success';
                } catch (Exception $e) {
                    $con->rollBack();
                    $mensaje = 'Error al guardar: ' . $e->getMessage(); $tipo = 'danger';
                }
            }
        }
    }
}

$con  = get_con();
$sets = $con->query("SELECT * FROM sets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
foreach ($sets as &$set) {
    $stmt = $con->prepare("SELECT palabra FROM palabras WHERE set_id = ? ORDER BY id");
    $stmt->execute([$set['id']]);
    $set['palabras'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($set);
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Nuevo set de palabras</h5>
        <hr>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="alias" class="form-label">Alias <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="alias" name="alias"
                       maxlength="20" placeholder="Máximo 20 caracteres, único"
                       value="<?= htmlspecialchars($_POST['alias'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control" id="descripcion" name="descripcion"
                       maxlength="255" placeholder="Descripción del criterio de armado del set"
                       value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label for="csv_file" class="form-label">Archivo CSV <span class="text-danger">*</span></label>
                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv">
                <div class="form-text">Una palabra o frase por línea, sin encabezado. Las frases se buscan de forma exacta.</div>
            </div>
            <button type="submit" class="btn btn-primary">Guardar set</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Sets existentes</h5>
        <hr>

        <?php if (empty($sets)): ?>
            <p class="text-muted">No hay sets creados todavía.</p>
        <?php else: ?>
            <?php foreach ($sets as $set): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($set['alias']) ?></strong>
                        <span class="text-muted small"><?= count($set['palabras']) ?> entrada(s) · ID <?= $set['id'] ?> · <?= $set['fecha_creacion'] ?></span>
                    </div>
                    <?php if ($set['descripcion']): ?>
                        <div class="card-body py-2 border-bottom">
                            <small class="text-muted"><?= htmlspecialchars($set['descripcion']) ?></small>
                        </div>
                    <?php endif; ?>
                    <div class="card-body py-2">
                        <small><?= implode(', ', array_map('htmlspecialchars', $set['palabras'])) ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

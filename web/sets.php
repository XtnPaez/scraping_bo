<?php
// sets.php — Tab 4: alta y consulta de sets de palabras

define('DB_PATH', 'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

$mensaje = null;
$tipo    = null;

// ─── POST: guardar nuevo set ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $alias       = trim($_POST['alias'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if ($alias === '') {
        $mensaje = 'El alias es obligatorio.';
        $tipo    = 'error';

    } elseif (strlen($alias) > 20) {
        $mensaje = 'El alias no puede superar los 20 caracteres.';
        $tipo    = 'error';

    } elseif (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Seleccioná un archivo CSV.';
        $tipo    = 'error';

    } elseif (strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)) !== 'csv') {
        $mensaje = 'El archivo debe tener extensión .csv.';
        $tipo    = 'error';

    } else {
        // Verificar alias único
        $con  = get_con();
        $stmt = $con->prepare("SELECT id FROM sets WHERE alias = ?");
        $stmt->execute([$alias]);

        if ($stmt->fetch()) {
            $mensaje = 'Ya existe un set con el alias "' . htmlspecialchars($alias) . '". Elegí otro nombre.';
            $tipo    = 'error';

        } else {
            // Leer CSV — forzar conversion Windows-1252 a UTF-8 (origen: Excel)
            $lineas = file($_FILES['csv_file']['tmp_name'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $lineas = array_map(function($l) {
                return iconv('Windows-1252', 'UTF-8//IGNORE', $l);
            }, $lineas);
            $palabras = array_values(array_unique(array_filter(
                array_map('trim', $lineas),
                fn($l) => $l !== ''
            )));

            if (empty($palabras)) {
                $mensaje = 'El CSV no contiene entradas válidas.';
                $tipo    = 'error';

            } else {
                try {
                    $con->beginTransaction();

                    $stmt = $con->prepare(
                        "INSERT INTO sets (alias, descripcion, fecha_creacion) VALUES (?, ?, ?)"
                    );
                    $stmt->execute([$alias, $descripcion, date('Y-m-d')]);
                    $set_id = $con->lastInsertId();

                    $stmt2 = $con->prepare("INSERT INTO palabras (set_id, palabra) VALUES (?, ?)");
                    foreach ($palabras as $p) {
                        $stmt2->execute([$set_id, $p]);
                    }

                    $con->commit();
                    $mensaje = 'Set "' . htmlspecialchars($alias) . '" creado con ' . count($palabras) . ' entrada(s).';
                    $tipo    = 'success';

                } catch (Exception $e) {
                    $con->rollBack();
                    $mensaje = 'Error al guardar: ' . $e->getMessage();
                    $tipo    = 'error';
                }
            }
        }
    }
}

// ─── Leer todos los sets ──────────────────────────────────────────────────────
$con  = get_con();
$sets = $con->query("SELECT * FROM sets ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);

foreach ($sets as &$set) {
    $stmt = $con->prepare("SELECT palabra FROM palabras WHERE set_id = ? ORDER BY id");
    $stmt->execute([$set['id']]);
    $set['palabras'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
unset($set);
?>

<!-- ALTA DE SET -->
<div class="card mb-4">
    <div class="card-body">
        <div class="card-title-bo">🗂️ Nuevo set de palabras</div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo === 'success' ? 'success' : ($tipo === 'warning' ? 'warning' : 'danger') ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="alias">Alias <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="alias" name="alias"
                       maxlength="20"
                       placeholder="ej: Salud, Vivienda, Licitaciones"
                       value="<?= htmlspecialchars($_POST['alias'] ?? '') ?>">
                <small class="form-text text-muted">Máximo 20 caracteres. Debe ser único.</small>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripción <span class="text-muted">(opcional)</span></label>
                <input type="text" class="form-control" id="descripcion" name="descripcion"
                       maxlength="255"
                       placeholder="ej: Palabras relacionadas con programas de salud pública"
                       value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>">
            </div>

            <div class="form-group">
                <label for="csv_file">Archivo CSV <span class="text-danger">*</span></label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="csv_file" name="csv_file" accept=".csv">
                    <label class="custom-file-label" for="csv_file">Seleccioná un archivo .csv</label>
                </div>
                <small class="form-text text-muted">
                    Una palabra o frase por línea, sin encabezado. Las frases se buscan de forma exacta (equivalente a buscar entre comillas).
                </small>
            </div>

            <button type="submit" class="btn btn-primary">💾 Guardar set</button>

        </form>
    </div>
</div>

<!-- CONSULTA DE SETS -->
<div class="card">
    <div class="card-body">
        <div class="card-title-bo">📋 Sets existentes</div>

        <?php if (empty($sets)): ?>
            <div class="alert alert-info">No hay sets creados todavía.</div>
        <?php else: ?>
            <?php foreach ($sets as $set): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex align-items-center justify-content-between"
                         style="background:#343a40; color:#fff;">
                        <div>
                            <span class="mono font-weight-bold"><?= htmlspecialchars($set['alias']) ?></span>
                            <span class="badge badge-warning ml-2"><?= count($set['palabras']) ?> entrada(s)</span>
                        </div>
                        <small class="mono" style="opacity:0.5;">
                            ID <?= $set['id'] ?> · <?= $set['fecha_creacion'] ?>
                        </small>
                    </div>

                    <?php if ($set['descripcion']): ?>
                        <div class="card-body py-2 px-3 bg-light border-bottom">
                            <small class="text-muted"><?= htmlspecialchars($set['descripcion']) ?></small>
                        </div>
                    <?php endif; ?>

                    <div class="card-body py-2 px-3">
                        <?php foreach ($set['palabras'] as $p): ?>
                            <span class="badge badge-secondary mr-1 mb-1"
                                  style="font-size:0.78rem; font-weight:400;">
                                <?= htmlspecialchars($p) ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Mostrar nombre del archivo seleccionado
document.getElementById('csv_file').addEventListener('change', function() {
    var label = this.nextElementSibling;
    label.textContent = this.files.length ? this.files[0].name : 'Seleccioná un archivo .csv';
});
</script>

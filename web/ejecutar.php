<?php
define('DB_PATH',    'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');
define('ARCHIVO_DIR','C:\\xampp\\htdocs\\scraping_bo\\archivo');
define('PYTHON_EXE', 'C:\\xampp\\htdocs\\scraping_bo\\scripts\\portable_python\\WPy64-31241\\python-3.12.4.amd64\\python.exe');
define('APP_PY',     'C:\\xampp\\htdocs\\scraping_bo\\scripts\\app.py');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

function ultimo_pdf() {
    $base = ARCHIVO_DIR;
    if (!is_dir($base)) return null;
    $ultimo = null; $ultimo_t = 0;
    foreach (glob($base . '\\*', GLOB_ONLYDIR) as $anio_dir)
        foreach (glob($anio_dir . '\\*', GLOB_ONLYDIR) as $mes_dir)
            foreach (glob($mes_dir . '\\*.pdf') as $pdf) {
                $t = filemtime($pdf);
                if ($t > $ultimo_t) { $ultimo = $pdf; $ultimo_t = $t; }
            }
    return $ultimo;
}

function fecha_desde_ruta($ruta) {
    if (preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $ruta, $m))
        return $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
    return null;
}

$con      = get_con();
$sets     = $con->query("SELECT id, alias, descripcion FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);
$pdf_path = ultimo_pdf();
$fecha_pdf = $pdf_path ? fecha_desde_ruta($pdf_path) : null;
$mensaje = null; $tipo = null; $ejecucion_id = null; $resultado = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_id = (int)($_POST['set_id'] ?? 0);
    if (!$pdf_path) {
        $mensaje = 'No hay ningún PDF archivado. Subí uno primero.';
        $tipo    = 'danger';
    } elseif ($set_id === 0) {
        $mensaje = 'Seleccioná un set de palabras.';
        $tipo    = 'danger';
    } else {
        $cmd    = '"' . PYTHON_EXE . '" "' . APP_PY . '" "' . $pdf_path . '" ' . $set_id . ' 2>&1';
        $output = shell_exec($cmd);
        if (strpos($output, 'DUPLICADO') !== false) {
            $mensaje = 'Esta combinación de PDF + set ya fue procesada. Consultá los resultados en Históricos.';
            $tipo    = 'warning';
        } elseif (strpos($output, 'ERROR') !== false) {
            $mensaje = 'Error en el procesamiento.';
            $tipo    = 'danger';
        } else {
            preg_match('/ejecucion_id=(\d+)/', $output, $m);
            if (!empty($m[1])) { $ejecucion_id = (int)$m[1]; }
            else { $mensaje = 'El script terminó sin confirmar el resultado.'; $tipo = 'warning'; }
        }
    }
}

if ($ejecucion_id) {
    $stmt = $con->prepare("SELECT * FROM ejecuciones WHERE id = ?");
    $stmt->execute([$ejecucion_id]);
    $ejecucion = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt2 = $con->prepare("SELECT palabra, parrafo FROM resultados WHERE ejecucion_id = ? ORDER BY palabra");
    $stmt2->execute([$ejecucion_id]);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $fila)
        $resultado[$fila['palabra']][] = $fila['parrafo'];
}
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">PDF disponible</h5>
        <hr>
        <?php if ($pdf_path): ?>
            <p class="mb-1 small"><code><?= htmlspecialchars($pdf_path) ?></code></p>
            <p class="mb-0 text-muted small">Fecha del boletín: <strong><?= htmlspecialchars($fecha_pdf ?? '—') ?></strong> · Modificado: <?= date('d/m/Y H:i', filemtime($pdf_path)) ?></p>
        <?php else: ?>
            <div class="alert alert-warning mb-0">No hay ningún PDF archivado. <a href="index.php?tab=subir">Subir PDF</a>.</div>
        <?php endif; ?>
    </div>
</div>

<?php if ($pdf_path): ?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Ejecutar búsqueda</h5>
        <hr>

        <?php if ($mensaje && !$ejecucion_id): ?>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" id="form-ejecutar">
            <div class="mb-3">
                <label for="set_id" class="form-label">Set de palabras</label>
                <?php if (empty($sets)): ?>
                    <div class="alert alert-warning">No hay sets creados. <a href="index.php?tab=sets">Crear set</a>.</div>
                <?php else: ?>
                    <select class="form-select" id="set_id" name="set_id">
                        <option value="">Elegí un set</option>
                        <?php foreach ($sets as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($_POST['set_id']) && $_POST['set_id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['alias']) ?><?= $s['descripcion'] ? ' — ' . htmlspecialchars($s['descripcion']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>
            <?php if (!empty($sets)): ?>
                <button type="submit" class="btn btn-primary">Ejecutar</button>
                <div class="loader" id="loader-ejecutar">
                    <div class="spinner"></div>
                    <span>Procesando PDF, esperá...</span>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>
<?php endif; ?>

<?php if ($ejecucion_id): ?>
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Resultados</h5>
        <hr>
        <?php if (isset($ejecucion)): ?>
            <p class="text-muted small mb-3">
                Boletín: <?= htmlspecialchars($ejecucion['fecha_boletin']) ?> ·
                <?= $ejecucion['cant_palabras'] ?> palabras buscadas ·
                <?= $ejecucion['cant_parrafos'] ?> párrafos encontrados ·
                Ejecución #<?= $ejecucion['id'] ?> · <?= $ejecucion['fecha_ejecucion'] ?>
            </p>
        <?php endif; ?>

        <?php if (empty($resultado)): ?>
            <div class="alert alert-info">No se encontraron párrafos para las palabras del set.</div>
        <?php else: ?>
            <?php foreach ($resultado as $palabra => $parrafos): ?>
                <div class="resultado-bloque">
                    <div class="palabra-header">
                        <?= htmlspecialchars($palabra) ?>
                        <span class="badge bg-secondary ms-auto"><?= count($parrafos) ?> párrafo(s)</span>
                    </div>
                    <?php foreach ($parrafos as $parrafo): ?>
                        <div class="parrafo-item"><?= htmlspecialchars($parrafo) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

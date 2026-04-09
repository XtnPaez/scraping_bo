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

function listar_pdfs() {
    $base = ARCHIVO_DIR; $pdfs = [];
    if (!is_dir($base)) return $pdfs;
    foreach (glob($base . '\\*', GLOB_ONLYDIR) as $anio_dir)
        foreach (glob($anio_dir . '\\*', GLOB_ONLYDIR) as $mes_dir)
            foreach (glob($mes_dir . '\\*.pdf') as $pdf)
                if (preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $pdf, $m)) {
                    $fecha = $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
                    $pdfs[] = ['ruta' => $pdf, 'fecha' => $fecha, 'label' => $m[3] . '/' . $m[2] . '/' . $m[1]];
                }
    usort($pdfs, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    return $pdfs;
}

$con  = get_con();
$sets = $con->query("SELECT id, alias, descripcion FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);
$pdfs = listar_pdfs();
$mensaje = null; $tipo = null; $ejecucion_id = null; $resultado = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdf_path = $_POST['pdf_path'] ?? '';
    $set_id   = (int)($_POST['set_id'] ?? 0);
    if ($pdf_path === '' || !file_exists($pdf_path)) {
        $mensaje = 'Seleccioná un PDF válido.'; $tipo = 'danger';
    } elseif ($set_id === 0) {
        $mensaje = 'Seleccioná un set de palabras.'; $tipo = 'danger';
    } else {
        preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $pdf_path, $m);
        $fecha_boletin = $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
        $cmd    = '"' . PYTHON_EXE . '" "' . APP_PY . '" "' . $pdf_path . '" ' . $set_id . ' "' . $fecha_boletin . '" 2>&1';
        $output = shell_exec($cmd);
        if (strpos($output, 'DUPLICADO') !== false) {
            $mensaje = 'Esta combinación de PDF + set ya fue procesada. Consultá los resultados en Históricos.'; $tipo = 'warning';
        } elseif (strpos($output, 'ERROR') !== false) {
            $mensaje = 'Error en el procesamiento.'; $tipo = 'danger';
        } else {
            preg_match('/ejecucion_id=(\d+)/', $output, $match);
            if (!empty($match[1])) { $ejecucion_id = (int)$match[1]; }
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
        <h5 class="card-title">Re-ejecutar PDF archivado</h5>
        <hr>

        <?php if (empty($pdfs)): ?>
            <p class="text-muted">No hay PDFs archivados. <a href="index.php?tab=subir">Subir PDF</a>.</p>
        <?php elseif (empty($sets)): ?>
            <p class="text-muted">No hay sets creados. <a href="index.php?tab=sets">Crear set</a>.</p>
        <?php else: ?>
            <?php if ($mensaje && !$ejecucion_id): ?>
                <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
            <?php endif; ?>
            <form method="POST" id="form-ejecutar">
                <div class="mb-3">
                    <label for="pdf_path" class="form-label">PDF archivado</label>
                    <select class="form-select" id="pdf_path" name="pdf_path">
                        <option value="">Elegí un boletín</option>
                        <?php foreach ($pdfs as $p): ?>
                            <option value="<?= htmlspecialchars($p['ruta']) ?>" <?= (isset($_POST['pdf_path']) && $_POST['pdf_path'] === $p['ruta']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['label']) ?> — <?= htmlspecialchars($p['ruta']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="set_id" class="form-label">Set de palabras</label>
                    <select class="form-select" id="set_id" name="set_id">
                        <option value="">Elegí un set</option>
                        <?php foreach ($sets as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (isset($_POST['set_id']) && $_POST['set_id'] == $s['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['alias']) ?><?= $s['descripcion'] ? ' — ' . htmlspecialchars($s['descripcion']) : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Ejecutar</button>
                <div class="loader" id="loader-ejecutar">
                    <div class="spinner"></div>
                    <span>Procesando PDF, esperá...</span>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($pdfs) && !empty($sets)): ?>
<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Combinaciones ya procesadas</h5>
        <hr>
        <p class="text-muted small">Estas combinaciones están bloqueadas. Para ver sus resultados usá <a href="index.php?tab=historicos">Históricos</a>.</p>
        <?php
        $stmt    = $con->query("SELECT e.id, e.fecha_boletin, e.fecha_ejecucion, e.tiene_resultados, e.cant_parrafos, s.alias as set_alias FROM ejecuciones e JOIN sets s ON s.id = e.set_id ORDER BY e.id DESC LIMIT 50");
        $previas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <?php if (empty($previas)): ?>
            <p class="text-muted">Todavía no hay ejecuciones registradas.</p>
        <?php else: ?>
            <table class="table table-hover table-sm">
                <thead class="table-dark">
                    <tr><th>#</th><th>Fecha boletín</th><th>Set</th><th>Párrafos</th><th>Resultado</th><th>Ejecutado</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($previas as $p): ?>
                    <tr>
                        <td><?= $p['id'] ?></td>
                        <td><?= htmlspecialchars($p['fecha_boletin']) ?></td>
                        <td><?= htmlspecialchars($p['set_alias']) ?></td>
                        <td><?= $p['cant_parrafos'] ?></td>
                        <td><?= $p['tiene_resultados'] ?></td>
                        <td class="text-muted small"><?= $p['fecha_ejecucion'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
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
                <?= $ejecucion['cant_palabras'] ?> palabras ·
                <?= $ejecucion['cant_parrafos'] ?> párrafos ·
                Ejecución #<?= $ejecucion['id'] ?>
            </p>
        <?php endif; ?>
        <?php if (empty($resultado)): ?>
            <p class="text-muted">No se encontraron párrafos para las palabras del set.</p>
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

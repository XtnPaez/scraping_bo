<?php
define('BASE_DIR',   'C:\\xampp\\htdocs\\scraping_bo');
define('ARCHIVO_DIR', BASE_DIR . '\\archivo');
define('PYTHON_EXE', BASE_DIR . '\\scripts\\portable_python\\WPy64-31241\\python-3.12.4.amd64\\python.exe');
define('APP_PY',     BASE_DIR . '\\scripts\\app.py');

$mensaje  = null;
$tipo     = null;
$pdf_info = null;

$status    = $_GET['status'] ?? '';
$fecha_get = $_GET['fecha']  ?? '';

if ($status === 'ok') {
    list($anio, $mes, $dia_str) = explode('-', $fecha_get);
    $dia      = (int)$dia_str;
    $mensaje  = 'PDF archivado correctamente: ' . $anio . '/' . $mes . '/' . $dia . '.pdf';
    $tipo     = 'success';
    $pdf_info = ['fecha' => $fecha_get, 'ruta' => ARCHIVO_DIR . '\\' . $anio . '\\' . $mes . '\\' . $dia . '.pdf'];
} elseif ($status === 'existe') {
    list($anio, $mes, $dia_str) = explode('-', $fecha_get);
    $dia      = (int)$dia_str;
    $mensaje  = 'Ya existe un PDF archivado para el ' . $dia . '/' . $mes . '/' . $anio . '. No se sobreescribió.';
    $tipo     = 'warning';
    $pdf_info = ['fecha' => $fecha_get, 'ruta' => ARCHIVO_DIR . '\\' . $anio . '\\' . $mes . '\\' . $dia . '.pdf'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {
    $file = $_FILES['pdf_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al recibir el archivo.';
        $tipo    = 'error';
    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        $mensaje = 'El archivo debe ser un PDF.';
        $tipo    = 'error';
    } else {
        $tmp = $file['tmp_name'];
        $cmd = '"' . PYTHON_EXE . '" "' . APP_PY . '" --solo-fecha "' . $tmp . '" 2>&1';
        $output = shell_exec($cmd);
        preg_match('/FECHA:(\d{4}-\d{2}-\d{2})/', $output, $matches);
        if (!empty($matches[1])) {
            $fecha = $matches[1];
            list($anio, $mes, $dia_str) = explode('-', $fecha);
            $dia      = (int)$dia_str;
            $dest_dir = ARCHIVO_DIR . '\\' . $anio . '\\' . $mes;
            $dest     = $dest_dir . '\\' . $dia . '.pdf';
            if (!is_dir($dest_dir)) mkdir($dest_dir, 0777, true);
            if (file_exists($dest)) {
                header('Location: index.php?tab=subir&status=existe&fecha=' . urlencode($fecha));
                exit;
            } else {
                move_uploaded_file($tmp, $dest);
                header('Location: index.php?tab=subir&status=ok&fecha=' . urlencode($fecha));
                exit;
            }
        } else {
            $mensaje = 'No se pudo extraer la fecha del PDF. Verificá que sea un Boletín Oficial válido.';
            $tipo    = 'error';
        }
    }
}

function ultimo_pdf_archivado() {
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

$ultimo_pdf = ultimo_pdf_archivado();
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Subir PDF del Boletín Oficial</h5>
        <hr>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo === 'success' ? 'success' : ($tipo === 'warning' ? 'warning' : 'danger') ?>">
                <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="pdf_file" class="form-label">Archivo PDF</label>
                <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
            </div>
            <button type="submit" class="btn btn-primary">Subir y archivar</button>
        </form>

        <?php if ($pdf_info): ?>
            <div class="mt-3">
                <p class="mb-1"><strong>Fecha detectada:</strong> <?= htmlspecialchars($pdf_info['fecha']) ?></p>
                <p class="mb-0 text-muted small"><code><?= htmlspecialchars($pdf_info['ruta']) ?></code></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($ultimo_pdf): ?>
<div class="card">
    <div class="card-body">
        <h6 class="card-title">Último PDF archivado</h6>
        <p class="mb-1 small"><code><?= htmlspecialchars($ultimo_pdf) ?></code></p>
        <p class="mb-0 text-muted small">Modificado: <?= date('d/m/Y H:i', filemtime($ultimo_pdf)) ?></p>
    </div>
</div>
<?php endif; ?>

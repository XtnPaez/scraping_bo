<?php
// subir.php — Tab 1: subir y archivar PDF

define('BASE_DIR', 'C:\\xampp\\htdocs\\scraping_bo');
define('ARCHIVO_DIR', BASE_DIR . '\\archivo');
define('PYTHON_EXE', BASE_DIR . '\\scripts\\portable_python\\WPy64-31241\\python-3.12.4.amd64\\python.exe');
define('APP_PY',     BASE_DIR . '\\scripts\\app.py');

$mensaje  = null;
$tipo     = null;
$pdf_info = null;

// Mensajes desde redirect
$status = $_GET['status'] ?? '';
$fecha_get = $_GET['fecha'] ?? '';
if ($status === 'ok') {
    list($anio, $mes, $dia_str) = explode('-', $fecha_get);
    $dia = (int)$dia_str;
    $mensaje  = 'PDF archivado correctamente: ' . $anio . '/' . $mes . '/' . $dia . '.pdf';
    $tipo     = 'success';
    $pdf_info = ['fecha' => $fecha_get, 'ruta' => ARCHIVO_DIR . '\\' . $anio . '\\' . $mes . '\\' . $dia . '.pdf', 'nuevo' => true];
} elseif ($status === 'existe') {
    list($anio, $mes, $dia_str) = explode('-', $fecha_get);
    $dia = (int)$dia_str;
    $mensaje  = 'Ya existe un PDF archivado para el ' . $dia . '/' . $mes . '/' . $anio . '. No se sobreescribió.';
    $tipo     = 'warning';
    $pdf_info = ['fecha' => $fecha_get, 'ruta' => ARCHIVO_DIR . '\\' . $anio . '\\' . $mes . '\\' . $dia . '.pdf', 'nuevo' => false];
}

// ─── POST: recibir PDF ────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdf_file'])) {

    $file = $_FILES['pdf_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $mensaje = 'Error al recibir el archivo.';
        $tipo    = 'error';

    } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        $mensaje = 'El archivo debe ser un PDF.';
        $tipo    = 'error';

    } else {
        // Guardar temporal para extraer fecha
        $tmp = $file['tmp_name'];

        // Llamar a Python solo para extraer la fecha
        $cmd    = '"' . PYTHON_EXE . '" "' . APP_PY . '" --solo-fecha "' . $tmp . '" 2>&1';
        $output = shell_exec($cmd);

        // Buscar fecha en el output: formato YYYY-MM-DD
        preg_match('/FECHA:(\d{4}-\d{2}-\d{2})/', $output, $matches);

        if (!empty($matches[1])) {
            $fecha = $matches[1]; // ej: 2026-04-09
            list($anio, $mes, $dia_str) = explode('-', $fecha);
            $dia      = (int)$dia_str; // sin cero
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

// ─── Último PDF archivado ─────────────────────────────────────────────────────
function ultimo_pdf_archivado() {
    $base = ARCHIVO_DIR;
    if (!is_dir($base)) return null;

    $ultimo = null;
    foreach (glob($base . '\\*', GLOB_ONLYDIR) as $anio_dir) {
        foreach (glob($anio_dir . '\\*', GLOB_ONLYDIR) as $mes_dir) {
            foreach (glob($mes_dir . '\\*.pdf') as $pdf) {
                if (!$ultimo || filemtime($pdf) > filemtime($ultimo)) {
                    $ultimo = $pdf;
                }
            }
        }
    }
    return $ultimo;
}

$ultimo_pdf = ultimo_pdf_archivado();
?>

<div class="card">
    <div class="card-title">📄 Subir PDF del Boletín Oficial</div>

    <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="form-subir">
        <div class="upload-zone" id="upload-zone">
            <span class="upload-icon">📥</span>
            <p>Arrastrá el PDF acá o <strong>hacé clic para seleccionarlo</strong></p>
            <input type="file" id="pdf_file" name="pdf_file" accept=".pdf" style="display:none">
        </div>

        <div class="mt-2">
            <button type="submit" class="btn-primary">
                ⬆️ Subir y archivar
            </button>
        </div>
    </form>

    <?php if ($pdf_info): ?>
        <div class="mt-3">
            <div class="alert alert-info">
                <div>
                    <strong>Fecha detectada:</strong>
                    <?= htmlspecialchars($pdf_info['fecha']) ?> &nbsp;|&nbsp;
                    <strong>Ruta:</strong>
                    <span class="mono"><?= htmlspecialchars($pdf_info['ruta']) ?></span>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($ultimo_pdf): ?>
<div class="card">
    <div class="card-title">📁 Último PDF archivado</div>
    <div class="flex items-center gap-2 justify-between">
        <span class="mono text-small"><?= htmlspecialchars($ultimo_pdf) ?></span>
        <span class="badge badge-verde">disponible</span>
    </div>
    <div class="mt-1 text-small text-muted">
        Modificado: <?= date('d/m/Y H:i', filemtime($ultimo_pdf)) ?>
    </div>
</div>
<?php endif; ?>

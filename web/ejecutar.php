<?php
// ejecutar.php — Tab 2: ejecutar búsqueda sobre el último PDF subido

define('DB_PATH',    'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');
define('ARCHIVO_DIR','C:\\xampp\\htdocs\\scraping_bo\\archivo');
define('PYTHON_EXE', 'C:\\xampp\\htdocs\\scraping_bo\\scripts\\portable_python\\WPy64-31241\\python-3.12.4.amd64\\python.exe');
define('APP_PY',     'C:\\xampp\\htdocs\\scraping_bo\\scripts\\app.py');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

// ─── Último PDF archivado ─────────────────────────────────────────────────────
function ultimo_pdf() {
    $base = ARCHIVO_DIR;
    if (!is_dir($base)) return null;
    $ultimo = null;
    $ultimo_t = 0;
    foreach (glob($base . '\\*', GLOB_ONLYDIR) as $anio_dir) {
        foreach (glob($anio_dir . '\\*', GLOB_ONLYDIR) as $mes_dir) {
            foreach (glob($mes_dir . '\\*.pdf') as $pdf) {
                $t = filemtime($pdf);
                if ($t > $ultimo_t) { $ultimo = $pdf; $ultimo_t = $t; }
            }
        }
    }
    return $ultimo;
}

// ─── Fecha desde ruta ─────────────────────────────────────────────────────────
function fecha_desde_ruta($ruta) {
    // ruta: .../archivo/2026/04/9.pdf
    if (preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $ruta, $m)) {
        return $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
    }
    return null;
}

$con  = get_con();
$sets = $con->query("SELECT id, alias, descripcion FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);

$pdf_path     = ultimo_pdf();
$fecha_pdf    = $pdf_path ? fecha_desde_ruta($pdf_path) : null;
$resultado    = null;
$ejecucion_id = null;
$mensaje      = null;
$tipo         = null;

// ─── POST: ejecutar ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $set_id = (int)($_POST['set_id'] ?? 0);

    if (!$pdf_path) {
        $mensaje = 'No hay ningún PDF archivado. Subí uno primero desde el Tab 1.';
        $tipo    = 'error';

    } elseif ($set_id === 0) {
        $mensaje = 'Seleccioná un set de palabras.';
        $tipo    = 'error';

    } else {
        $cmd    = '"' . PYTHON_EXE . '" "' . APP_PY . '" "' . $pdf_path . '" ' . $set_id . ' 2>&1';
        $output = shell_exec($cmd);

        // Detectar duplicado
        if (strpos($output, 'DUPLICADO') !== false) {
            $mensaje = 'Esta combinación de PDF + set ya fue procesada anteriormente.';
            $tipo    = 'warning';

        // Detectar error
        } elseif (strpos($output, 'ERROR') !== false) {
            $mensaje = 'Error en el procesamiento. Revisá la consola del servidor.';
            $tipo    = 'error';

        } else {
            // Extraer ejecucion_id del output
            preg_match('/ejecucion_id=(\d+)/', $output, $m);
            if (!empty($m[1])) {
                $ejecucion_id = (int)$m[1];
                $tipo         = 'success';
            } else {
                $mensaje = 'El script terminó pero no se pudo confirmar el resultado.';
                $tipo    = 'warning';
            }
        }
    }
}

// ─── Cargar resultados si hay ejecucion_id ────────────────────────────────────
if ($ejecucion_id) {
    $stmt = $con->prepare("SELECT * FROM ejecuciones WHERE id = ?");
    $stmt->execute([$ejecucion_id]);
    $ejecucion = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $con->prepare(
        "SELECT palabra, parrafo FROM resultados WHERE ejecucion_id = ? ORDER BY palabra"
    );
    $stmt2->execute([$ejecucion_id]);
    $filas = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    // Agrupar por palabra
    $resultado = [];
    foreach ($filas as $fila) {
        $resultado[$fila['palabra']][] = $fila['parrafo'];
    }
}
?>

<!-- INFO PDF ACTUAL -->
<div class="card">
    <div class="card-title">📄 PDF disponible para ejecutar</div>

    <?php if ($pdf_path): ?>
        <div class="flex items-center gap-2 justify-between">
            <span class="mono text-small"><?= htmlspecialchars($pdf_path) ?></span>
            <span class="badge badge-verde">listo</span>
        </div>
        <div class="mt-1 text-small text-muted">
            Fecha del boletín: <strong><?= htmlspecialchars($fecha_pdf ?? '—') ?></strong>
            &nbsp;·&nbsp; Modificado: <?= date('d/m/Y H:i', filemtime($pdf_path)) ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            No hay ningún PDF archivado. Subí uno desde <a href="index.php?tab=subir">Tab 1 — Subir PDF</a>.
        </div>
    <?php endif; ?>
</div>

<!-- FORMULARIO EJECUCION -->
<?php if ($pdf_path): ?>
<div class="card">
    <div class="card-title">▶️ Ejecutar búsqueda</div>

    <?php if ($mensaje && !$ejecucion_id): ?>
        <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form method="POST" id="form-ejecutar">
        <div class="form-group">
            <label for="set_id">Set de palabras</label>
            <?php if (empty($sets)): ?>
                <div class="alert alert-warning">
                    No hay sets creados. Creá uno en <a href="index.php?tab=sets">Tab 4 — Sets de palabras</a>.
                </div>
            <?php else: ?>
                <select id="set_id" name="set_id">
                    <option value="">— Elegí un set —</option>
                    <?php foreach ($sets as $s): ?>
                        <option value="<?= $s['id'] ?>"
                            <?= (isset($_POST['set_id']) && $_POST['set_id'] == $s['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['alias']) ?>
                            <?= $s['descripcion'] ? ' — ' . htmlspecialchars($s['descripcion']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </div>

        <?php if (!empty($sets)): ?>
            <button type="submit" class="btn-acento">▶️ Ejecutar</button>
            <div class="loader" id="loader-ejecutar">
                <div class="spinner"></div>
                Procesando PDF, esperá...
            </div>
        <?php endif; ?>
    </form>
</div>
<?php endif; ?>

<!-- RESULTADOS -->
<?php if ($ejecucion_id): ?>
<div class="card">
    <div class="card-title">
        📊 Resultados
        <?php if (isset($ejecucion)): ?>
            <span class="badge badge-<?= $ejecucion['tiene_resultados'] === 'Si' ? 'verde' : 'gris' ?>">
                <?= $ejecucion['cant_parrafos'] ?> párrafo(s)
            </span>
        <?php endif; ?>
    </div>

    <?php if (isset($ejecucion)): ?>
        <div class="flex gap-2 mb-2" style="flex-wrap:wrap;">
            <span class="badge badge-azul">Boletín: <?= htmlspecialchars($ejecucion['fecha_boletin']) ?></span>
            <span class="badge badge-azul"><?= $ejecucion['cant_palabras'] ?> palabras buscadas</span>
            <span class="badge badge-azul">Ejecución #<?= $ejecucion['id'] ?></span>
            <span class="badge badge-azul"><?= $ejecucion['fecha_ejecucion'] ?></span>
        </div>
    <?php endif; ?>

    <?php if (empty($resultado)): ?>
        <div class="alert alert-info">
            No se encontraron párrafos para las palabras del set seleccionado.
        </div>
    <?php else: ?>
        <?php foreach ($resultado as $palabra => $parrafos): ?>
            <div class="resultado-bloque">
                <div class="palabra-header">
                    🔍 <?= htmlspecialchars($palabra) ?>
                    <span class="badge" style="background:var(--acento);color:#fff;margin-left:auto;">
                        <?= count($parrafos) ?> párrafo(s)
                    </span>
                </div>
                <?php foreach ($parrafos as $parrafo): ?>
                    <div class="parrafo-item">
                        <?= htmlspecialchars($parrafo) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

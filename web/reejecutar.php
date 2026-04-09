<?php
// reejecutar.php — Tab 5: ejecutar cualquier PDF archivado con cualquier set

define('DB_PATH',    'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');
define('ARCHIVO_DIR','C:\\xampp\\htdocs\\scraping_bo\\archivo');
define('PYTHON_EXE', 'C:\\xampp\\htdocs\\scraping_bo\\scripts\\portable_python\\WPy64-31241\\python-3.12.4.amd64\\python.exe');
define('APP_PY',     'C:\\xampp\\htdocs\\scraping_bo\\scripts\\app.py');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

// ─── PDFs archivados ──────────────────────────────────────────────────────────
function listar_pdfs() {
    $base = ARCHIVO_DIR;
    $pdfs = [];
    if (!is_dir($base)) return $pdfs;
    foreach (glob($base . '\\*', GLOB_ONLYDIR) as $anio_dir) {
        foreach (glob($anio_dir . '\\*', GLOB_ONLYDIR) as $mes_dir) {
            foreach (glob($mes_dir . '\\*.pdf') as $pdf) {
                // Extraer fecha desde la ruta
                if (preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $pdf, $m)) {
                    $fecha = $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);
                    $pdfs[] = [
                        'ruta'  => $pdf,
                        'fecha' => $fecha,
                        'label' => $m[3] . '/' . $m[2] . '/' . $m[1]
                    ];
                }
            }
        }
    }
    // Ordenar por fecha descendente
    usort($pdfs, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));
    return $pdfs;
}

$con  = get_con();
$sets = $con->query("SELECT id, alias, descripcion FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);
$pdfs = listar_pdfs();

$mensaje      = null;
$tipo         = null;
$ejecucion_id = null;
$resultado    = [];

// ─── POST: ejecutar ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdf_path = $_POST['pdf_path'] ?? '';
    $set_id   = (int)($_POST['set_id'] ?? 0);

    if ($pdf_path === '' || !file_exists($pdf_path)) {
        $mensaje = 'Seleccioná un PDF válido.';
        $tipo    = 'error';

    } elseif ($set_id === 0) {
        $mensaje = 'Seleccioná un set de palabras.';
        $tipo    = 'error';

    } else {
        // Extraer fecha desde la ruta del PDF archivado
        preg_match('/(\d{4})[\\/\\\\](\d{2})[\\/\\\\](\d+)\.pdf$/', $pdf_path, $m);
        $fecha_boletin = $m[1] . '-' . $m[2] . '-' . str_pad($m[3], 2, '0', STR_PAD_LEFT);

        $cmd    = '"' . PYTHON_EXE . '" "' . APP_PY . '" "' . $pdf_path . '" ' . $set_id . ' "' . $fecha_boletin . '" 2>&1';
        $output = shell_exec($cmd);

        if (strpos($output, 'DUPLICADO') !== false) {
            $mensaje = 'Esta combinación de PDF + set ya fue procesada anteriormente. Revisá el Tab 3 — Históricos.';
            $tipo    = 'warning';

        } elseif (strpos($output, 'ERROR') !== false) {
            $mensaje = 'Error en el procesamiento. Revisá la consola del servidor.';
            $tipo    = 'error';

        } else {
            preg_match('/ejecucion_id=(\d+)/', $output, $match);
            if (!empty($match[1])) {
                $ejecucion_id = (int)$match[1];
                $tipo         = 'success';
            } else {
                $mensaje = 'El script terminó pero no se pudo confirmar el resultado.';
                $tipo    = 'warning';
            }
        }
    }
}

// ─── Cargar resultados ────────────────────────────────────────────────────────
if ($ejecucion_id) {
    $stmt = $con->prepare("SELECT * FROM ejecuciones WHERE id = ?");
    $stmt->execute([$ejecucion_id]);
    $ejecucion = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt2 = $con->prepare(
        "SELECT palabra, parrafo FROM resultados WHERE ejecucion_id = ? ORDER BY palabra"
    );
    $stmt2->execute([$ejecucion_id]);
    foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $resultado[$fila['palabra']][] = $fila['parrafo'];
    }
}
?>

<!-- SELECTOR -->
<div class="card">
    <div class="card-title">🔁 Re-ejecutar PDF archivado</div>

    <?php if (empty($pdfs)): ?>
        <div class="alert alert-warning">
            No hay PDFs archivados. Subí uno desde <a href="index.php?tab=subir">Tab 1 — Subir PDF</a>.
        </div>
    <?php elseif (empty($sets)): ?>
        <div class="alert alert-warning">
            No hay sets creados. Creá uno en <a href="index.php?tab=sets">Tab 4 — Sets de palabras</a>.
        </div>
    <?php else: ?>

        <?php if ($mensaje && !$ejecucion_id): ?>
            <div class="alert alert-<?= $tipo ?>"><?= htmlspecialchars($mensaje) ?></div>
        <?php endif; ?>

        <form method="POST" id="form-ejecutar">

            <div class="mb-3">
                <label for="pdf_path">PDF archivado</label>
                <select id="pdf_path" name="pdf_path">
                    <option value="">— Elegí un boletín —</option>
                    <?php foreach ($pdfs as $p): ?>
                        <option value="<?= htmlspecialchars($p['ruta']) ?>"
                            <?= (isset($_POST['pdf_path']) && $_POST['pdf_path'] === $p['ruta']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['label']) ?>
                            &nbsp;·&nbsp;
                            <span class="mono"><?= htmlspecialchars($p['ruta']) ?></span>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="set_id">Set de palabras</label>
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
            </div>

            <button type="submit" class="btn-acento">🔁 Ejecutar</button>
            <div class="loader" id="loader-ejecutar">
                <div class="spinner"></div>
                Procesando PDF, esperá...
            </div>

        </form>
    <?php endif; ?>
</div>

<!-- EJECUCIONES PREVIAS DE CADA COMBINACION -->
<?php if (!empty($pdfs) && !empty($sets)): ?>
<div class="card">
    <div class="card-title">📋 Combinaciones ya procesadas</div>
    <div class="text-small text-muted mb-2">
        Estas combinaciones están bloqueadas. Para ver sus resultados usá el
        <a href="index.php?tab=historicos">Tab 3 — Históricos</a>.
    </div>

    <?php
    $stmt = $con->query("
        SELECT e.id, e.fecha_boletin, e.fecha_ejecucion, e.tiene_resultados,
               e.cant_parrafos, s.alias as set_alias
        FROM ejecuciones e
        JOIN sets s ON s.id = e.set_id
        ORDER BY e.id DESC
        LIMIT 50
    ");
    $previas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ?>

    <?php if (empty($previas)): ?>
        <div class="alert alert-info">Todavía no hay ejecuciones registradas.</div>
    <?php else: ?>
        <table class="tabla-resultados">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha boletín</th>
                    <th>Set</th>
                    <th>Párrafos</th>
                    <th>Resultado</th>
                    <th>Ejecutado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($previas as $p): ?>
                <tr>
                    <td class="mono"><?= $p['id'] ?></td>
                    <td><?= htmlspecialchars($p['fecha_boletin']) ?></td>
                    <td><?= htmlspecialchars($p['set_alias']) ?></td>
                    <td class="mono"><?= $p['cant_parrafos'] ?></td>
                    <td>
                        <span class="badge badge-<?= $p['tiene_resultados'] === 'Si' ? 'verde' : 'gris' ?>">
                            <?= $p['tiene_resultados'] ?>
                        </span>
                    </td>
                    <td class="text-small text-muted"><?= $p['fecha_ejecucion'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- RESULTADOS -->
<?php if ($ejecucion_id): ?>
<div class="card">
    <div class="card-title">
        📊 Resultados
        <span class="badge badge-<?= isset($ejecucion) && $ejecucion['tiene_resultados'] === 'Si' ? 'verde' : 'gris' ?>">
            <?= isset($ejecucion) ? $ejecucion['cant_parrafos'] : 0 ?> párrafo(s)
        </span>
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
        <div class="alert alert-info">No se encontraron párrafos para las palabras del set seleccionado.</div>
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
                    <div class="parrafo-item"><?= htmlspecialchars($parrafo) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php endif; ?>

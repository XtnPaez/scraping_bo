<?php
// historicos.php — Tab 3: búsqueda de resultados históricos

define('DB_PATH', 'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

$con = get_con();

// ─── Filtros ──────────────────────────────────────────────────────────────────
$filtro_fecha  = trim($_GET['fecha']  ?? '');
$filtro_texto  = trim($_GET['texto']  ?? '');
$filtro_set_id = (int)($_GET['set_id'] ?? 0);

// ─── Sets para el combo ───────────────────────────────────────────────────────
$sets = $con->query("SELECT id, alias FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);

// ─── Consulta de ejecuciones ──────────────────────────────────────────────────
$where  = ["1=1"];
$params = [];

if ($filtro_fecha !== '') {
    $where[]  = "e.fecha_boletin = :fecha";
    $params[':fecha'] = $filtro_fecha;
}

if ($filtro_set_id > 0) {
    $where[]  = "e.set_id = :set_id";
    $params[':set_id'] = $filtro_set_id;
}

if ($filtro_texto !== '') {
    $where[]  = "EXISTS (
        SELECT 1 FROM resultados r
        WHERE r.ejecucion_id = e.id
        AND (r.palabra LIKE :texto OR r.parrafo LIKE :texto2)
    )";
    $params[':texto']  = '%' . $filtro_texto . '%';
    $params[':texto2'] = '%' . $filtro_texto . '%';
}

$sql = "
    SELECT e.*, s.alias as set_alias
    FROM ejecuciones e
    JOIN sets s ON s.id = e.set_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY e.id DESC
    LIMIT 100
";

$stmt = $con->prepare($sql);
$stmt->execute($params);
$ejecuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ─── Si se pide detalle de una ejecucion ──────────────────────────────────────
$detalle_id = (int)($_GET['ver'] ?? 0);
$detalle    = null;
$resultado  = [];

if ($detalle_id > 0) {
    $stmt = $con->prepare("
        SELECT e.*, s.alias as set_alias
        FROM ejecuciones e
        JOIN sets s ON s.id = e.set_id
        WHERE e.id = ?
    ");
    $stmt->execute([$detalle_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detalle) {
        $stmt2 = $con->prepare(
            "SELECT palabra, parrafo FROM resultados WHERE ejecucion_id = ? ORDER BY palabra"
        );
        $stmt2->execute([$detalle_id]);
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $resultado[$fila['palabra']][] = $fila['parrafo'];
        }
    }
}
?>

<!-- FILTROS -->
<div class="card">
    <div class="card-title">🔍 Buscar en históricos</div>

    <form method="GET" action="index.php">
        <input type="hidden" name="tab" value="historicos">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr auto; gap:1rem; align-items:end;">

            <div class="form-group" style="margin:0">
                <label for="fecha">Fecha del boletín</label>
                <input type="date" id="fecha" name="fecha"
                       value="<?= htmlspecialchars($filtro_fecha) ?>">
            </div>

            <div class="form-group" style="margin:0">
                <label for="set_id">Set de palabras</label>
                <select id="set_id" name="set_id">
                    <option value="0">— Todos —</option>
                    <?php foreach ($sets as $s): ?>
                        <option value="<?= $s['id'] ?>"
                            <?= $filtro_set_id === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['alias']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin:0">
                <label for="texto">Palabra o texto</label>
                <input type="text" id="texto" name="texto"
                       placeholder="ej: incompatibilidad"
                       value="<?= htmlspecialchars($filtro_texto) ?>">
            </div>

            <div style="display:flex; gap:8px;">
                <button type="submit" class="btn-primary">Buscar</button>
                <a href="index.php?tab=historicos" class="btn-outline">Limpiar</a>
            </div>

        </div>
    </form>
</div>

<!-- DETALLE DE EJECUCION -->
<?php if ($detalle): ?>
<div class="card">
    <div class="card-title">
        📋 Detalle — Ejecución #<?= $detalle['id'] ?>
        <a href="index.php?tab=historicos&<?= http_build_query(array_filter([
            'fecha'  => $filtro_fecha,
            'texto'  => $filtro_texto,
            'set_id' => $filtro_set_id ?: null
        ])) ?>" class="btn-outline" style="margin-left:auto; font-size:0.8rem; padding:5px 12px;">
            ← Volver
        </a>
    </div>

    <div class="flex gap-2 mb-2" style="flex-wrap:wrap;">
        <span class="badge badge-azul">Boletín: <?= htmlspecialchars($detalle['fecha_boletin']) ?></span>
        <span class="badge badge-azul">Set: <?= htmlspecialchars($detalle['set_alias']) ?></span>
        <span class="badge badge-azul"><?= $detalle['cant_palabras'] ?> palabras buscadas</span>
        <span class="badge badge-<?= $detalle['tiene_resultados'] === 'Si' ? 'verde' : 'gris' ?>">
            <?= $detalle['cant_parrafos'] ?> párrafo(s)
        </span>
        <span class="badge badge-gris"><?= $detalle['fecha_ejecucion'] ?></span>
    </div>

    <div class="text-small text-muted mb-2 mono"><?= htmlspecialchars($detalle['ruta_pdf']) ?></div>

    <?php if (empty($resultado)): ?>
        <div class="alert alert-info">Esta ejecución no tuvo resultados.</div>
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

<?php else: ?>

<!-- LISTADO DE EJECUCIONES -->
<div class="card">
    <div class="card-title">
        📁 Ejecuciones
        <span class="badge badge-gris" style="margin-left:auto;">
            <?= count($ejecuciones) ?> resultado(s)
        </span>
    </div>

    <?php if (empty($ejecuciones)): ?>
        <div class="alert alert-info">No se encontraron ejecuciones con los filtros aplicados.</div>
    <?php else: ?>
        <table class="tabla-resultados">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha boletín</th>
                    <th>Set</th>
                    <th>Palabras</th>
                    <th>Párrafos</th>
                    <th>Resultados</th>
                    <th>Ejecutado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ejecuciones as $ej): ?>
                <tr>
                    <td class="mono"><?= $ej['id'] ?></td>
                    <td><?= htmlspecialchars($ej['fecha_boletin']) ?></td>
                    <td><?= htmlspecialchars($ej['set_alias']) ?></td>
                    <td class="mono"><?= $ej['cant_palabras'] ?></td>
                    <td class="mono"><?= $ej['cant_parrafos'] ?></td>
                    <td>
                        <span class="badge badge-<?= $ej['tiene_resultados'] === 'Si' ? 'verde' : 'gris' ?>">
                            <?= $ej['tiene_resultados'] ?>
                        </span>
                    </td>
                    <td class="text-small text-muted"><?= $ej['fecha_ejecucion'] ?></td>
                    <td>
                        <?php if ($ej['tiene_resultados'] === 'Si'): ?>
                        <a href="index.php?tab=historicos&ver=<?= $ej['id'] ?>&<?= http_build_query(array_filter([
                            'fecha'  => $filtro_fecha,
                            'texto'  => $filtro_texto,
                            'set_id' => $filtro_set_id ?: null
                        ])) ?>"
                           class="btn-outline" style="font-size:0.78rem; padding:4px 12px;">
                            Ver
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php endif; ?>

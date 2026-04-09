<?php
define('DB_PATH', 'C:\\xampp\\htdocs\\scraping_bo\\db\\boletin.db');

function get_con() {
    $con = new PDO('sqlite:' . DB_PATH);
    $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $con;
}

$con = get_con();
$filtro_fecha  = trim($_GET['fecha']  ?? '');
$filtro_texto  = trim($_GET['texto']  ?? '');
$filtro_set_id = (int)($_GET['set_id'] ?? 0);
$sets = $con->query("SELECT id, alias FROM sets ORDER BY alias")->fetchAll(PDO::FETCH_ASSOC);

$where = ["1=1"]; $params = [];
if ($filtro_fecha !== '') { $where[] = "e.fecha_boletin = :fecha"; $params[':fecha'] = $filtro_fecha; }
if ($filtro_set_id > 0)   { $where[] = "e.set_id = :set_id"; $params[':set_id'] = $filtro_set_id; }
if ($filtro_texto !== '') {
    $where[] = "EXISTS (SELECT 1 FROM resultados r WHERE r.ejecucion_id = e.id AND (r.palabra LIKE :texto OR r.parrafo LIKE :texto2))";
    $params[':texto'] = '%' . $filtro_texto . '%';
    $params[':texto2'] = '%' . $filtro_texto . '%';
}

$stmt = $con->prepare("SELECT e.*, s.alias as set_alias FROM ejecuciones e JOIN sets s ON s.id = e.set_id WHERE " . implode(" AND ", $where) . " ORDER BY e.id DESC LIMIT 100");
$stmt->execute($params);
$ejecuciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

$detalle_id = (int)($_GET['ver'] ?? 0);
$detalle = null; $resultado = [];

if ($detalle_id > 0) {
    $stmt = $con->prepare("SELECT e.*, s.alias as set_alias FROM ejecuciones e JOIN sets s ON s.id = e.set_id WHERE e.id = ?");
    $stmt->execute([$detalle_id]);
    $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($detalle) {
        $stmt2 = $con->prepare("SELECT palabra, parrafo FROM resultados WHERE ejecucion_id = ? ORDER BY palabra");
        $stmt2->execute([$detalle_id]);
        foreach ($stmt2->fetchAll(PDO::FETCH_ASSOC) as $fila)
            $resultado[$fila['palabra']][] = $fila['parrafo'];
    }
}
?>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title">Buscar en históricos</h5>
        <hr>
        <form method="GET" action="index.php">
            <input type="hidden" name="tab" value="historicos">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="fecha" class="form-label">Fecha del boletín</label>
                    <input type="date" class="form-control" id="fecha" name="fecha" value="<?= htmlspecialchars($filtro_fecha) ?>">
                </div>
                <div class="col-md-3">
                    <label for="set_id" class="form-label">Set de palabras</label>
                    <select class="form-select" id="set_id" name="set_id">
                        <option value="0">Todos</option>
                        <?php foreach ($sets as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $filtro_set_id === (int)$s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['alias']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="texto" class="form-label">Palabra o texto</label>
                    <input type="text" class="form-control" id="texto" name="texto" placeholder="ej: incompatibilidad" value="<?= htmlspecialchars($filtro_texto) ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Buscar</button>
                    <a href="index.php?tab=historicos" class="btn btn-outline-secondary">Limpiar</a>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($detalle): ?>
<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="card-title mb-0">Detalle — Ejecución #<?= $detalle['id'] ?></h5>
            <a href="index.php?tab=historicos&<?= http_build_query(array_filter(['fecha'=>$filtro_fecha,'texto'=>$filtro_texto,'set_id'=>$filtro_set_id?:null])) ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
        </div>
        <hr>
        <p class="text-muted small mb-3">
            Boletín: <?= htmlspecialchars($detalle['fecha_boletin']) ?> ·
            Set: <?= htmlspecialchars($detalle['set_alias']) ?> ·
            <?= $detalle['cant_palabras'] ?> palabras ·
            <?= $detalle['cant_parrafos'] ?> párrafos ·
            <?= $detalle['fecha_ejecucion'] ?>
        </p>
        <?php if (empty($resultado)): ?>
            <p class="text-muted">Esta ejecución no tuvo resultados.</p>
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

<?php else: ?>
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Ejecuciones <span class="text-muted small fw-normal">(<?= count($ejecuciones) ?> resultado(s))</span></h5>
        <hr>
        <?php if (empty($ejecuciones)): ?>
            <p class="text-muted">No se encontraron ejecuciones con los filtros aplicados.</p>
        <?php else: ?>
            <table class="table table-hover table-sm">
                <thead class="table-dark">
                    <tr>
                        <th>#</th><th>Fecha boletín</th><th>Set</th><th>Palabras</th><th>Párrafos</th><th>Resultado</th><th>Ejecutado</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ejecuciones as $ej): ?>
                    <tr>
                        <td><?= $ej['id'] ?></td>
                        <td><?= htmlspecialchars($ej['fecha_boletin']) ?></td>
                        <td><?= htmlspecialchars($ej['set_alias']) ?></td>
                        <td><?= $ej['cant_palabras'] ?></td>
                        <td><?= $ej['cant_parrafos'] ?></td>
                        <td><?= $ej['tiene_resultados'] ?></td>
                        <td class="text-muted small"><?= $ej['fecha_ejecucion'] ?></td>
                        <td><?php if ($ej['tiene_resultados'] === 'Si'): ?>
                            <a href="index.php?tab=historicos&ver=<?= $ej['id'] ?>&<?= http_build_query(array_filter(['fecha'=>$filtro_fecha,'texto'=>$filtro_texto,'set_id'=>$filtro_set_id?:null])) ?>" class="btn btn-sm btn-outline-secondary">Ver</a>
                        <?php endif; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

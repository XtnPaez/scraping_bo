<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'subir';
$tabs_validos = ['subir', 'ejecutar', 'historicos', 'sets', 'reejecutar'];
if (!in_array($tab, $tabs_validos)) $tab = 'subir';
$tab_activo = $tab;
?>
<!DOCTYPE html>
<html lang="es" class="h-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>scraping_BO — <?= ucfirst($tab) ?></title>
    <link rel="icon" href="data:,">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="d-flex flex-column h-100">

<?php include 'navbar.php'; ?>

<main class="flex-shrink-0">
    <div class="container-fluid px-4 py-4">
        <?php include $tab . '.php'; ?>
    </div>
</main>

<?php include 'footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>

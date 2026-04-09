<?php
// index.php — punto de entrada principal
// Enruta a cada tab según ?tab=

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'subir';
$tabs_validos = ['subir', 'ejecutar', 'historicos', 'sets', 'reejecutar'];
if (!in_array($tab, $tabs_validos)) $tab = 'subir';

$tab_activo = $tab;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>scraping_BO — <?= ucfirst($tab) ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="main-content">
    <?php include $tab . '.php'; ?>
</div>

<?php include 'footer.php'; ?>

<script src="assets/app.js"></script>
</body>
</html>

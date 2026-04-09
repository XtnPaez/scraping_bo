<?php
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'subir';
$tabs_validos = ['subir', 'ejecutar', 'historicos', 'sets', 'reejecutar'];
if (!in_array($tab, $tabs_validos)) $tab = 'subir';
$tab_activo = $tab;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>scraping_BO — <?= ucfirst($tab) ?></title>

    <!-- Bootstrap 4 -->
    <link rel="stylesheet"
          href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <!-- CSS propio encima de Bootstrap -->
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="main-content">
    <div class="container-fluid px-4">
        <?php include $tab . '.php'; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/app.js"></script>
</body>
</html>

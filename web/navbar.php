<?php
if (!isset($tab_activo)) $tab_activo = '';
?>
<header>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">scraping_BO</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarCollapse" aria-controls="navbarCollapse"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarCollapse">
                <ul class="navbar-nav me-auto mb-2 mb-md-0">
                    <li class="nav-item">
                        <a class="nav-link <?= $tab_activo==='subir'      ? 'active':'' ?>" href="index.php?tab=subir">Subir PDF</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab_activo==='ejecutar'   ? 'active':'' ?>" href="index.php?tab=ejecutar">Ejecutar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab_activo==='historicos' ? 'active':'' ?>" href="index.php?tab=historicos">Históricos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab_activo==='sets'       ? 'active':'' ?>" href="index.php?tab=sets">Sets de palabras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $tab_activo==='reejecutar' ? 'active':'' ?>" href="index.php?tab=reejecutar">Re-ejecutar</a>
                    </li>
                </ul>
                <span class="navbar-text d-none d-md-block">
                    Boletín Oficial · Primera Sección · <?= date('d/m/Y') ?>
                </span>
            </div>
        </div>
    </nav>
</header>

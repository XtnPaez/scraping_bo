<?php
if (!isset($tab_activo)) $tab_activo = '';
?>
<nav class="navbar navbar-expand-md navbar-dark bg-dark fixed-top">
    <a class="navbar-brand" href="index.php">
        <span class="brand-dot"></span>scraping_BO
    </a>
    <span class="navbar-meta ml-auto d-none d-md-block">
        Boletín Oficial · Primera Sección &nbsp;|&nbsp; <?= date('d/m/Y') ?>
    </span>
</nav>

<div class="tab-bar">
    <div class="container-fluid">
        <ul class="nav">
            <li class="nav-item">
                <a class="nav-link <?= $tab_activo==='subir'      ? 'active':'' ?>" href="index.php?tab=subir">📄 Subir PDF</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab_activo==='ejecutar'   ? 'active':'' ?>" href="index.php?tab=ejecutar">▶️ Ejecutar</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab_activo==='historicos' ? 'active':'' ?>" href="index.php?tab=historicos">🔍 Históricos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab_activo==='sets'       ? 'active':'' ?>" href="index.php?tab=sets">🗂️ Sets de palabras</a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $tab_activo==='reejecutar' ? 'active':'' ?>" href="index.php?tab=reejecutar">🔁 Re-ejecutar</a>
            </li>
        </ul>
    </div>
</div>

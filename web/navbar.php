<?php
// navbar.php — incluir en todas las páginas
// Uso: include 'navbar.php';
// Definir $tab_activo antes de incluir: $tab_activo = 'subir';
// Valores posibles: 'subir', 'ejecutar', 'historicos', 'sets', 'reejecutar'

if (!isset($tab_activo)) $tab_activo = '';
?>
<nav class="navbar">
    <div class="navbar-brand">
        <span class="brand-dot"></span>
        scraping_BO
    </div>
    <div class="navbar-meta">
        Boletín Oficial · Primera Sección &nbsp;|&nbsp; <?= date('d/m/Y') ?>
    </div>
</nav>

<div class="tab-bar">
    <a href="index.php?tab=subir"      class="<?= $tab_activo === 'subir'      ? 'active' : '' ?>">
        <span class="tab-icon">📄</span> Subir PDF
    </a>
    <a href="index.php?tab=ejecutar"   class="<?= $tab_activo === 'ejecutar'   ? 'active' : '' ?>">
        <span class="tab-icon">▶️</span> Ejecutar
    </a>
    <a href="index.php?tab=historicos" class="<?= $tab_activo === 'historicos' ? 'active' : '' ?>">
        <span class="tab-icon">🔍</span> Históricos
    </a>
    <a href="index.php?tab=sets"       class="<?= $tab_activo === 'sets'       ? 'active' : '' ?>">
        <span class="tab-icon">🗂️</span> Sets de palabras
    </a>
    <a href="index.php?tab=reejecutar" class="<?= $tab_activo === 'reejecutar' ? 'active' : '' ?>">
        <span class="tab-icon">🔁</span> Re-ejecutar
    </a>
</div>

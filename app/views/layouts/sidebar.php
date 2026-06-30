<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$menu_items = $_SESSION['menu_items'] ?? [];
?>

<aside class="main_sidebar">
    <div class="sidebar-header">
        <div class="login-logo"><img src="assets/images/icono_takab.png" alt="logo_TAKAB" width="90" height="55"></div>
        <div>
            <div class="sidebar-title">TAKAB</div>
            <div class="sidebar-desc">ERP Takab</div>
        </div>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($menu_items as $item): ?>
            <?php 
            if ($item['role'] !== 'Todos' && $role !== $item['role']) {
                continue; 
            }
           $clase_activa = (isset($seccion_activa) && $seccion_activa === $item['slug']) ? 'class="active"' : ''; 
            ?>
            
            <a href="<?= $item['slug'] ?>" <?= $clase_activa ?>>
                <i class="fa-solid <?= $item['icon'] ?>"></i> <?= $item['label'] ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
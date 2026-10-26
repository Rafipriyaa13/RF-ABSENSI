<?php

$baseUrl = defined('BASE_URL') ? BASE_URL : '';
$pageTitle    = $pageTitle    ?? 'Dashboard';
$pageSubtitle = $pageSubtitle ?? '';
$addButton    = $addButton    ?? '';   
$addModal     = $addModal     ?? '';   
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> | RF Absensi</title>
    <link rel="stylesheet" href="<?= $baseUrl ?>/assets/css/style.css">
</head>
<body>
<div class="app">

    <?php include __DIR__ . '/SidebarManager.php'; ?>

    <!-- Overlay gelap saat sidebar terbuka di mobile -->
    <div class="overlay" id="overlay"></div>

    <div class="main">
        <header class="topbar">
            <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>

            <div class="topbar-title">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($pageSubtitle): ?>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>

            <div class="topbar-actions">
                <?php if ($addButton && $addModal): ?>
                    <button type="button" class="btn btn-dark" data-modal-open="<?= htmlspecialchars($addModal) ?>">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        <span><?= htmlspecialchars($addButton) ?></span>
                    </button>
                <?php endif; ?>
                <span class="status" id="statusBadge"><i class="dot"></i><span>Online</span></span>
                <button class="icon-btn" id="refreshBtn" aria-label="Muat ulang">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/></svg>
                </button>
            </div>
        </header>

        <main class="content">
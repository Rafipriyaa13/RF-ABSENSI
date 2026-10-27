<?php
// Variabel yang dibaca dari halaman: $pageTitle, $pageSubtitle, $activeMenu, $topAction (opsional: [label, url])
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle ?? 'Richeese Absensi') ?> - Richeese Factory</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time(); ?>">
</head>

<body>
    <?php include __DIR__ . '/SidebarManager.php'; ?>
    <div class="main">
        <header class="topbar">
            <button class="icon-btn" type="button" data-nav-toggle aria-label="Buka menu">&#9776;</button>
            <div class="title">
                <h1><?= e($pageTitle ?? '') ?></h1>
                <p><?= e($pageSubtitle ?? '') ?></p>
            </div>
            <?php if (!empty($topAction)): ?>
                <a class="btn" href="<?= e($topAction[1]) ?>">+ <span class="txt"><?= e($topAction[0]) ?></span></a>
            <?php endif; ?>
            <button class="icon-btn" type="button" onclick="location.reload()" aria-label="Muat ulang">&#8635;</button>
        </header>
        <main class="content">
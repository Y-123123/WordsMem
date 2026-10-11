<?php
header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
$cur_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
<title><?= isset($page_title) ? e($page_title) . ' — ' : '' ?><?= APP_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/bootstrap-icons/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<nav class="navbar navbar-dark bg-dark py-2 shadow-sm">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>/dashboard.php">
      <i class="bi bi-book-half text-warning"></i> <?= APP_NAME ?>
    </a>
    <?php if (is_logged_in()): ?>
    <div class="d-flex align-items-center gap-3">
      <a href="<?= BASE_URL ?>/dashboard.php" class="nav-link d-inline text-white<?= $cur_page === 'dashboard.php' ? ' fw-bold text-warning' : '' ?>">概览</a>
      <a href="<?= BASE_URL ?>/study.php" class="nav-link d-inline text-white<?= $cur_page === 'study.php' ? ' fw-bold text-warning' : '' ?>">背单词</a>
      <a href="<?= BASE_URL ?>/categories.php" class="nav-link d-inline text-white<?= $cur_page === 'categories.php' ? ' fw-bold text-warning' : '' ?>">分类词汇</a>
      <a href="<?= BASE_URL ?>/roots.php" class="nav-link d-inline text-white<?= $cur_page === 'roots.php' ? ' fw-bold text-warning' : '' ?>">词根词缀</a>
      <a href="<?= BASE_URL ?>/settings.php" class="nav-link d-inline text-white<?= $cur_page === 'settings.php' ? ' fw-bold text-warning' : '' ?>">设置</a>
      <div class="dropdown">
        <a class="text-white text-decoration-none dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle"></i> <?= e(auth_user()['name'] ?? '') ?>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i> 退出</a></li>
        </ul>
      </div>
    </div>
    <?php endif; ?>
  </div>
</nav>

<main class="container py-4">
  <?= flash_html() ?>

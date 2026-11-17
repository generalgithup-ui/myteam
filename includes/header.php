<?php
require_once __DIR__ . '/bootstrap.php';
$pageTitle = $pageTitle ?? 'TEAM Assignment - M5';
$isAdminPage = $isAdminPage ?? false;
$admin = current_admin();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title><?= e($pageTitle) ?> | TEAM Assignment - M5</title>
  <meta name="app-base" content="<?= e(app_base_path()) ?>">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="<?= e(app_url('/assets/css/style.css')) ?>" rel="stylesheet">
</head>
<body class="<?= $isAdminPage ? 'admin-shell' : 'public-shell' ?>">
<?php if ($isAdminPage): ?>
  <div class="app-layout">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="main-content">
      <?php require __DIR__ . '/navbar.php'; ?>
      <div class="content-wrap">
<?php else: ?>
  <nav class="navbar navbar-expand-lg public-nav">
    <div class="container">
      <a class="navbar-brand fw-bold" href="<?= e(app_url('/student/index.php')) ?>"><span class="brand-mark me-2">T</span>TEAM Assignment <span class="text-primary">M5</span></a>
      <div class="ms-auto d-flex gap-2">
        <a class="btn btn-sm btn-outline-primary" href="<?= e(app_url('/student/subjects.php')) ?>">Subjects</a>
        <a class="btn btn-sm btn-primary" href="<?= e(app_url('/login.php')) ?>">Admin</a>
      </div>
    </div>
  </nav>
<?php endif; ?>

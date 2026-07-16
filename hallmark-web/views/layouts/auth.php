<?php
use App\Core\Config;
/** @var string $content */
/** @var string $title */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Sign in') ?> · <?= e(Config::get('app_name', 'Hallmark')) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="auth-body">
<main class="auth-shell">
  <?= \App\Core\View::partial('partials/flash') ?>
  <?= $content ?>
</main>
</body>
</html>

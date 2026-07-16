<?php
use App\Core\Config;
use App\Support\Auth;
/** @var string $content */
/** @var string $title */
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'Hallmark') ?> · <?= e(Config::get('app_name', 'Hallmark')) ?></title>
<link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="/"><span class="dot"></span><?= e(Config::get('app_name', 'Hallmark')) ?></a>
  <nav class="nav">
    <a href="/">Dashboard</a>
    <a href="/items">Items</a>
    <a href="/items/new" class="cta">+ New composite</a>
  </nav>
  <form class="signout" action="/logout" method="post">
    <?= csrf_field() ?>
    <span class="who"><?= e(Auth::username()) ?></span>
    <button type="submit">Sign out</button>
  </form>
</header>
<main class="container">
  <?= \App\Core\View::partial('partials/flash') ?>
  <?= $content ?>
</main>
<footer class="pagefoot">
  Hallmark — Image Processing &amp; AI Automation prototype · single-admin
</footer>
</body>
</html>

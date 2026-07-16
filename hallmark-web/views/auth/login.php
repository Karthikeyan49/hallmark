<?php
use App\Core\Config;
?>
<div class="auth-card">
  <div class="auth-head">
    <span class="dot"></span>
    <h1><?= e(Config::get('app_name', 'Hallmark')) ?></h1>
    <p>Image Processing &amp; AI Automation — admin sign in</p>
  </div>
  <form action="/login" method="post" class="stack">
    <?= csrf_field() ?>
    <label>Username
      <input type="text" name="username" autofocus required value="<?= e(Config::get('admin_username')) ?>">
    </label>
    <label>Password
      <input type="password" name="password" required placeholder="••••••••">
    </label>
    <button type="submit" class="btn btn-gold">Sign in</button>
  </form>
  <p class="hint">Prototype single admin — default password <code><?= e(Config::get('admin_password')) ?></code></p>
</div>

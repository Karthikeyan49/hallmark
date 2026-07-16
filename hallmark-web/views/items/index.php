<?php
/** @var array $items */
?>
<div class="page-head">
  <h1>Compliance items</h1>
  <a href="/items/new" class="btn btn-gold">+ New composite</a>
</div>

<div class="card">
  <?php if (!$items): ?>
    <p class="empty">No composites yet. <a href="/items/new">Generate the first one.</a></p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>UID</th><th>Type</th><th>Weight</th><th>Track</th><th>Provider</th><th>Cost</th><th>Created</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td class="mono"><?= e($item['uid']) ?></td>
        <td><?= e($item['item_type'] ?: '—') ?></td>
        <td><?= number_format((float) $item['weight_grams'], 2) ?> g</td>
        <td><span class="badge">Track <?= e($item['track']) ?></span></td>
        <td><?= e($item['provider']) ?></td>
        <td><?= money_inr((float) $item['cost_inr']) ?></td>
        <td class="muted"><?= e($item['created_at']) ?></td>
        <td><a href="/items/<?= (int) $item['id'] ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

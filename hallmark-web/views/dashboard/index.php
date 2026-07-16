<?php
/** @var array $stats */
/** @var array $recent */
?>
<div class="page-head">
  <h1>Dashboard</h1>
  <a href="/items/new" class="btn btn-gold">+ New composite</a>
</div>

<section class="cards">
  <div class="card stat">
    <span class="stat-label">Items processed</span>
    <span class="stat-value"><?= (int) $stats['count'] ?></span>
  </div>
  <div class="card stat">
    <span class="stat-label">Total API cost</span>
    <span class="stat-value"><?= money_inr((float) $stats['total_cost']) ?></span>
  </div>
  <div class="card stat">
    <span class="stat-label">Billable to client</span>
    <span class="stat-value"><?= money_inr((float) $stats['billable_cost']) ?></span>
  </div>
</section>

<section class="card">
  <div class="card-head">
    <h2>Recent composites</h2>
    <a href="/items">View all →</a>
  </div>
  <?php if (!$recent): ?>
    <p class="empty">No composites yet. <a href="/items/new">Generate the first one.</a></p>
  <?php else: ?>
  <table class="table">
    <thead><tr><th>UID</th><th>Type</th><th>Weight</th><th>Track</th><th>Provider</th><th>Cost</th><th></th></tr></thead>
    <tbody>
      <?php foreach ($recent as $item): ?>
      <tr>
        <td class="mono"><?= e($item['uid']) ?></td>
        <td><?= e($item['item_type'] ?: '—') ?></td>
        <td><?= number_format((float) $item['weight_grams'], 2) ?> g</td>
        <td><span class="badge">Track <?= e($item['track']) ?></span></td>
        <td><?= e($item['provider']) ?></td>
        <td><?= money_inr((float) $item['cost_inr']) ?></td>
        <td><a href="/items/<?= (int) $item['id'] ?>">Open</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

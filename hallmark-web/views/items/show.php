<?php
/** @var array $item */
?>
<div class="page-head">
  <h1>Composite · <span class="mono"><?= e($item['uid']) ?></span></h1>
  <a href="/items" class="btn btn-ghost">Back to items</a>
</div>

<div class="card">
  <div class="composite-view">
    <img src="/items/<?= (int) $item['id'] ?>/composite" alt="Compliance composite for <?= e($item['uid']) ?>">
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <h2>Item details</h2>
    <dl class="detail">
      <dt>UID</dt><dd class="mono"><?= e($item['uid']) ?></dd>
      <dt>Item type</dt><dd><?= e($item['item_type'] ?: '—') ?></dd>
      <dt>Company</dt><dd><?= e($item['company'] ?: '—') ?></dd>
      <dt>Weight</dt><dd><?= number_format((float) $item['weight_grams'], 2) ?> g</dd>
      <dt>Created</dt><dd><?= e($item['created_at']) ?></dd>
    </dl>
  </div>
  <div class="card">
    <h2>Pipeline</h2>
    <dl class="detail">
      <dt>Track</dt><dd><span class="badge">Track <?= e($item['track']) ?></span>
        <?= $item['track'] === 'B' ? '(uploaded UID crop)' : '(deterministic UID render)' ?></dd>
      <dt>Model provider</dt><dd><?= e($item['provider']) ?></dd>
      <dt>Cost</dt><dd><?= money_inr((float) $item['cost_inr']) ?> / image</dd>
      <dt>Billed to client</dt><dd><?= ((int) $item['billable']) ? 'Yes' : 'No' ?></dd>
    </dl>
    <a class="btn btn-ghost" href="/items/<?= (int) $item['id'] ?>/composite" download>Download PNG</a>
  </div>
</div>

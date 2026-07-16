<?php
/** @var string $provider */
/** @var float $maxCost */
?>
<div class="page-head">
  <h1>New compliance composite</h1>
  <a href="/items" class="btn btn-ghost">Back to items</a>
</div>

<div class="grid-2">
  <form class="card stack" action="/items" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>UID <span class="req">*</span>
      <input type="text" name="uid" placeholder="HUID-7A2C9K" required>
      <small>The real laser-etched UID. 3–40 chars: letters, digits, - or /.</small>
    </label>

    <label>Weight (grams) <span class="req">*</span>
      <input type="number" name="weight_grams" step="0.01" min="0.01" placeholder="2.00" required>
    </label>

    <label>Item type
      <input type="text" name="item_type" placeholder="Ring / Pendant / Stud">
    </label>

    <label>Company / centre
      <input type="text" name="company" placeholder="Optional — shown in the header">
    </label>

    <label>Model image <span class="req">*</span>
      <input type="file" name="model_image" accept="image/*" required>
      <small>A single clean studio photo of the product.</small>
    </label>

    <label>UID close-up <span class="muted">(optional → Track B)</span>
      <input type="file" name="uid_image" accept="image/*">
      <small>Macro of the etched UID. Omit to use the deterministic UID render (Track A).</small>
    </label>

    <label>Model-pane provider
      <select name="provider">
        <option value="">Server default (<?= e($provider) ?>)</option>
        <option value="passthrough">passthrough — 0 INR</option>
        <option value="flux">flux — generative (~0.25 INR)</option>
      </select>
    </label>

    <button type="submit" class="btn btn-gold">Generate composite</button>
  </form>

  <aside class="card info">
    <h2>How it works</h2>
    <p>One product photo + UID + weight becomes the portal's standardised
       three-pane deliverable:</p>
    <ol class="panes">
      <li><strong>UID</strong> — the real etched UID (rendered from data or your close-up).</li>
      <li><strong>Model</strong> — the studio product shot.</li>
      <li><strong>Weight</strong> — the item on a scale showing the measured weight.</li>
    </ol>
    <p class="note">UID and weight are drawn from verified data — never AI-generated —
       so they stay accurate and compliant. Only the model pane can use a paid
       model, capped at <strong><?= money_inr((float) $maxCost) ?>/image</strong>.</p>
  </aside>
</div>

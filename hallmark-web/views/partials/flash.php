<?php
use App\Core\Session;

$flash = Session::takeFlash();
foreach ($flash as $type => $message):
    $class = $type === 'error' ? 'flash flash-error' : 'flash flash-ok';
?>
<div class="<?= e($class) ?>"><?= e($message) ?></div>
<?php endforeach; ?>

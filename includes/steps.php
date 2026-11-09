<?php
/** @var int $stepDone  jumlah langkah yang aktif (1-4) */
$labels = ['1. Cart', '2. Shipping', '3. Payment', '4. Confirmation'];
?>
<ol class="steps <?= e($stepsClass ?? '') ?>">
    <?php foreach ($labels as $i => $label): ?>
        <li class="<?= $i < $stepDone ? 'active' : '' ?>"><?= e($label) ?></li>
    <?php endforeach; ?>
</ol>

<script>window.LZFJ = { baseUrl: <?= json_encode(base_url()) ?>, csrf: <?= json_encode(csrf_token()) ?> };</script>
<script src="<?= e(asset('js/app.js')) ?>?v=1"></script>
<?php foreach (($extraScripts ?? []) as $src): ?>
<script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>

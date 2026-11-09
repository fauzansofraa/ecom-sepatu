<?php
/** @var string $pageTitle */
/** @var string $pageDescription */
/** @var string $bodyClass */
$pageTitle = $pageTitle ?? 'LZFJ Shoes Store';
$pageDescription = $pageDescription ?? 'Premium sneakers and everyday footwear, picked for comfort and made to last.';
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <meta property="og:title" content="<?= e($pageTitle) ?>">
    <meta property="og:description" content="<?= e($pageDescription) ?>">
    <meta property="og:type" content="website">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>?v=3">
    <link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
<?php $flashes = take_flashes(); if ($flashes): ?>
<div class="toast-stack" data-auto-hide>
    <?php foreach ($flashes as $f): ?>
        <div class="toast toast-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

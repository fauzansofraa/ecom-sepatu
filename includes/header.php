<?php
/** @var string $activeNav  home|shop|men|women|kids */
$activeNav = $activeNav ?? '';
$navItems = [
    'home'  => ['Home', 'home.php'],
    'shop'  => ['Shop', 'shop.php'],
    'men'   => ['Men', 'men.php'],
    'women' => ['Women', 'women.php'],
    'kids'  => ['Kids', 'kids.php'],
];
$count = cart_count();
$me = current_user();
require APP_ROOT . '/includes/head.php';
?>
<div class="topbar">Free shipping on orders over Rp 1.000.000 &nbsp;&nbsp;|&nbsp;&nbsp; Sale up to 50% off</div>
<header class="site-header">
    <div class="container header-inner">
        <a class="logo" href="<?= e(url('home.php')) ?>"><b>LZFJ</b> <span>Shoes Store</span></a>
        <nav class="main-nav" aria-label="Main">
            <?php foreach ($navItems as $key => [$label, $href]): ?>
                <a href="<?= e(url($href)) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="header-icons">
            <button type="button" class="icon-btn" data-search-toggle aria-label="Search"><?= icon('search', 22) ?></button>
            <a class="icon-btn" href="<?= e(url($me ? 'account.php' : 'login.php')) ?>" aria-label="<?= $me ? 'My account' : 'Login' ?>">
                <?php if ($me && $me['avatar_url']): ?>
                    <img class="avatar-sm" src="<?= e($me['avatar_url']) ?>" alt="" referrerpolicy="no-referrer">
                <?php else: ?>
                    <?= icon('user', 22) ?>
                <?php endif; ?>
            </a>
            <a class="icon-btn cart-btn" href="<?= e(url('cart.php')) ?>" aria-label="Cart">
                <?= icon('bag', 22) ?>
                <span class="cart-count" data-cart-count <?= $count ? '' : 'hidden' ?>><?= $count ?></span>
            </a>
        </div>
    </div>
    <form class="search-drawer" action="<?= e(url('shop.php')) ?>" method="get" data-search-drawer hidden>
        <div class="container">
            <input type="search" name="q" placeholder="Search shoes..." aria-label="Search shoes" value="<?= e($_GET['q'] ?? '') ?>">
            <button class="btn btn-dark" type="submit">Search</button>
        </div>
    </form>
</header>

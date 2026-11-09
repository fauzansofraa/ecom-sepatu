<?php
require __DIR__ . '/includes/bootstrap.php';

$featured = db()->query('SELECT * FROM products WHERE is_featured > 0 ORDER BY is_featured ASC LIMIT 4')->fetchAll();

$pageTitle = 'LZFJ Shoes Store — Step Into Greatness';
$pageDescription = 'Premium sneakers and everyday footwear from Nike, Adidas, New Balance, Puma, Converse and Vans.';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';
?>
<main>
    <section class="hero">
        <div class="container hero-inner">
            <div class="hero-copy">
                <span class="eyebrow">NEW SEASON</span>
                <h1>Step Into<br>Greatness</h1>
                <p>Premium sneakers and everyday footwear, picked for comfort and made to last.</p>
                <div class="hero-actions">
                    <a class="btn btn-light" href="<?= e(url('shop.php')) ?>">Shop Now</a>
                    <a class="btn btn-ghost-light" href="<?= e(url('shop.php')) ?>">Explore Collection</a>
                </div>
            </div>
            <div class="hero-visual">
                <img src="<?= e(asset('img/hero-shoe.webp')) ?>" alt="Nike Dunk Low Panda">
            </div>
        </div>
    </section>

    <section class="brand-strip">
        <div class="container brand-row">
            <?php foreach (['NIKE', 'ADIDAS', 'NEW BALANCE', 'PUMA', 'CONVERSE', 'VANS'] as $b): ?>
                <a href="<?= e(url('shop.php?q=' . urlencode(ucwords(strtolower($b))))) ?>"><?= $b ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="container features">
        <?php
        $features = [
            ['truck', 'Free Shipping', 'On orders over Rp 1.000.000'],
            ['returns', '30-Day Returns', 'Easy and free exchange'],
            ['shield', '100% Authentic', 'Original products only'],
            ['lock', 'Secure Payment', 'Encrypted checkout'],
        ];
        foreach ($features as [$ic, $t, $s]): ?>
            <div class="feature">
                <span class="feature-icon"><?= icon($ic, 18) ?></span>
                <div><strong><?= e($t) ?></strong><small><?= e($s) ?></small></div>
            </div>
        <?php endforeach; ?>
    </section>

    <section class="container section">
        <h2 class="section-title">Shop by Category</h2>
        <div class="grid-4">
            <a class="category-card" href="<?= e(url('men.php')) ?>">Men</a>
            <a class="category-card" href="<?= e(url('women.php')) ?>">Women</a>
            <a class="category-card" href="<?= e(url('kids.php')) ?>">Kids</a>
        </div>
    </section>

    <section class="container section">
        <div class="section-head">
            <h2 class="section-title">Featured Products</h2>
            <a class="link" href="<?= e(url('shop.php')) ?>">View all</a>
        </div>
        <div class="grid-4">
            <?php $cardLinksToShop = true; foreach ($featured as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
        </div>
    </section>

    <section class="container section section-testimonials">
        <h2 class="section-title">What Our Customers Say</h2>
        <div class="grid-3">
            <?php
            $reviews = [
                'Sepatunya nyaman dipakai seharian, ukuran pas dan pengiriman cepat.',
                'Kualitas bagus dan sesuai foto. Packing rapi dan aman.',
                'Harga bersahabat, original, dan layanan pelanggannya responsif.',
            ];
            foreach ($reviews as $r): ?>
                <figure class="testimonial">
                    <div class="stars"><?= str_repeat(star_icon(14), 5) ?></div>
                    <blockquote><?= e($r) ?></blockquote>
                    <figcaption><strong>[Nama Pelanggan]</strong><small>Verified buyer</small></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="newsletter">
        <div class="container newsletter-inner">
            <div>
                <h2>Get 10% off your first order</h2>
                <p>Subscribe for new drops and exclusive deals.</p>
            </div>
            <form class="newsletter-form" action="<?= e(url('subscribe.php')) ?>" method="post">
                <?= csrf_field() ?>
                <input type="email" name="email" placeholder="Enter your email" required aria-label="Email">
                <button class="btn btn-dark" type="submit">Subscribe</button>
            </form>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>

<?php
/** @var array $p  baris produk dari database */
?>
<article class="product-card" data-product-id="<?= (int) $p['id'] ?>" data-slug="<?= e($p['slug']) ?>">
    <div class="product-media">
        <?php if (!empty($p['badge'])): ?><span class="badge"><?= e($p['badge']) ?></span><?php endif; ?>
        <button type="button" class="wish-btn" data-wish="<?= (int) $p['id'] ?>" aria-label="Add to wishlist"><?= icon('heart', 14) ?></button>
        <img src="<?= e(product_image($p['image'])) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
    </div>
    <div class="product-info">
        <span class="product-cat"><?= e(strtoupper($p['category'])) ?></span>
        <h3 class="product-name"><?= e($p['name']) ?></h3>
        <div class="product-rating"><?= star_icon(13) ?> <span><?= e(number_format((float) $p['rating'], 1)) ?></span> <span class="muted">(<?= (int) $p['reviews'] ?>)</span></div>
        <div class="product-price">
            <strong><?= e(rupiah((int) $p['price'])) ?></strong>
            <?php if (!empty($p['old_price'])): ?><s><?= e(rupiah((int) $p['old_price'])) ?></s><?php endif; ?>
        </div>
        <?php if (!empty($cardLinksToShop)): ?>
            <a class="btn btn-outline btn-block" href="<?= e(url('shop.php?product=' . urlencode($p['slug']))) ?>">View Details</a>
        <?php else: ?>
            <button type="button" class="btn btn-outline btn-block" data-view-details="<?= (int) $p['id'] ?>">View Details</button>
        <?php endif; ?>
    </div>
</article>

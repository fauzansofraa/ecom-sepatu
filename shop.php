<?php
require_once __DIR__ . '/includes/bootstrap.php';

/** $gender diset oleh men.php / women.php / kids.php. Kosong = semua produk. */
$gender = $gender ?? '';
$genderLabels = ['men' => 'Men', 'women' => 'Women', 'kids' => 'Kids'];
$selfPage = $gender ? $gender . '.php' : 'shop.php';

// Ukuran yang ditampilkan di filter samping
$sizeOptions = match ($gender) {
    'women' => ['37', '38', '39', '40'],
    'kids'  => ['24', '25', '26', '27'],
    default => ['40', '41', '42', '43'],
};
$categoryOptions = ['running' => 'Running', 'casual' => 'Casual', 'lifestyle' => 'Lifestyle'];
$colorOptions = ['Black', 'White', 'Grey'];
$priceMin = 500000;
$priceMax = 3000000;

// ---------------- Baca filter dari URL
$arr = fn($k) => array_values(array_filter((array) ($_GET[$k] ?? []), 'is_string'));
$fCategory = array_values(array_intersect($arr('category'), array_keys($categoryOptions)));
$fSize     = array_values(array_intersect($arr('size'), $sizeOptions));
$fColor    = array_values(array_intersect($arr('color'), $colorOptions));
$fMax      = isset($_GET['max_price']) ? max($priceMin, min($priceMax, (int) $_GET['max_price'])) : $priceMax;
$q         = trim((string) ($_GET['q'] ?? ''));
$sort      = in_array($_GET['sort'] ?? '', ['newest', 'price_asc', 'price_desc', 'rating'], true) ? $_GET['sort'] : 'newest';
$perPage   = 4;
$page      = max(1, (int) ($_GET['page'] ?? 1));

// ---------------- Query
$where = ['1=1'];
$params = [];
if ($gender) {
    $where[] = 'gender = ?';
    $params[] = $gender;
}
if ($fCategory) {
    $where[] = 'category IN (' . implode(',', array_fill(0, count($fCategory), '?')) . ')';
    array_push($params, ...$fCategory);
}
if ($fSize) {
    $where[] = '(' . implode(' OR ', array_fill(0, count($fSize), 'FIND_IN_SET(?, sizes)')) . ')';
    array_push($params, ...$fSize);
}
if ($fColor) {
    $where[] = '(' . implode(' OR ', array_fill(0, count($fColor), 'colors LIKE ?')) . ')';
    foreach ($fColor as $c) {
        $params[] = '%' . $c . ':%';
    }
}
if ($fMax < $priceMax) {
    $where[] = 'price <= ?';
    $params[] = $fMax;
}
if ($q !== '') {
    $where[] = '(name LIKE ? OR brand LIKE ? OR category LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$orderBy = match ($sort) {
    'price_asc'  => 'price ASC, id ASC',
    'price_desc' => 'price DESC, id ASC',
    'rating'     => 'rating DESC, reviews DESC',
    default      => $gender ? 'position ASC, id ASC' : 'rank_all ASC, id ASC',
};
$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM products WHERE $whereSql");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page  = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare("SELECT * FROM products WHERE $whereSql ORDER BY $orderBy LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Produk yang dibuka lewat ?product=slug
$selected = null;
if (!empty($_GET['product'])) {
    $s = db()->prepare('SELECT * FROM products WHERE slug = ?');
    $s->execute([(string) $_GET['product']]);
    $selected = $s->fetch() ?: null;
}
$jsonProducts = [];
foreach ($products as $p) {
    $jsonProducts[(int) $p['id']] = product_public($p);
}
if ($selected) {
    $jsonProducts[(int) $selected['id']] = product_public($selected);
}

/** Bangun URL dengan mempertahankan filter. */
function shop_url(string $self, array $override = [], array $remove = []): string
{
    $qs = array_merge($_GET, $override);
    foreach ($remove as $key => $value) {
        if (is_int($key)) {
            unset($qs[$value]);
        } elseif (isset($qs[$key]) && is_array($qs[$key])) {
            $qs[$key] = array_values(array_diff($qs[$key], [$value]));
        }
    }
    unset($qs['product']);
    $query = http_build_query($qs);
    return url($self . ($query ? '?' . $query : ''));
}

$chips = [];
foreach ($fCategory as $c) {
    $chips[] = [$categoryOptions[$c], shop_url($selfPage, ['page' => 1], ['category' => $c])];
}
foreach ($fSize as $s) {
    $chips[] = ['Size ' . $s, shop_url($selfPage, ['page' => 1], ['size' => $s])];
}
foreach ($fColor as $c) {
    $chips[] = [$c, shop_url($selfPage, ['page' => 1], ['color' => $c])];
}
if ($fMax < $priceMax) {
    $chips[] = ['≤ ' . rupiah($fMax), shop_url($selfPage, ['page' => 1], ['max_price'])];
}
if ($q !== '') {
    $chips[] = ['"' . $q . '"', shop_url($selfPage, ['page' => 1], ['q'])];
}

$crumb = $gender ? $genderLabels[$gender] : 'Shop';
$pageTitle = ($gender ? $genderLabels[$gender] . ' Shoes' : 'Shop All Shoes') . ' — LZFJ Shoes Store';
$pageDescription = 'Browse our collection of ' . ($gender ? strtolower($genderLabels[$gender]) . "'s " : '') . 'sneakers: running, casual and lifestyle shoes.';
$activeNav = $gender ?: 'shop';
require __DIR__ . '/includes/header.php';
?>
<main class="container shop-page">
    <nav class="breadcrumb" aria-label="Breadcrumb"><span><?= e($crumb) ?></span><?php if (!$gender): ?><span class="crumb-sep">/</span><?php endif; ?></nav>

    <div class="shop-head">
        <h1 class="page-title">Our Collection</h1>
        <form class="shop-tools" method="get" action="<?= e(url($selfPage)) ?>" data-filter-form>
            <input class="input" type="search" name="q" value="<?= e($q) ?>" placeholder="Search shoes..." aria-label="Search shoes">
            <label class="select-wrap">
                <select name="sort" class="input" aria-label="Sort" data-autosubmit>
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort: Newest</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Sort: Price low-high</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Sort: Price high-low</option>
                    <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Sort: Top rated</option>
                </select>
                <span class="select-caret">v</span>
            </label>
            <?php foreach ($fCategory as $c): ?><input type="hidden" name="category[]" value="<?= e($c) ?>"><?php endforeach; ?>
            <?php foreach ($fSize as $s): ?><input type="hidden" name="size[]" value="<?= e($s) ?>"><?php endforeach; ?>
            <?php foreach ($fColor as $c): ?><input type="hidden" name="color[]" value="<?= e($c) ?>"><?php endforeach; ?>
            <?php if ($fMax < $priceMax): ?><input type="hidden" name="max_price" value="<?= (int) $fMax ?>"><?php endif; ?>
        </form>
    </div>

    <div class="shop-layout">
        <form class="filters" method="get" action="<?= e(url($selfPage)) ?>" data-filter-form>
            <input type="hidden" name="q" value="<?= e($q) ?>">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <fieldset>
                <legend>Category</legend>
                <?php foreach ($categoryOptions as $val => $label): ?>
                    <label class="check"><input type="checkbox" name="category[]" value="<?= e($val) ?>" <?= in_array($val, $fCategory, true) ? 'checked' : '' ?> data-autosubmit><span><?= e($label) ?></span></label>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>Size</legend>
                <?php foreach ($sizeOptions as $s): ?>
                    <label class="check"><input type="checkbox" name="size[]" value="<?= e($s) ?>" <?= in_array($s, $fSize, true) ? 'checked' : '' ?> data-autosubmit><span><?= e($s) ?></span></label>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>Color</legend>
                <?php foreach ($colorOptions as $c): ?>
                    <label class="check"><input type="checkbox" name="color[]" value="<?= e($c) ?>" <?= in_array($c, $fColor, true) ? 'checked' : '' ?> data-autosubmit><span><?= e($c) ?></span></label>
                <?php endforeach; ?>
            </fieldset>
            <fieldset>
                <legend>Price range</legend>
                <input class="range" type="range" name="max_price" min="<?= $priceMin ?>" max="<?= $priceMax ?>" step="100000" value="<?= (int) $fMax ?>" data-range data-autosubmit-change aria-label="Maximum price">
                <div class="range-labels"><span>Rp 500rb</span><span data-range-label><?= $fMax < $priceMax ? e(rupiah($fMax)) : 'Rp 3jt' ?></span></div>
            </fieldset>
            <noscript><button class="btn btn-dark btn-block" type="submit">Apply filters</button></noscript>
        </form>

        <section class="results">
            <div class="results-bar">
                <p class="muted">Showing <?= $total ? ($offset + 1) . '-' . ($offset + count($products)) : 0 ?> of <?= $total ?> products</p>
                <div class="chips">
                    <?php foreach ($chips as [$label, $href]): ?>
                        <a class="chip" href="<?= e($href) ?>"><?= e($label) ?> <span aria-hidden="true">x</span></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php if (!$products): ?>
                <div class="empty-state">
                    <p>No shoes match your filters.</p>
                    <a class="btn btn-outline" href="<?= e(url($selfPage)) ?>">Clear filters</a>
                </div>
            <?php else: ?>
                <div class="grid-2">
                    <?php foreach ($products as $p) { include __DIR__ . '/includes/product-card.php'; } ?>
                </div>
            <?php endif; ?>

            <?php if ($pages > 1): ?>
                <nav class="pagination" aria-label="Pagination">
                    <a class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= e(shop_url($selfPage, ['page' => max(1, $page - 1)])) ?>" aria-label="Previous page"><?= icon('chevron-left', 14) ?></a>
                    <?php
                    $show = array_unique(array_filter([1, 2, 3, $page, $pages], fn($n) => $n >= 1 && $n <= $pages));
                    sort($show);
                    $prev = 0;
                    foreach ($show as $n):
                        if ($n - $prev > 1): ?><span class="page-btn ellipsis">...</span><?php endif; ?>
                        <a class="page-btn <?= $n === $page ? 'active' : '' ?>" href="<?= e(shop_url($selfPage, ['page' => $n])) ?>"><?= $n ?></a>
                    <?php $prev = $n; endforeach; ?>
                    <a class="page-btn <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= e(shop_url($selfPage, ['page' => min($pages, $page + 1)])) ?>" aria-label="Next page"><?= icon('chevron-right', 14) ?></a>
                </nav>
            <?php endif; ?>
        </section>

        <aside class="detail-panel" data-detail-panel aria-live="polite">
            <div class="detail-empty" data-detail-empty>
                <svg width="64" height="64" viewBox="0 0 64 64" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><circle cx="28" cy="28" r="26"/><path d="m47 47 14 14" stroke-linecap="round"/></svg>
                <p>Select to see description</p>
            </div>
            <div class="detail-body" data-detail-body hidden></div>
        </aside>
    </div>
</main>
<script>window.LZFJ_PRODUCTS = <?= json_encode($jsonProducts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>; window.LZFJ_SELECTED = <?= $selected ? (int) $selected['id'] : 'null' ?>;</script>
<?php $extraScripts = [asset('js/shop.js') . '?v=1']; require __DIR__ . '/includes/footer.php'; ?>

/* LZFJ Shoes Store — halaman Shop / Men / Women / Kids */
(function () {
    'use strict';
    var products = window.LZFJ_PRODUCTS || {};
    var panel = document.querySelector('[data-detail-panel]');
    var empty = document.querySelector('[data-detail-empty]');
    var body = document.querySelector('[data-detail-body]');

    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }
    var star = '<svg class="star" width="15" height="15" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2.8l2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.2l-5.7 3.1 1.2-6.4-4.7-4.4 6.4-.8z"/></svg>';
    var truck = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h11v9H3z"/><path d="M14 9h4l3 3v3h-7"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>';

    var state = { id: null, color: null, size: null, qty: 1 };

    function render(p) {
        var sizes = p.sizes;
        // Ukuran default = ukuran ke-4 (seperti desain: 42 / 40 / 26)
        var defaultSize = sizes[Math.min(3, sizes.length - 1)];
        state = { id: p.id, color: p.colors[0] ? p.colors[0].name : '', size: defaultSize, qty: 1 };

        body.innerHTML =
            '<div class="detail-media">' + (p.badge ? '<span class="badge">' + esc(p.badge) + '</span>' : '') +
            '<img src="' + esc(p.image) + '" alt="' + esc(p.name) + '"></div>' +
            '<h2 class="detail-name">' + esc(p.name) + '</h2>' +
            '<div class="detail-meta"><span class="stars">' + star + star + star + star + star + '</span><b>' + esc(p.rating) + '</b> (' + p.reviews + ' reviews) | ' + p.sold + ' sold</div>' +
            '<div class="detail-price">' + esc(p.price_label) + '</div>' +
            '<p class="detail-desc">' + esc(p.description) + '</p>' +
            '<div class="detail-row"><span class="detail-label">Color</span><div class="swatches">' +
            p.colors.map(function (c, i) {
                return '<button type="button" class="swatch' + (i === 0 ? ' active' : '') + '" style="--c:' + esc(c.hex) + '" data-color="' + esc(c.name) + '" title="' + esc(c.name) + '" aria-label="' + esc(c.name) + '"></button>';
            }).join('') + '</div></div>' +
            '<div class="detail-label" style="margin-top:20px">Size</div>' +
            '<div class="sizes">' + sizes.map(function (s) {
                return '<button type="button" class="size-btn' + (s === defaultSize ? ' active' : '') + '" data-size="' + esc(s) + '">' + esc(s) + '</button>';
            }).join('') + '</div>' +
            '<div class="qty-row"><div class="qty"><button type="button" class="qty-btn" data-qty="-1" aria-label="Decrease">-</button><span data-qty-val>1</span><button type="button" class="qty-btn" data-qty="1" aria-label="Increase">+</button></div>' +
            '<span class="in-stock">' + (p.stock > 0 ? 'In stock' : 'Out of stock') + '</span></div>' +
            '<button type="button" class="btn btn-dark btn-block" data-add-to-cart ' + (p.stock > 0 ? '' : 'disabled') + '>Add to Cart</button>' +
            '<p class="ship-note">' + truck + '<span>Free shipping, ready to ship. Free returns within 30 days.</span></p>';

        empty.hidden = true;
        body.hidden = false;
        panel.classList.add('has-product');
        document.querySelectorAll('.product-card').forEach(function (c) {
            c.classList.toggle('is-selected', Number(c.getAttribute('data-product-id')) === p.id);
        });
    }

    function open(id, scroll) {
        var p = products[id];
        if (!p) return;
        render(p);
        var url = new URL(window.location.href);
        url.searchParams.set('product', p.slug);
        history.replaceState(null, '', url.toString());
        if (scroll && window.matchMedia('(max-width: 1100px)').matches) {
            panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    document.addEventListener('click', function (ev) {
        var view = ev.target.closest('[data-view-details]');
        if (view) { open(Number(view.getAttribute('data-view-details')), true); return; }

        var card = ev.target.closest('.product-card');
        if (card && !ev.target.closest('button, a')) { open(Number(card.getAttribute('data-product-id')), true); return; }

        var sw = ev.target.closest('[data-color]');
        if (sw) {
            state.color = sw.getAttribute('data-color');
            body.querySelectorAll('[data-color]').forEach(function (b) { b.classList.toggle('active', b === sw); });
            return;
        }
        var sz = ev.target.closest('[data-size]');
        if (sz) {
            state.size = sz.getAttribute('data-size');
            body.querySelectorAll('[data-size]').forEach(function (b) { b.classList.toggle('active', b === sz); });
            return;
        }
        var q = ev.target.closest('[data-qty]');
        if (q) {
            state.qty = Math.max(1, Math.min(10, state.qty + Number(q.getAttribute('data-qty'))));
            body.querySelector('[data-qty-val]').textContent = state.qty;
            return;
        }
        var add = ev.target.closest('[data-add-to-cart]');
        if (add) {
            add.disabled = true;
            add.textContent = 'Adding...';
            window.lzfjPost('cart-action.php', { action: 'add', product_id: state.id, size: state.size, color: state.color, qty: state.qty })
                .then(function (res) {
                    if (res.ok) {
                        var badge = document.querySelector('[data-cart-count]');
                        if (badge) { badge.textContent = res.count; badge.hidden = false; }
                        window.lzfjToast(res.message, 'success', window.LZFJ.baseUrl + '/cart.php', 'View cart');
                    } else {
                        window.lzfjToast(res.message || 'Could not add to cart.', 'error');
                    }
                })
                .catch(function () { window.lzfjToast('Network error, please try again.', 'error'); })
                .finally(function () { add.disabled = false; add.textContent = 'Add to Cart'; });
        }
    });

    // Filter otomatis terkirim saat dicentang
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });
    var range = document.querySelector('[data-range]');
    if (range) {
        var label = document.querySelector('[data-range-label]');
        var paint = function () {
            var pct = (range.value - range.min) / (range.max - range.min) * 100;
            range.style.setProperty('--fill', pct + '%');
            var v = Number(range.value);
            label.textContent = v >= Number(range.max) ? 'Rp 3jt' : 'Rp ' + v.toLocaleString('id-ID');
        };
        range.addEventListener('input', paint);
        range.addEventListener('change', function () { range.form.submit(); });
        paint();
    }

    if (window.LZFJ_SELECTED) open(window.LZFJ_SELECTED, false);
})();

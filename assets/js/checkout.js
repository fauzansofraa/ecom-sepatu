/* LZFJ Shoes Store — Checkout + Midtrans Snap */
(function () {
    'use strict';

    function rupiah(n) { return 'Rp ' + Math.round(n).toLocaleString('id-ID'); }

    function openSnap(token, redirectUrl, confirmUrl) {
        if (!window.snap) {
            if (redirectUrl) { window.location.href = redirectUrl; return; }
            window.lzfjToast('Midtrans could not be loaded. Check your internet connection.', 'error');
            return;
        }
        window.snap.pay(token, {
            language: 'id',
            onSuccess: function () { window.location.href = confirmUrl; },
            onPending: function () { window.location.href = confirmUrl; },
            onError: function () { window.lzfjToast('Payment failed. Please try again.', 'error'); window.location.href = confirmUrl; },
            onClose: function () { window.location.href = confirmUrl; }
        });
    }

    // ------------- Halaman Order Confirmation: tombol "Pay Now"
    document.querySelectorAll('[data-pay-now]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var code = btn.getAttribute('data-pay-now');
            btn.disabled = true;
            window.lzfjPost('pay.php', { order: code }).then(function (res) {
                btn.disabled = false;
                if (!res.ok) { window.lzfjToast(res.message, 'error'); return; }
                openSnap(res.token, res.redirect_url, window.location.href);
            });
        });
    });

    // ------------- Halaman Checkout
    var form = document.getElementById('checkout-form');
    if (!form) return;

    var subtotal = Number(form.dataset.subtotal);
    var discount = Number(form.dataset.discount);
    var ship = { regular: Number(form.dataset.shipRegular), express: Number(form.dataset.shipExpress) };
    var out = {
        shipping: form.querySelector('[data-sum-shipping]'),
        discount: form.querySelector('[data-sum-discount]'),
        total: form.querySelector('[data-sum-total]')
    };
    var errorBox = form.querySelector('[data-form-error]');

    function recalc() {
        var d = form.querySelector('input[name=delivery]:checked');
        var s = ship[d ? d.value : 'regular'];
        out.shipping.textContent = rupiah(s);
        out.discount.textContent = '- ' + rupiah(discount);
        out.total.textContent = rupiah(subtotal + s - discount);
    }
    form.querySelectorAll('input[name=delivery]').forEach(function (r) { r.addEventListener('change', recalc); });

    // Pilih metode bayar -> tampilkan daftar bank / e-wallet
    form.querySelectorAll('input[name=payment_method]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            form.querySelectorAll('[data-method-group]').forEach(function (g) {
                var open = g.getAttribute('data-method-group') === radio.value;
                g.classList.toggle('is-open', open);
                g.querySelector('[data-channels]').hidden = !open;
                if (!open) {
                    g.querySelectorAll('input[name=payment_channel]').forEach(function (c) { c.checked = false; });
                }
            });
            var auto = form.querySelector('[data-method-group="' + radio.value + '"] [data-auto-select]');
            if (auto) auto.checked = true;
        });
    });

    // Promo code
    var promoBtn = form.querySelector('[data-apply-promo]');
    var promoInput = form.querySelector('[data-promo-input]');
    var promoMsg = form.querySelector('[data-promo-msg]');
    promoBtn.addEventListener('click', function () {
        promoBtn.disabled = true;
        window.lzfjPost('apply-promo.php', { code: promoInput.value }).then(function (res) {
            promoBtn.disabled = false;
            discount = Number(res.discount || 0);
            promoMsg.textContent = res.message || '';
            promoMsg.classList.toggle('is-error', !res.ok);
            recalc();
        });
    });
    promoInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); promoBtn.click(); } });

    function showError(msg) {
        errorBox.textContent = msg;
        errorBox.hidden = !msg;
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        showError('');
        var bad = [];
        form.querySelectorAll('.form-grid .input').forEach(function (i) {
            var ok = i.value.trim() !== '' && i.checkValidity();
            i.classList.toggle('is-invalid', !ok);
            if (!ok) bad.push(i);
        });
        if (bad.length) { showError('Please complete your shipping information.'); bad[0].focus(); return; }
        if (!form.querySelector('input[name=delivery]:checked')) { showError('Please choose a delivery method.'); return; }
        var method = form.querySelector('input[name=payment_method]:checked');
        if (!method) { showError('Please choose a payment method.'); return; }
        if (!form.querySelector('input[name=payment_channel]:checked')) {
            showError(method.value === 'bank_transfer' ? 'Please choose your bank (BCA, BNI, BRI, Mandiri, BSI, ...).' : 'Please choose your e-wallet.');
            return;
        }

        var btn = form.querySelector('[data-place-order]');
        btn.disabled = true;
        btn.textContent = 'Processing...';
        var data = {};
        new FormData(form).forEach(function (v, k) { if (k !== 'csrf') data[k] = v; });

        window.lzfjPost('place-order.php', data).then(function (res) {
            if (!res.ok) {
                btn.disabled = false;
                btn.textContent = 'Place Order';
                showError(res.message || 'Something went wrong.');
                if (res.redirect) setTimeout(function () { window.location.href = res.redirect; }, 1200);
                return;
            }
            btn.textContent = 'Opening payment...';
            openSnap(res.token, res.redirect_url, res.confirm_url);
        }).catch(function () {
            btn.disabled = false;
            btn.textContent = 'Place Order';
            showError('Network error, please try again.');
        });
    });
})();

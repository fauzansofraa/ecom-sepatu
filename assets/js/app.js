/* LZFJ Shoes Store — script umum */
(function () {
    'use strict';

    window.lzfjToast = function (message, type, linkHref, linkText) {
        var stack = document.querySelector('.toast-stack');
        if (!stack) {
            stack = document.createElement('div');
            stack.className = 'toast-stack';
            document.body.appendChild(stack);
        }
        var t = document.createElement('div');
        t.className = 'toast toast-' + (type || 'success');
        t.textContent = message;
        if (linkHref) {
            var a = document.createElement('a');
            a.href = linkHref;
            a.textContent = linkText || 'View';
            t.appendChild(a);
        }
        stack.appendChild(t);
        setTimeout(function () { t.remove(); }, 4500);
    };

    window.lzfjPost = function (path, data) {
        var body = new FormData();
        Object.keys(data || {}).forEach(function (k) { body.append(k, data[k]); });
        body.append('csrf', window.LZFJ.csrf);
        return fetch(window.LZFJ.baseUrl + '/' + path, {
            method: 'POST',
            body: body,
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        }).then(function (r) {
            return r.json().catch(function () { return { ok: false, message: 'Unexpected server response (HTTP ' + r.status + ').' }; });
        });
    };

    // Toast dari server hilang otomatis
    document.querySelectorAll('[data-auto-hide] .toast').forEach(function (t) {
        setTimeout(function () { t.remove(); }, 5000);
    });

    // Tombol search di header
    var searchToggle = document.querySelector('[data-search-toggle]');
    var drawer = document.querySelector('[data-search-drawer]');
    if (searchToggle && drawer) {
        searchToggle.addEventListener('click', function () {
            drawer.hidden = !drawer.hidden;
            if (!drawer.hidden) drawer.querySelector('input').focus();
        });
    }

    // Show / Hide password
    document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input');
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            btn.textContent = show ? 'Hide' : 'Show';
        });
    });

    // Wishlist (disimpan di browser)
    var wishKey = 'lzfj_wishlist';
    var wish = [];
    try { wish = JSON.parse(localStorage.getItem(wishKey) || '[]'); } catch (e) { wish = []; }
    function paintWish() {
        document.querySelectorAll('[data-wish]').forEach(function (b) {
            b.classList.toggle('is-on', wish.indexOf(b.getAttribute('data-wish')) !== -1);
        });
    }
    document.addEventListener('click', function (ev) {
        var b = ev.target.closest('[data-wish]');
        if (!b) return;
        ev.preventDefault();
        ev.stopPropagation();
        var id = b.getAttribute('data-wish');
        var i = wish.indexOf(id);
        if (i === -1) { wish.push(id); window.lzfjToast('Added to wishlist'); } else { wish.splice(i, 1); }
        try { localStorage.setItem(wishKey, JSON.stringify(wish)); } catch (e) {}
        paintWish();
    });
    paintWish();
})();

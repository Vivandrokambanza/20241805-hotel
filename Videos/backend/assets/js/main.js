'use strict';

// Confirm dialogs for destructive actions
document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (btn) {
        if (!confirm(btn.dataset.confirm)) {
            e.preventDefault();
        }
    }
});

// Auto-dismiss alerts after 5 seconds
document.querySelectorAll('.alert').forEach(function (el) {
    setTimeout(function () {
        el.style.transition = 'opacity .5s';
        el.style.opacity = '0';
        setTimeout(function () { el.remove(); }, 500);
    }, 5000);
});

// Live price estimator on book.php
(function () {
    const form = document.getElementById('booking-form');
    if (!form) return;

    const inputs = ['start_date', 'end_date', 'num_rooms', 'num_guests', 'include_breakfast'];
    inputs.forEach(function (name) {
        const el = form.querySelector('[name="' + name + '"]');
        if (el) el.addEventListener('change', updateEstimate);
    });

    function updateEstimate() {
        const start    = form.querySelector('[name="start_date"]')?.value;
        const end      = form.querySelector('[name="end_date"]')?.value;
        const rooms    = parseInt(form.querySelector('[name="num_rooms"]')?.value) || 1;
        const guests   = parseInt(form.querySelector('[name="num_guests"]')?.value) || 1;
        const bfast    = form.querySelector('[name="include_breakfast"]')?.checked ? 1 : 0;
        const rtId     = form.querySelector('[name="room_type_id"]')?.value;
        const preview  = document.getElementById('price-preview');
        if (!preview || !start || !end || !rtId) return;

        const url = 'ajax_price.php?room_type_id=' + rtId
            + '&start=' + start + '&end=' + end
            + '&num_rooms=' + rooms + '&num_guests=' + guests
            + '&breakfast=' + bfast;

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                preview.textContent = data.total_fmt || '—';
            })
            .catch(function () {});
    }
    updateEstimate();
})();

// Highlight current nav link
(function () {
    const path = window.location.pathname;
    document.querySelectorAll('.main-nav a, .admin-sidebar a').forEach(function (a) {
        if (a.getAttribute('href') && path.endsWith(a.getAttribute('href').replace(/^.*\//, ''))) {
            a.classList.add('active');
        }
    });
})();

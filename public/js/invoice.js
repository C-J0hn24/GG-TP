/**
 * Invoice page — export via print dialog (PDF via browser print).
 */
(function () {
    'use strict';

    var exportBtn = document.querySelector('[data-invoice-export]');
    if (!exportBtn) {
        return;
    }

    exportBtn.addEventListener('click', function () {
        var area = document.getElementById('invoice-print-area');
        if (!area) {
            window.print();
            return;
        }
        document.body.classList.add('invoice-print-mode');
        window.print();
        window.addEventListener(
            'afterprint',
            function () {
                document.body.classList.remove('invoice-print-mode');
            },
            { once: true }
        );
    });
})();

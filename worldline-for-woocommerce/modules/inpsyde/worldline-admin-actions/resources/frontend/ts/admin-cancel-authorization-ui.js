document.addEventListener('DOMContentLoaded', () => {
    const bulkActionsRow = document.querySelector('.wc-order-data-row.wc-order-bulk-actions');
    const totalsRow = document.querySelector('.wc-order-data-row.wc-order-totals-items');
    const cancelRow = document.getElementById('cancel_items_container');
    const itemsTable = document.querySelector('table.woocommerce_order_items');

    if (!bulkActionsRow || !cancelRow || !itemsTable) return;

    bulkActionsRow.insertAdjacentElement('afterend', cancelRow);

    const show = (el) => {
        if (!el) return;
        el.style.display = (el.tagName === 'TR') ? 'table-row' : 'block';
    };
    const hide = (el) => el && (el.style.display = 'none');

    let manualCancelAmount = false;

    function parsePriceComma(v) {
        const s = String(v || '')
            .replace(/\s/g, '')
            .replace(',', '.')
            .replace(/[^0-9.\-]/g, '');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    function formatComma(n) {
        return (Number.isFinite(n) ? n : 0).toFixed(2).replace('.', ',');
    }

    function extractNumber(text) {
        const normalized = text
            .replace(/\./g, '')
            .replace(',', '.')
            .replace(/[^0-9.\-]/g, '');
        const n = parseFloat(normalized);
        return Number.isFinite(n) ? n : 0;
    }

    function updateCancelTotals() {
        let total = 0;

        itemsTable.querySelectorAll('.wl-line-total').forEach((el) => {
            total += parsePriceComma(el.value);
        });

        const amountInput = cancelRow.querySelector('.wl-cancel-amount');
        const btnTotal = cancelRow.querySelector('.wl-cancel-btn-total');

        if (amountInput && !manualCancelAmount) {
            amountInput.value = formatComma(total);
        }

        const displayed = amountInput && manualCancelAmount
            ? parsePriceComma(amountInput.value)
            : total;

        if (btnTotal) btnTotal.textContent = formatComma(displayed);
    }

    function applyCancelInputsToItems() {
        const rows = itemsTable.querySelectorAll('tr.item, tr.shipping, tr.fee');

        rows.forEach((row) => {
            const qtyTd = row.querySelector('td.quantity');
            const totalTd = row.querySelector('td.line_cost');
            if (!totalTd) return;

            if (qtyTd && !qtyTd.dataset.wlOrig) qtyTd.dataset.wlOrig = qtyTd.innerHTML;
            if (!totalTd.dataset.wlOrig) totalTd.dataset.wlOrig = totalTd.innerHTML;

            const viewEl = totalTd.querySelector('.view') || totalTd;
            const currentTotal = extractNumber(viewEl.textContent || '0');

            if (row.classList.contains('item') && qtyTd) {
                const origQty = extractNumber(qtyTd.textContent) || 0;
                const unit = origQty > 0 ? (currentTotal / origQty) : 0;

                qtyTd.innerHTML = `
${qtyTd.dataset.wlOrig}
<div class="edit" style="margin-top:6px;">
    <input type="number"
           class="input-text wl-cancel-qty"
           min="0"
           max="${origQty}"
           value="0"
           data-unit="${unit}"
           style="width:60px;">
</div>
`;

                totalTd.innerHTML = `
${totalTd.dataset.wlOrig}
<div class="edit" style="margin-top:6px;">
    <input type="text"
           class="input-text wl-line-total"
           value=""
           inputmode="decimal"
           autocomplete="off">
</div>
`;
                return;
            }

            totalTd.innerHTML = `
${totalTd.dataset.wlOrig}
<div class="edit" style="margin-top:6px;">
    <input type="text"
           class="input-text wl-line-total"
           value=""
           value="${formatComma(currentTotal)}"
           inputmode="decimal"
           autocomplete="off">
</div>
`;
        });
    }

    function parseLocalizedPrice(v) {
        const s = String(v || '')
            .replace(/\s/g, '')
            .replace(',', '.')
            .replace(/[^0-9.\-]/g, '');
        const n = parseFloat(s);
        return Number.isFinite(n) ? n : 0;
    }

    function restoreItemsTable() {
        itemsTable.querySelectorAll('tr.item, tr.fee, tr.shipping').forEach((row) => {
            const qtyTd = row.querySelector('td.quantity');
            const totalTd = row.querySelector('td.line_cost');

            if (qtyTd && qtyTd.dataset.wlOrig) qtyTd.innerHTML = qtyTd.dataset.wlOrig;
            if (totalTd && totalTd.dataset.wlOrig) totalTd.innerHTML = totalTd.dataset.wlOrig;
        });
    }

    function openCancel() {
        manualCancelAmount = false;
        hide(bulkActionsRow);
        if (totalsRow) hide(totalsRow);
        show(cancelRow);
        applyCancelInputsToItems();
        updateCancelTotals();
    }

    function closeCancel() {
        restoreItemsTable();
        hide(cancelRow);
        show(bulkActionsRow);
        if (totalsRow) show(totalsRow);
    }

    document.body.addEventListener('input', (e) => {
        const amountEl = e.target.closest('.wl-cancel-amount');
        if (!amountEl) return;

        manualCancelAmount = true;
        updateCancelTotals();
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.closest('.do-cancel-items')) {
            e.preventDefault();
            openCancel();
            return;
        }
        if (e.target.closest('.wl-cancel-close, .cancel-cancel')) {
            e.preventDefault();
            closeCancel();
        }
    });

    document.body.addEventListener('input', (e) => {
        const qtyEl = e.target.closest('.wl-cancel-qty');
        if (!qtyEl) return;

        manualCancelAmount = false;

        const unit = parseFloat(qtyEl.dataset.unit) || 0;
        const q = parseFloat(qtyEl.value) || 0;

        const row = qtyEl.closest('tr');
        const totalInput = row?.querySelector('.wl-line-total');
        if (totalInput) {
            const raw = unit * q;
            totalInput.value = raw.toFixed(2).replace('.', ',');
        }

        updateCancelTotals();
    });

    document.body.addEventListener('input', (e) => {
        const totalEl = e.target.closest('.wl-line-total');
        if (!totalEl) return;

        manualCancelAmount = false;

        updateCancelTotals();
    });

    document.body.addEventListener('blur', (e) => {
        const totalEl = e.target.closest('.wl-line-total');
        if (totalEl) {
            totalEl.value = formatComma(parsePriceComma(totalEl.value));
            updateCancelTotals();
            return;
        }

        const amountEl = e.target.closest('.wl-cancel-amount');
        if (amountEl) {
            amountEl.value = formatComma(parsePriceComma(amountEl.value));
            updateCancelTotals();
        }
    }, true);

    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.wl-cancel-submit');
        if (!btn) return;

        const amount = document.querySelector('.wl-cancel-amount').value;
        const orderId = new URLSearchParams(window.location.search).get('id');

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'worldline_cancel_authorization',
                order_id: orderId,
                amount: amount
            })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.data.message);
                } else {
                    alert(res.data.message || 'Cancellation submitted successfully.');
                    setTimeout(() => window.location.reload(), 1500);
                }
            });
    });

    hide(cancelRow);
});
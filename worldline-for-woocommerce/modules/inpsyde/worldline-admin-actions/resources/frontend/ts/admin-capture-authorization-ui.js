document.addEventListener('DOMContentLoaded', () => {
    const bulkActionsRow = document.querySelector('.wc-order-data-row.wc-order-bulk-actions');
    const totalsRow = document.querySelector('.wc-order-data-row.wc-order-totals-items');
    const captureRow = document.getElementById('capture_items_container');
    const itemsTable = document.querySelector('table.woocommerce_order_items');

    if (!bulkActionsRow || !captureRow || !itemsTable) return;

    bulkActionsRow.insertAdjacentElement('afterend', captureRow);

    const show = (el) => {
        if (!el) return;
        el.style.display = (el.tagName === 'TR') ? 'table-row' : 'block';
    };
    const hide = (el) => el && (el.style.display = 'none');

    let manualCaptureAmount = false;

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

    function updateCaptureTotals() {
        let total = 0;

        itemsTable.querySelectorAll('.wl-line-total').forEach((el) => {
            total += parsePriceComma(el.value);
        });

        const amountInput = captureRow.querySelector('.wl-capture-amount');
        const btnTotal = captureRow.querySelector('.wl-capture-btn-total');

        if (amountInput && !manualCaptureAmount) {
            amountInput.value = formatComma(total);
        }

        const displayed = amountInput && manualCaptureAmount
            ? parsePriceComma(amountInput.value)
            : total;

        if (btnTotal) btnTotal.textContent = formatComma(displayed);
    }

    function applyCaptureInputsToItems() {
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
                 class="input-text wl-capture-qty"
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

    function openCapture() {
        manualCaptureAmount = false;
        hide(bulkActionsRow);
        if (totalsRow) hide(totalsRow);
        show(captureRow);
        applyCaptureInputsToItems();
        updateCaptureTotals();
    }

    function closeCapture() {
        restoreItemsTable();
        hide(captureRow);
        show(bulkActionsRow);
        if (totalsRow) show(totalsRow);
    }

    document.body.addEventListener('input', (e) => {
        const amountEl = e.target.closest('.wl-capture-amount');
        if (!amountEl) return;

        manualCaptureAmount = true;
        updateCaptureTotals();
    });

    document.body.addEventListener('click', (e) => {
        if (e.target.closest('.do-capture-items')) {
            e.preventDefault();
            openCapture();
            return;
        }
        if (e.target.closest('.wl-capture-close, .capture-capture')) {
            e.preventDefault();
            closeCapture();
        }
    });

    document.body.addEventListener('input', (e) => {
        const qtyEl = e.target.closest('.wl-capture-qty');
        if (!qtyEl) return;

        manualCaptureAmount = false;

        const unit = parseFloat(qtyEl.dataset.unit) || 0;
        const q = parseFloat(qtyEl.value) || 0;

        const row = qtyEl.closest('tr');
        const totalInput = row?.querySelector('.wl-line-total');
        if (totalInput) {
            const raw = unit * q;
            totalInput.value = raw.toFixed(2).replace('.', ',');
        }

        updateCaptureTotals();
    });

    document.body.addEventListener('input', (e) => {
        const totalEl = e.target.closest('.wl-line-total');
        if (!totalEl) return;

        manualCaptureAmount = false;

        updateCaptureTotals();
    });

    document.body.addEventListener('blur', (e) => {
        const totalEl = e.target.closest('.wl-line-total');
        if (totalEl) {
            totalEl.value = formatComma(parsePriceComma(totalEl.value));
            updateCaptureTotals();
            return;
        }

        const amountEl = e.target.closest('.wl-capture-amount');
        if (amountEl) {
            amountEl.value = formatComma(parsePriceComma(amountEl.value));
            updateCaptureTotals();
        }
    }, true);

    document.body.addEventListener('click', e => {
        const btn = e.target.closest('.wl-capture-submit');
        if (!btn) return;

        const amount = document.querySelector('.wl-capture-amount').value;
        const orderId = new URLSearchParams(window.location.search).get('id');

        fetch(ajaxurl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'worldline_capture_authorization',
                order_id: orderId,
                amount: amount
            })
        })
            .then(r => r.json())
            .then(res => {
                if (!res.success) {
                    alert(res.data.message);
                } else {
                    alert(res.data.message || 'Capture submitted successfully.');
                    setTimeout(() => window.location.reload(), 1500);
                }
            });
    });

    jQuery(document.body).on('order-totals-recalculate-complete', function () {
        setTimeout(() => window.location.reload(), 100);
    });

    jQuery(document).ajaxComplete(function (_event, _xhr, settings) {
        const data =
            typeof settings?.data === 'string'
                ? settings.data
                : settings?.data instanceof URLSearchParams
                    ? settings.data.toString()
                    : '';

        if (data.includes('action=woocommerce_refund_line_items')) {
            setTimeout(() => window.location.reload(), 100);
        }
    });

    hide(captureRow);
});

// order.js - calcule le prix unitaire et le total sur la page de commande
(function () {
    function qs(id) { return document.getElementById(id); }

    function parseFloatSafe(v) {
        const n = parseFloat(v);
        return Number.isNaN(n) ? 0 : n;
    }

    function parseIntSafe(v) {
        const n = parseInt(v, 10);
        return Number.isNaN(n) ? 0 : n;
    }

    function luhnCheck(value) {
        if (!value) return false;
        const s = value.replace(/\D/g, '');
        if (s.length < 12) return false; // safeguard: card numbers are usually >= 12
        let sum = 0;
        let shouldDouble = false;
        for (let i = s.length - 1; i >= 0; i--) {
            let digit = parseInt(s.charAt(i), 10);
            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
            shouldDouble = !shouldDouble;
        }
        return (sum % 10) === 0;
    }

    function initOrderForm(options) {
        const { quantityId, unitPriceId, totalId, discountId, annualId, offerSelectId, availableId, cardId, formId, submitBtnId, cardErrorId } = options;
        const qtyEl = qs(quantityId);
        const unitPriceEl = qs(unitPriceId);
        const totalEl = qs(totalId);
        const discountEl = qs(discountId);
        const annualEl = qs(annualId);
        const offerSelect = offerSelectId ? qs(offerSelectId) : null;
        const availableEl = qs(availableId);
        const cardEl = cardId ? qs(cardId) : null;
        const formEl = formId ? qs(formId) : null;
        const submitBtn = submitBtnId ? qs(submitBtnId) : null;
        const cardErrorEl = cardErrorId ? qs(cardErrorId) : null;

        const baseUnitPrice = (typeof window.ORDER_BASE_UNIT_PRICE !== 'undefined') ? parseFloatSafe(window.ORDER_BASE_UNIT_PRICE) : 0;

        function computeFromOffer() {
            let unitPrice = baseUnitPrice;
            let offerDiscount = 0;
            if (offerSelect && offerSelect.selectedIndex >= 0) {
                const opt = offerSelect.options[offerSelect.selectedIndex];
                const d = opt.getAttribute('data-discount');
                offerDiscount = parseFloatSafe(d);
            }

            const extraDiscount = parseFloatSafe(discountEl ? discountEl.value : 0);
            const totalDiscount = (offerDiscount || 0);

            // If the offer defines a discount, update the discount input so it is submitted
            if (discountEl && offerDiscount && !Number.isNaN(offerDiscount)) {
                // format with up to 2 decimals
                discountEl.value = (Math.round(offerDiscount * 100) / 100).toString();
                // make readonly to prevent accidental edits
                discountEl.readOnly = true;
                discountEl.classList.add('readonly');
            } else if (discountEl) {
                // no offer discount -> allow manual edits
                discountEl.readOnly = false;
                discountEl.classList.remove('readonly');
            }

            if (totalDiscount > 0) {
                unitPrice = unitPrice * (1 - totalDiscount / 100);
            }

            if (annualEl && annualEl.checked) {
                unitPrice = unitPrice * 0.90; // 10% pour paiement annuel
            }

            return { unitPrice, offerDiscount };
        }

        function update() {
            const { unitPrice, offerDiscount } = computeFromOffer();
            if (unitPriceEl) unitPriceEl.value = unitPrice.toFixed(2);

            const qty = parseIntSafe(qtyEl ? qtyEl.value : 0);
            if (totalEl) totalEl.value = (unitPrice * qty).toFixed(2);

            if (offerSelect && availableEl) {
                const sel = offerSelect.selectedIndex;
                if (sel >= 0) {
                    const opt = offerSelect.options[sel];
                    const av = parseIntSafe(opt.getAttribute('data-available'));
                    availableEl.textContent = av;
                    if (qty > av) {
                        qtyEl.setCustomValidity(`La quantité demandée (${qty}) dépasse la disponibilité (${av}).`);
                    } else {
                        qtyEl.setCustomValidity('');
                    }
                }
            }
        }

        function updateCardState() {
            if (!cardEl || !submitBtn) return;
            const val = cardEl.value.trim();
            const ok = luhnCheck(val);
            if (ok) {
                submitBtn.disabled = false;
                if (cardErrorEl) {
                    cardErrorEl.style.display = 'none';
                    cardErrorEl.textContent = '';
                }
                cardEl.setCustomValidity('');
            } else {
                submitBtn.disabled = true;
                if (cardErrorEl) {
                    cardErrorEl.style.display = 'block';
                    cardErrorEl.textContent = val ? 'Numéro de carte invalide.' : 'Veuillez saisir un numéro de carte.';
                }
                cardEl.setCustomValidity('Numéro de carte invalide');
            }
        }

        // events
        if (offerSelect) offerSelect.addEventListener('change', update);
        if (qtyEl) qtyEl.addEventListener('input', update);
        if (discountEl) discountEl.addEventListener('input', update);
        if (annualEl) annualEl.addEventListener('change', update);

        // Card events
        if (cardEl) {
            cardEl.addEventListener('input', updateCardState);
            // initial state
            updateCardState();
        }

        // Prevent form submit if invalid
        if (formEl && submitBtn) {
            formEl.addEventListener('submit', function (e) {
                // re-run card validation
                updateCardState();
                if (cardEl && !luhnCheck(cardEl.value.trim())) {
                    e.preventDefault();
                    if (cardErrorEl) cardErrorEl.style.display = 'block';
                    return false;
                }
                // disable button to prevent double submit
                submitBtn.disabled = true;
            });
        }

        // initial
        update();
    }

    // expose to window
    window.OrderUI = {
        init: initOrderForm,
        luhnCheck: luhnCheck
    };
})();

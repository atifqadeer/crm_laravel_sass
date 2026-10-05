{{-- Shared styles and scripts for the create and edit sale forms. Include in @section('css'). --}}
<style>
    /* Section headers */
    .sale-section-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 42px;
        height: 42px;
        font-size: 22px;
        border-radius: 10px;
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
    }

    .sale-section .card-header {
        display: flex;
        align-items: center;
        gap: .75rem;
    }

    .sale-section .form-label {
        font-weight: 500;
    }

    /* Sticky action bar */
    .sale-form-actions {
        position: sticky;
        bottom: 1rem;
        z-index: 10;
        box-shadow: 0 -2px 18px rgba(0, 0, 0, .08);
    }
</style>

<script>
    /**
     * Salary: composes the £ amount(s) and pay period into the hidden "salary" input,
     * e.g. "£12.50 per hour" or "£30,000 - £35,000 per annum".
     * On the edit form a saved salary in that format is pre-filled; anything else is kept until replaced.
     */
    const SalaryField = (function() {
        const picker = () => document.getElementById('salaryPicker');
        let legacy = '';

        function formatAmount(value, period) {
            const amount = parseFloat(value);
            if (!isFinite(amount) || amount <= 0) return '';
            const decimals = period === 'per hour' || amount % 1 !== 0 ? 2 : 0;
            return '£' + amount.toLocaleString('en-GB', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: 2
            });
        }

        function compose() {
            const root = picker();
            const period = root.querySelector('#salary_period').value;
            const amounts = ['#salary_from', '#salary_to']
                .map(sel => parseFloat(root.querySelector(sel).value))
                .filter(n => isFinite(n) && n > 0)
                .sort((a, b) => a - b);

            if (!amounts.length) return '';
            const low = formatAmount(amounts[0], period);
            const high = formatAmount(amounts[amounts.length - 1], period);
            return `${low === high ? low : `${low} - ${high}`} ${period}`;
        }

        function plainText(raw) {
            const doc = new DOMParser().parseFromString(String(raw), 'text/html');
            return (doc.body.textContent || '').replace(/\s+/g, ' ').trim();
        }

        // "£12.50 - £14.00 per hour" → fills the inputs; returns false for any other format
        function prefill(raw) {
            const match = plainText(raw).match(
                /^£\s?([\d,]+(?:\.\d+)?)(?:\s*-\s*£\s?([\d,]+(?:\.\d+)?))?\s+(per hour|per annum)$/i);
            if (!match) return false;

            const root = picker();
            root.querySelector('#salary_from').value = match[1].replace(/,/g, '');
            root.querySelector('#salary_to').value = match[2] ? match[2].replace(/,/g, '') : '';
            root.querySelector('#salary_period').value = match[3].toLowerCase();
            return true;
        }

        function refresh() {
            const root = picker();
            const period = root.querySelector('#salary_period').value;
            const hidden = root.querySelector('#salary');
            const composed = compose();
            const text = composed || legacy;

            hidden.value = text;
            if (text) hidden.classList.remove('is-invalid');

            root.querySelector('#salary_from').placeholder = period === 'per hour' ? 'From, e.g. 12.50' : 'From, e.g. 30000';
            root.querySelector('#salary_to').placeholder = period === 'per hour' ? 'To (optional)' : 'To, e.g. 35000';
            root.querySelector('.salary-preview span').textContent = composed ? `Saved as: ${composed}` :
                (legacy ? `Current: ${plainText(legacy)} — enter an amount to replace it` : 'Enter an amount');
        }

        function init() {
            const root = picker();
            if (!root) return;
            if (root.dataset.initial && !prefill(root.dataset.initial)) {
                legacy = root.dataset.initial;
            }
            root.addEventListener('input', refresh);
            root.addEventListener('change', refresh);
            refresh();
        }

        function validate() {
            refresh();
            const hidden = picker().querySelector('#salary');
            if (hidden.value) return true;

            hidden.classList.add('is-invalid');
            const feedback = picker().querySelector('.invalid-feedback');
            if (!feedback.textContent.trim()) feedback.textContent = 'Please provide salary';
            return false;
        }

        return {
            init,
            validate
        };
    })();
</script>

@include('sales.partials.requirement-picker-assets')

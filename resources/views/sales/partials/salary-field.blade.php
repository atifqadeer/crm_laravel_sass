{{--
    Salary input: £ from / to amounts and a pay period, composed by SalaryField (sale-form-assets)
    into the hidden "salary" input, e.g. "£12.50 - £14.00 per hour" or "£30,000 per annum".

    @param string|null $value  saved salary to pre-fill (edit form); other formats are kept until replaced
--}}
<div class="mb-3" id="salaryPicker" @if (filled($value ?? null)) data-initial="{{ $value }}" @endif>
    <label for="salary_from" class="form-label">Salary <span class="text-danger">*</span></label>
    <div class="input-group has-validation">
        <span class="input-group-text fw-semibold">£</span>
        <input type="number" id="salary_from" class="form-control salary-amount" min="0" step="0.01"
            placeholder="From, e.g. 12.50" aria-label="Salary from">
        <span class="input-group-text">to</span>
        <span class="input-group-text fw-semibold">£</span>
        <input type="number" id="salary_to" class="form-control salary-amount" min="0" step="0.01"
            placeholder="To (optional)" aria-label="Salary to">
        <select id="salary_period" class="form-select salary-period flex-grow-0 w-auto" aria-label="Pay period">
            <option value="per hour" selected>/ hour</option>
            <option value="per annum">/ year</option>
        </select>
        <input type="hidden" id="salary" name="salary">
        <div class="invalid-feedback">Please provide salary</div>
    </div>
    <div class="form-text salary-preview">
        <iconify-icon icon="solar:eye-linear" class="align-middle me-1"></iconify-icon><span>Enter an amount</span>
    </div>
</div>

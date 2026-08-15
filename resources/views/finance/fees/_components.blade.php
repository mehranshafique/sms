@php
    $existingComponents = collect($components ?? []);
    $isProportional = (bool) ($isProportional ?? false);
@endphp

<div class="card border mt-3" id="componentsCard">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-0">{{ __('finance.proportional_allocation') }}</h5>
            <small class="text-muted">{{ __('finance.proportional_allocation_help') }}</small>
        </div>
        <div class="form-check form-switch mb-0">
            <input type="hidden" name="allocation_mode" value="none">
            <input class="form-check-input" type="checkbox" id="allocationModeToggle" name="allocation_mode" value="proportional"
                {{ $isProportional ? 'checked' : '' }}>
            <label class="form-check-label" for="allocationModeToggle">{{ __('finance.split_into_components') }}</label>
        </div>
    </div>
    <div class="card-body {{ $isProportional ? '' : 'd-none' }}" id="componentsBody">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-2" id="componentsTable">
                <thead>
                    <tr>
                        <th style="width:45%">{{ __('finance.component_name') }}</th>
                        <th style="width:25%">{{ __('finance.amount') }}</th>
                        <th style="width:20%">{{ __('finance.share') }}</th>
                        <th style="width:10%"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($existingComponents as $index => $component)
                        <tr class="component-row">
                            <td>
                                <input type="hidden" name="components[{{ $index }}][id]" value="{{ $component->id }}">
                                <input type="text" class="form-control form-control-sm" name="components[{{ $index }}][name]"
                                    value="{{ $component->name }}" maxlength="100">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm component-amount"
                                    name="components[{{ $index }}][amount]" value="{{ number_format((float) $component->amount, 2, '.', '') }}"
                                    min="0" step="0.01">
                            </td>
                            <td class="component-share text-muted">—</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-danger btn-xs sharp remove-component"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <th>{{ __('finance.total') }}</th>
                        <th id="componentsTotal">0.00</th>
                        <th id="componentsTotalShare">0%</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="button" class="btn btn-sm btn-outline-primary" id="addComponent">
            <i class="fa fa-plus me-1"></i> {{ __('finance.add_component') }}
        </button>

        <div class="alert alert-warning mt-3 mb-0 d-none" id="componentsMismatch">
            {{ __('finance.components_must_match_total') }}
        </div>
    </div>
</div>

<template id="componentRowTemplate">
    <tr class="component-row">
        <td>
            <input type="text" class="form-control form-control-sm" name="components[__INDEX__][name]" maxlength="100"
                placeholder="{{ __('finance.component_name') }}">
        </td>
        <td>
            <input type="number" class="form-control form-control-sm component-amount" name="components[__INDEX__][amount]"
                min="0" step="0.01">
        </td>
        <td class="component-share text-muted">—</td>
        <td class="text-end">
            <button type="button" class="btn btn-danger btn-xs sharp remove-component"><i class="fa fa-trash"></i></button>
        </td>
    </tr>
</template>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('allocationModeToggle');
        const body = document.getElementById('componentsBody');
        const table = document.querySelector('#componentsTable tbody');
        const template = document.getElementById('componentRowTemplate');
        const totalCell = document.getElementById('componentsTotal');
        const totalShareCell = document.getElementById('componentsTotalShare');
        const mismatch = document.getElementById('componentsMismatch');
        const feeAmount = document.querySelector('input[name="amount"]');

        if (!toggle || !table) {
            return;
        }

        let index = table.querySelectorAll('.component-row').length;

        function recalc() {
            const total = parseFloat(feeAmount ? feeAmount.value : 0) || 0;
            let sum = 0;

            table.querySelectorAll('.component-row').forEach(function (row) {
                sum += parseFloat(row.querySelector('.component-amount').value) || 0;
            });

            table.querySelectorAll('.component-row').forEach(function (row) {
                const amount = parseFloat(row.querySelector('.component-amount').value) || 0;
                const share = total > 0 ? (amount / total) * 100 : 0;
                row.querySelector('.component-share').textContent = total > 0 ? share.toFixed(2) + '%' : '—';
            });

            totalCell.textContent = sum.toFixed(2);
            totalShareCell.textContent = total > 0 ? ((sum / total) * 100).toFixed(2) + '%' : '—';

            const off = Math.abs(sum - total) > 0.01;
            mismatch.classList.toggle('d-none', !(toggle.checked && off));
        }

        function addRow() {
            const html = template.innerHTML.replace(/__INDEX__/g, index++);
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            table.appendChild(wrapper.firstElementChild);
            recalc();
        }

        toggle.addEventListener('change', function () {
            body.classList.toggle('d-none', !this.checked);

            if (this.checked && table.querySelectorAll('.component-row').length === 0) {
                addRow();
                addRow();
            }

            recalc();
        });

        document.getElementById('addComponent').addEventListener('click', addRow);

        table.addEventListener('click', function (event) {
            const button = event.target.closest('.remove-component');

            if (button) {
                button.closest('tr').remove();
                recalc();
            }
        });

        table.addEventListener('input', recalc);

        if (feeAmount) {
            feeAmount.addEventListener('input', recalc);
        }

        recalc();
    });
</script>

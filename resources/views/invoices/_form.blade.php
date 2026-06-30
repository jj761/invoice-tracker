@php
    // Determines initial row data: validation-failure old() input takes priority
    // (so a failed submit doesn't wipe out rows the user already typed),
// then existing invoice items on edit, then a single blank row on create.
if (old('items')) {
    $initialItems = old('items');
} elseif (isset($invoice)) {
    $initialItems = $invoice->items
        ->map(
            fn($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ],
        )
        ->all();
} else {
    $initialItems = [['description' => '', 'quantity' => '', 'unit_price' => '']];
    }
@endphp

<div class="mb-3">
    <label class="form-label">Client <span class="text-danger">*</span></label>
    <select name="client_id" class="form-select @error('client_id') is-invalid @enderror">
        <option value="">Select a client</option>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}" @selected((int) old('client_id', $invoice->client_id ?? '') === $client->id)>
                {{ $client->name }}
            </option>
        @endforeach
    </select>
    @error('client_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
        <input type="date" name="issue_date" class="form-control @error('issue_date') is-invalid @enderror"
            value="{{ old('issue_date', isset($invoice) ? $invoice->issue_date->format('Y-m-d') : '') }}">
        @error('issue_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Due Date <span class="text-danger">*</span></label>
        <input type="date" name="due_date" class="form-control @error('due_date') is-invalid @enderror"
            value="{{ old('due_date', isset($invoice) ? $invoice->due_date->format('Y-m-d') : '') }}">
        @error('due_date')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">Tax %</label>
    <input type="number" step="0.01" min="0" max="100" name="tax_percent" id="tax_percent"
        class="form-control @error('tax_percent') is-invalid @enderror"
        value="{{ old('tax_percent', $invoice->tax_percent ?? 0) }}">
    @error('tax_percent')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label class="form-label">Notes</label>
    <textarea name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2">{{ old('notes', $invoice->notes ?? '') }}</textarea>
    @error('notes')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<hr>

<h6 class="mb-3">Line Items</h6>
@error('items')
    <div class="alert alert-danger py-2">{{ $message }}</div>
@enderror

<div class="table-responsive">
    <table class="table" id="items-table">
        <thead>
            <tr>
                <th style="width: 45%">Description</th>
                <th style="width: 15%">Quantity</th>
                <th style="width: 15%">Unit Price</th>
                <th style="width: 15%">Line Total</th>
                <th style="width: 10%"></th>
            </tr>
        </thead>
        <tbody id="items-body">
            @foreach ($initialItems as $index => $item)
                <tr class="item-row">
                    <td>
                        <input type="text" name="items[{{ $index }}][description]"
                            class="form-control @error('items.' . $index . '.description') is-invalid @enderror"
                            value="{{ $item['description'] }}">
                        @error('items.' . $index . '.description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td>
                        <input type="number" min="1" step="1" name="items[{{ $index }}][quantity]"
                            class="form-control item-quantity @error('items.' . $index . '.quantity') is-invalid @enderror"
                            value="{{ $item['quantity'] }}">
                        @error('items.' . $index . '.quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td>
                        <input type="number" min="0" step="0.01"
                            name="items[{{ $index }}][unit_price]"
                            class="form-control item-unit-price @error('items.' . $index . '.unit_price') is-invalid @enderror"
                            value="{{ $item['unit_price'] }}">
                        @error('items.' . $index . '.unit_price')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </td>
                    <td class="item-line-total align-middle">0.00</td>
                    <td class="align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<button type="button" id="add-row" class="btn btn-sm btn-outline-primary mb-3">Add Line</button>

<div class="row justify-content-end">
    <div class="col-md-4">
        <table class="table table-sm">
            <tr>
                <td>Subtotal</td>
                <td class="text-end" id="calc-subtotal">0.00</td>
            </tr>
            <tr>
                <td>Tax</td>
                <td class="text-end" id="calc-tax">0.00</td>
            </tr>
            <tr class="fw-bold">
                <td>Total</td>
                <td class="text-end" id="calc-total">0.00</td>
            </tr>
        </table>
        <p class="text-muted small">
            These totals are for preview only. The server always recalculates authoritative values on save.
        </p>
    </div>
</div>

@push('scripts')
    <script>
        (function() {
            const itemsBody = document.getElementById('items-body');
            const addRowBtn = document.getElementById('add-row');
            const taxInput = document.getElementById('tax_percent');
            let rowIndex = itemsBody.querySelectorAll('.item-row').length;

            function rowTemplate(index) {
                return `
            <tr class="item-row">
                <td>
                    <input type="text" name="items[${index}][description]" class="form-control">
                </td>
                <td>
                    <input type="number" min="1" step="1" name="items[${index}][quantity]" class="form-control item-quantity" value="1">
                </td>
                <td>
                    <input type="number" min="0" step="0.01" name="items[${index}][unit_price]" class="form-control item-unit-price" value="0">
                </td>
                <td class="item-line-total align-middle">0.00</td>
                <td class="align-middle">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-row">&times;</button>
                </td>
            </tr>
        `;
            }

            function recalculate() {
                let subtotal = 0;
                itemsBody.querySelectorAll('.item-row').forEach((row) => {
                    const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
                    const price = parseFloat(row.querySelector('.item-unit-price').value) || 0;
                    const lineTotal = qty * price;
                    row.querySelector('.item-line-total').textContent = lineTotal.toFixed(2);
                    subtotal += lineTotal;
                });
                const taxPercent = parseFloat(taxInput.value) || 0;
                const tax = subtotal * (taxPercent / 100);
                const total = subtotal + tax;
                document.getElementById('calc-subtotal').textContent = subtotal.toFixed(2);
                document.getElementById('calc-tax').textContent = tax.toFixed(2);
                document.getElementById('calc-total').textContent = total.toFixed(2);
            }

            addRowBtn.addEventListener('click', () => {
                itemsBody.insertAdjacentHTML('beforeend', rowTemplate(rowIndex));
                rowIndex++;
                recalculate();
            });

            itemsBody.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-row')) {
                    const rows = itemsBody.querySelectorAll('.item-row');
                    if (rows.length > 1) {
                        e.target.closest('.item-row').remove();
                        recalculate();
                    }
                }
            });

            itemsBody.addEventListener('input', (e) => {
                if (e.target.classList.contains('item-quantity') || e.target.classList.contains(
                        'item-unit-price')) {
                    recalculate();
                }
            });

            taxInput.addEventListener('input', recalculate);

            recalculate();
        })();
    </script>
@endpush

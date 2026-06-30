@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Invoice {{ $invoice->invoice_number }}</h4>
        <div>
            <a href="{{ route('invoices.pdf.download', $invoice) }}" class="btn btn-outline-secondary">Download PDF</a>
            <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Client</h6>
                    <p class="mb-1"><strong>{{ $invoice->client->name }}</strong></p>
                    <p class="mb-1">{{ $invoice->client->email }}</p>
                    @if ($invoice->client->company_name)
                        <p class="mb-1">{{ $invoice->client->company_name }}</p>
                    @endif
                    @if ($invoice->client->billing_address)
                        <p class="mb-0 text-muted">{{ $invoice->client->billing_address }}</p>
                    @endif
                    <a href="{{ route('clients.show', $invoice->client) }}" class="d-inline-block mt-2">View Client</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-subtitle mb-3 text-muted">Invoice Details</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Status</dt>
                        <dd class="col-sm-7">@include('partials.status-badge', ['status' => $invoice->status])</dd>

                        <dt class="col-sm-5">Issue Date</dt>
                        <dd class="col-sm-7">{{ $invoice->issue_date->format('Y-m-d') }}</dd>

                        <dt class="col-sm-5">Due Date</dt>
                        <dd class="col-sm-7">{{ $invoice->due_date->format('Y-m-d') }}</dd>

                        <dt class="col-sm-5">Tax %</dt>
                        <dd class="col-sm-7">{{ number_format($invoice->tax_percent, 2) }}</dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    @if ($invoice->notes)
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Notes</h6>
                <p class="mb-0">{{ $invoice->notes }}</p>
            </div>
        </div>
    @endif

    <h6 class="mb-3">Line Items</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm bg-white rounded">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Quantity</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end">Subtotal</td>
                    <td class="text-end">{{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Tax</td>
                    <td class="text-end">{{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Grand Total</td>
                    <td class="text-end">{{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">Amount Paid</td>
                    <td class="text-end">{{ number_format($invoice->amount_paid, 2) }}</td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">Amount Due</td>
                    <td class="text-end">{{ number_format($invoice->amount_due, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($invoice->overdueLogs->isNotEmpty())
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-subtitle mb-2 text-muted">Overdue History</h6>
                <ul class="mb-0">
                    @foreach ($invoice->overdueLogs as $log)
                        <li>Flagged overdue on {{ $log->flagged_at->format('Y-m-d H:i') }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <h6 class="mb-3">Payment History</h6>
            <div class="table-responsive">
                <table class="table table-sm bg-white rounded">
                    <thead class="table-light">
                        <tr>
                            <th>Amount</th>
                            <th>Paid On</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoice->payments as $payment)
                            <tr>
                                <td>{{ number_format($payment->amount, 2) }}</td>
                                <td>{{ $payment->paid_on->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center text-muted">No payments recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-md-6">
            <h6 class="mb-3">Record Payment</h6>
            @if ($invoice->amount_due > 0)
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('invoices.payments.store', $invoice) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="amount"
                                    class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}">
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Amount due: {{ number_format($invoice->amount_due, 2) }}</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Paid On <span class="text-danger">*</span></label>
                                <input type="date" name="paid_on"
                                    class="form-control @error('paid_on') is-invalid @enderror"
                                    value="{{ old('paid_on', now()->format('Y-m-d')) }}">
                                @error('paid_on')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">Record Payment</button>
                        </form>
                    </div>
                </div>
            @else
                <p class="text-muted">This invoice is fully paid.</p>
            @endif
        </div>
    </div>
@endsection

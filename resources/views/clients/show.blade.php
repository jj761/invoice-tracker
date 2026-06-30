@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>{{ $client->name }}</h4>
        <div>
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Back</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-sm-2">Email</dt>
                <dd class="col-sm-10">{{ $client->email }}</dd>

                <dt class="col-sm-2">Phone</dt>
                <dd class="col-sm-10">{{ $client->phone ?? '—' }}</dd>

                <dt class="col-sm-2">Company</dt>
                <dd class="col-sm-10">{{ $client->company_name ?? '—' }}</dd>

                <dt class="col-sm-2">Billing Address</dt>
                <dd class="col-sm-10">{{ $client->billing_address ?? '—' }}</dd>
            </dl>
        </div>
    </div>

    <h5 class="mb-3">Invoices</h5>
    <div class="table-responsive">
        <table class="table table-hover bg-white rounded">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Status</th>
                    <th>Grand Total</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($client->invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>@include('partials.status-badge', ['status' => $invoice->status])</td>
                        <td>{{ number_format($invoice->grand_total, 2) }}</td>
                        <td>{{ number_format($invoice->amount_due, 2) }}</td>
                        <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
                        <td>
                            <a href="{{ route('invoices.show', $invoice) }}"
                                class="btn btn-sm btn-outline-primary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted">No invoices yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

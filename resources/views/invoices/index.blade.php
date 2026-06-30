@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Invoices</h4>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">New Invoice</a>
    </div>

    <form method="GET" action="{{ route('invoices.index') }}" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="status" class="form-select" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach (['unpaid', 'partially_paid', 'paid', 'overdue'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ ucfirst(str_replace('_', ' ', $status)) }}
                    </option>
                @endforeach
            </select>
        </div>
        @if (request('status'))
            <div class="col-auto">
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Clear</a>
            </div>
        @endif
    </form>

    <div class="table-responsive">
        <table class="table table-hover bg-white rounded">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>
                        <a
                            href="{{ route('invoices.index', array_merge(request()->query(), ['sort' => 'status', 'direction' => request('sort') === 'status' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                            Status
                        </a>
                    </th>
                    <th>Grand Total</th>
                    <th>Amount Due</th>
                    <th>
                        <a
                            href="{{ route('invoices.index', array_merge(request()->query(), ['sort' => 'due_date', 'direction' => request('sort') === 'due_date' && request('direction') === 'asc' ? 'desc' : 'asc'])) }}">
                            Due Date
                        </a>
                    </th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->client->name }}</td>
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
                        <td colspan="7" class="text-center text-muted">No invoices found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $invoices->links() }}
@endsection

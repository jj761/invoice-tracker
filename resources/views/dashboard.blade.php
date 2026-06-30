@extends('layouts.app')

@section('content')
    <h4 class="mb-4">Dashboard</h4>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="card-title">Total Invoiced</div>
                    <h4>BHD {{ number_format($totalInvoiced, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="card-title">Total Collected</div>
                    <h4>BHD {{ number_format($totalCollected, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="card-title">Outstanding</div>
                    <h4>BHD {{ number_format($outstanding, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="card-title">Overdue</div>
                    <h4>{{ $countByStatus['overdue'] ?? 0 }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @foreach (['unpaid' => 'secondary', 'partially_paid' => 'warning', 'paid' => 'success', 'overdue' => 'danger'] as $status => $color)
            <div class="col-md-3">
                <div class="card border-{{ $color }}">
                    <div class="card-body text-center">
                        <div class="text-muted small">{{ ucfirst(str_replace('_', ' ', $status)) }}</div>
                        <h5>{{ $countByStatus[$status] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h6 class="mb-3">Recent Invoices</h6>
    <div class="table-responsive">
        <table class="table table-hover table-sm bg-white rounded">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Client</th>
                    <th>Grand Total</th>
                    <th>Amount Due</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->client->name }}</td>
                        <td>BHD {{ number_format($invoice->grand_total, 2) }}</td>
                        <td>BHD {{ number_format($invoice->amount_due, 2) }}</td>
                        <td>{{ $invoice->due_date->format('d M Y') }}</td>
                        <td>@include('partials.status-badge', ['status' => $invoice->status])</td>
                        <td><a href="{{ route('invoices.show', $invoice) }}"
                                class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No invoices yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

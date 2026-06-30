<?php

use Livewire\Attributes\Computed;
use Livewire\Component;
use App\Models\Invoice;

new class extends Component {
    #[Computed]
    public function invoices()
    {
        return Invoice::with('client')->latest()->take(10)->get();
    }

    #[Computed]
    public function totalInvoiced()
    {
        return Invoice::with('items')->get()->sum(fn(Invoice $i) => $i->grand_total);
    }

    #[Computed]
    public function totalCollected()
    {
        return Invoice::with('payments')->get()->sum(fn(Invoice $i) => $i->amount_paid);
    }

    #[Computed]
    public function outstanding()
    {
        return $this->totalInvoiced - $this->totalCollected;
    }

    #[Computed]
    public function countByStatus()
    {
        return Invoice::selectRaw('status, count(*) as count')->groupBy('status')->pluck('count', 'status');
    }
};
?>
<div wire:poll.10s>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-white bg-primary">
                <div class="card-body">
                    <div class="card-title">Total Invoiced</div>
                    <h4>{{ config('app.currency') }} {{ number_format($this->totalInvoiced, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <div class="card-title">Total Collected</div>
                    <h4>{{ config('app.currency') }} {{ number_format($this->totalCollected, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-warning">
                <div class="card-body">
                    <div class="card-title">Outstanding</div>
                    <h4>{{ config('app.currency') }} {{ number_format($this->outstanding, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <div class="card-title">Overdue</div>
                    <h4>{{ $this->countByStatus['overdue'] ?? 0 }}</h4>
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
                        <h5>{{ $this->countByStatus[$status] ?? 0 }}</h5>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <h6 class="mb-3">
        Recent Invoices
        <span class="badge bg-light text-muted fw-normal" style="font-size: 0.7rem;">auto-refreshes every 10s</span>
    </h6>
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
                @forelse($this->invoices as $invoice)
                    <tr wire:key="invoice-{{ $invoice->id }}">
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->client->name }}</td>
                        <td>{{ config('app.currency') }} {{ number_format($invoice->grand_total, 2) }}</td>
                        <td>{{ config('app.currency') }} {{ number_format($invoice->amount_due, 2) }}</td>
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
</div>

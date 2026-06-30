@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Edit Invoice {{ $invoice->invoice_number }}</h4>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline-secondary">Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('invoices.update', $invoice) }}">
                @csrf
                @method('PUT')
                @include('invoices._form')
                <button type="submit" class="btn btn-primary">Update Invoice</button>
            </form>
        </div>
    </div>
@endsection

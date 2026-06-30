@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>New Invoice</h4>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('invoices.store') }}">
                @csrf
                @include('invoices._form')
                <button type="submit" class="btn btn-primary">Create Invoice</button>
            </form>
        </div>
    </div>
@endsection

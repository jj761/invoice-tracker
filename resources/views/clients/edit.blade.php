@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Edit Client</h4>
        <a href="{{ route('clients.show', $client) }}" class="btn btn-outline-secondary">Back</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.update', $client) }}">
                @csrf
                @method('PUT')
                @include('clients._form')
                <button type="submit" class="btn btn-primary">Update Client</button>
            </form>
        </div>
    </div>
@endsection

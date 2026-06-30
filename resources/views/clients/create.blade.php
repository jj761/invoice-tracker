@extends('layouts.app')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Add Client</h4>
        <a href="{{ route('clients.index') }}" class="btn btn-outline-secondary">Back</a>
    </div>
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('clients.store') }}">
                @csrf
                @include('clients._form')
                <button type="submit" class="btn btn-primary">Create Client</button>
            </form>
        </div>
    </div>
@endsection

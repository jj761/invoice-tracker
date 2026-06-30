@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4>Clients</h4>
        <a href="{{ route('clients.create') }}" class="btn btn-primary">Add Client</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover bg-white rounded">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($clients as $client)
                    <tr>
                        <td>{{ $client->name }}</td>
                        <td>{{ $client->email }}</td>
                        <td>{{ $client->phone ?? '—' }}</td>
                        <td>{{ $client->company_name ?? '—' }}</td>
                        <td>
                            <a href="{{ route('clients.show', $client) }}" class="btn btn-sm btn-outline-primary">View</a>
                            <a href="{{ route('clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No clients yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $clients->links() }}
@endsection

@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Dashboard Test</h1>
    <p>If you see this, the basic dashboard is working!</p>

    <h2>Debug Info:</h2>
    <ul>
        <li>User ID: {{ auth()->id() ?? 'Not authenticated' }}</li>
        <li>User Email: {{ auth()->user()->email ?? 'N/A' }}</li>
        <li>Tenant: {{ $tenant->name ?? 'No tenant' }}</li>
        <li>Subdomain: {{ request()->route('subdomain') ?? 'No subdomain' }}</li>
    </ul>

    <h2>Route Tests:</h2>
    <ul>
        <li><a href="{{ route('orders.index', ['subdomain' => request()->route('subdomain')]) }}">Orders (should work)</a></li>
        <li><a href="{{ route('product-mappings.index', ['subdomain' => request()->route('subdomain')]) }}">Product Mappings (should work)</a></li>
    </ul>

    <h2>Stats Test:</h2>
    <p>Total Orders: {{ $stats['total_orders'] ?? 'Error loading stats' }}</p>
</div>
@endsection

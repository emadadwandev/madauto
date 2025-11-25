@extends('emails.layout')

@section('content')
    <h2 style="color: #e3342f;">Sync Failed Notification</h2>
    <p>Hello,</p>
    <p>We encountered an issue while syncing an order to Loyverse.</p>

    <h3>Order Details:</h3>
    <ul>
        <li><strong>Order ID:</strong> {{ $order->id }}</li>
        <li><strong>Platform:</strong> {{ ucfirst($order->platform) }}</li>
        <li><strong>Platform Order ID:</strong> {{ $order->platform_order_id }}</li>
        <li><strong>Tenant:</strong> {{ $order->tenant->name ?? 'N/A' }}</li>
    </ul>

    <h3>Error Details:</h3>
    <div style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; border: 1px solid #f5c6cb;">
        {{ $errorMessage }}
    </div>

    <p>Please check the dashboard for more details and to retry the sync.</p>

    <a href="{{ $dashboardUrl }}" class="button">View Order</a>
@endsection

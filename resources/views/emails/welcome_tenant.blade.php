@extends('emails.layout')

@section('content')
    <h2>Welcome to {{ config('app.name') }}!</h2>
    <p>Dear {{ $user->name }},</p>
    <p>Thank you for registering with us. Your tenant account has been successfully created.</p>

    <h3>Your Account Details:</h3>
    <ul>
        <li><strong>Company Name:</strong> {{ $tenant->name }}</li>
        <li><strong>Subdomain:</strong> {{ $tenant->subdomain }}</li>
        <li><strong>Dashboard URL:</strong> <a href="{{ $dashboardUrl }}">{{ $dashboardUrl }}</a></li>
    </ul>

    <h3>Admin Credentials:</h3>
    <ul>
        <li><strong>Username/Email:</strong> {{ $user->email }}</li>
        <li><strong>Password:</strong> {{ $password }}</li>
    </ul>

    <p>Please keep these credentials safe. We recommend changing your password after your first login.</p>

    <a href="{{ $dashboardUrl }}" class="button">Go to Dashboard</a>
@endsection

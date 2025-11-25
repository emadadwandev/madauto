@extends('emails.layout')

@section('content')
    <h2>Reset Password Request</h2>
    <p>Hello,</p>
    <p>You are receiving this email because we received a password reset request for your account.</p>

    <p>Click the button below to reset your password:</p>

    <a href="{{ $url }}" class="button">Reset Password</a>

    <p>This password reset link will expire in {{ config('auth.passwords.'.config('auth.defaults.passwords').'.expire') }} minutes.</p>

    <p>If you did not request a password reset, no further action is required.</p>
@endsection
